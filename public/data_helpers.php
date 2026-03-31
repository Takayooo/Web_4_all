<?php
require_once __DIR__ . '/../config/database.php';

function db(): PDO
{
    global $pdo;

    static $schemaReady = false;

    if (!$schemaReady) {
        $schemaReady = true; // mis à true AVANT l'appel pour éviter la récursion infinie
        ensure_app_schema($pdo);
    }

    return $pdo;
}

function db_fetch_all(string $sql, array $params = []): array
{
    $statement = db()->prepare($sql);
    $statement->execute($params);

    return $statement->fetchAll(PDO::FETCH_ASSOC);
}

function db_fetch_one(string $sql, array $params = []): ?array
{
    $statement = db()->prepare($sql);
    $statement->execute($params);
    $row = $statement->fetch(PDO::FETCH_ASSOC);

    return $row === false ? null : $row;
}

function db_execute(string $sql, array $params = []): bool
{
    $statement = db()->prepare($sql);

    return $statement->execute($params);
}

function column_exists(string $table, string $column): bool
{
    $row = db_fetch_one(
        'SELECT 1
         FROM information_schema.columns
         WHERE table_schema = DATABASE()
           AND table_name = :nom_table
           AND column_name = :nom_colonne',
        [':nom_table' => $table, ':nom_colonne' => $column]
    );

    return $row !== null;
}

function ensure_app_schema(PDO $database): void
{
    $updates = [];

    if (!column_exists('offre', 'contrat')) {
        $updates[] = "ALTER TABLE offre ADD COLUMN contrat VARCHAR(30) NOT NULL DEFAULT 'stage' AFTER titre";
    }

    if (!column_exists('offre', 'date_creation')) {
        $updates[] = 'ALTER TABLE offre ADD COLUMN date_creation DATETIME NULL DEFAULT CURRENT_TIMESTAMP AFTER date_publication';
    }

    if (!column_exists('etudiant', 'id_pilote')) {
        $updates[] = 'ALTER TABLE etudiant ADD COLUMN id_pilote INT NULL AFTER id_utilisateur';
    }

    foreach ($updates as $sql) {
        $database->exec($sql);
    }

    if (column_exists('offre', 'date_creation')) {
        $database->exec("UPDATE offre SET date_creation = COALESCE(date_creation, CONCAT(date_publication, ' 00:00:00'))");
    }

    if (column_exists('offre', 'contrat')) {
        $database->exec("UPDATE offre SET contrat = CASE WHEN LOWER(titre) LIKE '%alternance%' THEN 'alternance' ELSE 'stage' END WHERE contrat IS NULL OR contrat = ''");
    }
}

function company_name_from_email(?string $email, int $companyId): string
{
    if (!$email) {
        return 'Entreprise ' . $companyId;
    }

    $parts = explode('@', $email, 2);
    $domain = $parts[1] ?? '';
    $firstLabel = explode('.', $domain)[0] ?? '';
    $firstLabel = trim(preg_replace('/[-_]+/', ' ', $firstLabel));

    if ($firstLabel === '') {
        return 'Entreprise ' . $companyId;
    }

    return ucwords($firstLabel);
}

function resolve_company_display_name(array $row): string
{
    $nom = trim((string) ($row['nom'] ?? ''));
    $prenom = trim((string) ($row['prenom'] ?? ''));

    if ($prenom === '' && $nom !== '') {
        return $nom;
    }

    return company_name_from_email($row['entreprise_email'] ?? $row['email'] ?? null, (int) ($row['id_entreprise'] ?? 0));
}

function normalize_user_row(array $row): array
{
    $isAdmin = !empty($row['admin_account_id']);
    $isEntreprise = !$isAdmin && !empty($row['id_entreprise']);
    $isPilote = !$isAdmin && !$isEntreprise && !empty($row['pilot_account_id']);
    $role = $isAdmin ? 'administrateur' : ($isEntreprise ? 'entreprise' : ($isPilote ? 'pilote' : 'eleve'));

    $user = [
        'id' => (int) $row['id_utilisateur'],
        'email' => (string) $row['email'],
        'password' => (string) $row['motdepasse'],
        'role' => $role,
        'nom' => $role === 'entreprise' ? resolve_company_display_name($row) : (string) ($row['nom'] ?? ''),
        'prenom' => $role === 'entreprise' ? '' : (string) ($row['prenom'] ?? ''),
    ];

    if ($role === 'eleve') {
        $user['pilote_id'] = isset($row['pilote_utilisateur_id']) ? (int) $row['pilote_utilisateur_id'] : null;
    }

    if ($role === 'entreprise') {
        $user['entreprise_id'] = (int) $row['id_entreprise'];
        $user['secteur'] = (string) ($row['secteur'] ?? '');
        $user['ville'] = (string) ($row['ville'] ?? '');
        $user['description'] = (string) ($row['entreprise_description'] ?? '');
        $user['telephone'] = (string) ($row['telephone'] ?? '');
        $user['note'] = isset($row['entreprise_note']) ? (float) $row['entreprise_note'] : 0.0;
    }

    if ($role === 'pilote') {
        $user['eleves'] = [];
    }

    return $user;
}

function user_select_sql(): string
{
    return "SELECT u.id_utilisateur,
                   u.nom,
                   u.prenom,
                   u.email,
                   u.motdepasse,
                   e.id_etudiant,
                   e.id_pilote,
                   admin_role.id_admin AS admin_account_id,
                   pilot_role.id_pilotes AS pilot_account_id,
                   pilot_ref.id_utilisateur AS pilote_utilisateur_id,
                   ce.id_entreprise,
                   ce.ville,
                   ce.description_ AS entreprise_description,
                   ce.secteur,
                   ce.email AS entreprise_email,
                   ce.telephone,
                   COALESCE(notes.note, 0) AS entreprise_note
            FROM utilisateur u
            LEFT JOIN etudiant e ON e.id_utilisateur = u.id_utilisateur
            LEFT JOIN admin admin_role ON admin_role.id_utilisateur = u.id_utilisateur
            LEFT JOIN pilotes pilot_role ON pilot_role.id_utilisateur = u.id_utilisateur
            LEFT JOIN pilotes pilot_ref ON pilot_ref.id_pilotes = e.id_pilote
            LEFT JOIN compte_entreprise ce ON ce.id_utilisateur = u.id_utilisateur
            LEFT JOIN (
                SELECT id_entreprise, ROUND(AVG(note), 1) AS note
                FROM evaluation
                GROUP BY id_entreprise
            ) notes ON notes.id_entreprise = ce.id_entreprise";
}

function get_all_users(): array
{
    $rows = db_fetch_all(user_select_sql() . ' ORDER BY u.id_utilisateur ASC');

    return array_map('normalize_user_row', $rows);
}

function get_all_users_indexed(): array
{
    $indexed = [];

    foreach (get_all_users() as $user) {
        $indexed[$user['id']] = $user;
    }

    return $indexed;
}

function get_user_by_id(int $userId): ?array
{
    $row = db_fetch_one(user_select_sql() . ' WHERE u.id_utilisateur = :id_utilisateur', [':id_utilisateur' => $userId]);

    return $row ? normalize_user_row($row) : null;
}

function get_user_by_email(string $email): ?array
{
    $row = db_fetch_one(user_select_sql() . ' WHERE LOWER(u.email) = LOWER(:email)', [':email' => $email]);

    return $row ? normalize_user_row($row) : null;
}

function authenticate_user(string $email, string $password, string $role): ?array
{
    $user = get_user_by_email($email);

    if (!$user) {
        return null;
    }

    if ($user['password'] !== $password || $user['role'] !== $role) {
        return null;
    }

    return $user;
}

function authenticate_user_auto(string $email, string $password): ?array
{
    $user = get_user_by_email($email);

    if (!$user) {
        return null;
    }

    if ($user['password'] !== $password) {
        return null;
    }

    return $user;
}

function email_exists(string $email, ?int $excludeUserId = null): bool
{
    $sql = 'SELECT 1 FROM utilisateur WHERE LOWER(email) = LOWER(:email)';
    $params = [':email' => $email];

    if ($excludeUserId !== null) {
        $sql .= ' AND id_utilisateur <> :id_exclus';
        $params[':id_exclus'] = $excludeUserId;
    }

    return db_fetch_one($sql, $params) !== null;
}

function find_pilot_record_id_by_user_id(int $pilotUserId): ?int
{
    $row = db_fetch_one('SELECT id_pilotes FROM pilotes WHERE id_utilisateur = :id_utilisateur', [':id_utilisateur' => $pilotUserId]);

    return $row ? (int) $row['id_pilotes'] : null;
}

function find_student_record_id_by_user_id(int $userId): ?int
{
    $row = db_fetch_one('SELECT id_etudiant FROM etudiant WHERE id_utilisateur = :id_utilisateur', [':id_utilisateur' => $userId]);

    return $row ? (int) $row['id_etudiant'] : null;
}

function get_pilot_by_email(string $email): ?array
{
    $user = get_user_by_email($email);

    return $user && $user['role'] === 'pilote' ? $user : null;
}

function create_student_account(string $email, string $password, string $nom, string $prenom, int $pilotUserId): bool
{
    $pilotRecordId = find_pilot_record_id_by_user_id($pilotUserId);

    if ($pilotRecordId === null) {
        return false;
    }

    $database = db();
    $database->beginTransaction();

    try {
        db_execute(
            'INSERT INTO utilisateur (nom, prenom, email, motdepasse) VALUES (:nom, :prenom, :email, :motdepasse)',
            [':nom' => $nom, ':prenom' => $prenom, ':email' => $email, ':motdepasse' => $password]
        );

        $userId = (int) $database->lastInsertId();

        db_execute(
            'INSERT INTO etudiant (id_utilisateur, id_pilote) VALUES (:id_utilisateur, :id_pilote)',
            [':id_utilisateur' => $userId, ':id_pilote' => $pilotRecordId]
        );

        $database->commit();

        return true;
    } catch (Throwable $exception) {
        if ($database->inTransaction()) {
            $database->rollBack();
        }

        return false;
    }
}

function create_pilot_account(string $email, string $password, string $nom, string $prenom): bool
{
    $database = db();
    $database->beginTransaction();

    try {
        db_execute(
            'INSERT INTO utilisateur (nom, prenom, email, motdepasse) VALUES (:nom, :prenom, :email, :motdepasse)',
            [':nom' => $nom, ':prenom' => $prenom, ':email' => $email, ':motdepasse' => $password]
        );

        $userId = (int) $database->lastInsertId();

        db_execute('INSERT INTO pilotes (id_utilisateur) VALUES (:id_utilisateur)', [':id_utilisateur' => $userId]);

        $database->commit();

        return true;
    } catch (Throwable $exception) {
        if ($database->inTransaction()) {
            $database->rollBack();
        }

        return false;
    }
}

function create_company_account(string $email, string $password, string $companyName, string $secteur, string $ville): bool
{
    $database = db();
    $database->beginTransaction();

    try {
        db_execute(
            'INSERT INTO utilisateur (nom, prenom, email, motdepasse) VALUES (:nom, :prenom, :email, :motdepasse)',
            [':nom' => $companyName, ':prenom' => '', ':email' => $email, ':motdepasse' => $password]
        );

        $userId = (int) $database->lastInsertId();

        db_execute(
            'INSERT INTO compte_entreprise (ville, description_, secteur, email, telephone, id_utilisateur) VALUES (:ville, :description, :secteur, :email, :telephone, :id_utilisateur)',
            [':ville' => $ville, ':description' => '', ':secteur' => $secteur, ':email' => $email, ':telephone' => '', ':id_utilisateur' => $userId]
        );

        $database->commit();

        return true;
    } catch (Throwable $exception) {
        if ($database->inTransaction()) {
            $database->rollBack();
        }

        return false;
    }
}

function update_basic_account(int $userId, string $nom, string $prenom, string $email): bool
{
    return db_execute(
        'UPDATE utilisateur SET nom = :nom, prenom = :prenom, email = :email WHERE id_utilisateur = :id_utilisateur',
        [':nom' => $nom, ':prenom' => $prenom, ':email' => $email, ':id_utilisateur' => $userId]
    );
}

function update_company_account(int $userId, string $companyName, string $secteur, string $ville, string $email): bool
{
    $database = db();
    $database->beginTransaction();

    try {
        db_execute(
            'UPDATE utilisateur SET nom = :nom, prenom = :prenom, email = :email WHERE id_utilisateur = :id_utilisateur',
            [':nom' => $companyName, ':prenom' => '', ':email' => $email, ':id_utilisateur' => $userId]
        );

        db_execute(
            'UPDATE compte_entreprise SET secteur = :secteur, ville = :ville, email = :email WHERE id_utilisateur = :id_utilisateur',
            [':secteur' => $secteur, ':ville' => $ville, ':email' => $email, ':id_utilisateur' => $userId]
        );

        $database->commit();

        return true;
    } catch (Throwable $exception) {
        if ($database->inTransaction()) {
            $database->rollBack();
        }

        return false;
    }
}

function delete_account_by_admin(int $userId): bool
{
    $target = get_user_by_id($userId);

    if (!$target || $target['role'] === 'administrateur') {
        return false;
    }

    $database = db();
    $database->beginTransaction();

    try {
        if ($target['role'] === 'pilote') {
            $pilotRecordId = find_pilot_record_id_by_user_id($userId);

            if ($pilotRecordId !== null) {
                db_execute('UPDATE etudiant SET id_pilote = NULL WHERE id_pilote = :id_pilote', [':id_pilote' => $pilotRecordId]);
                db_execute('DELETE FROM evaluation WHERE id_pilotes = :id_pilotes', [':id_pilotes' => $pilotRecordId]);
            }

            db_execute('DELETE FROM pilotes WHERE id_utilisateur = :id_utilisateur', [':id_utilisateur' => $userId]);
        } elseif ($target['role'] === 'entreprise') {
            $entrepriseId = (int) ($target['entreprise_id'] ?? 0);

            if ($entrepriseId > 0) {
                $offerRows = db_fetch_all('SELECT id_offre FROM proposer WHERE id_entreprise = :id_entreprise', [':id_entreprise' => $entrepriseId]);
                $offerIds = array_map(
                    static fn(array $row): int => (int) $row['id_offre'],
                    $offerRows
                );

                db_execute('DELETE FROM evaluation WHERE id_entreprise = :id_entreprise', [':id_entreprise' => $entrepriseId]);
                db_execute('DELETE FROM proposer WHERE id_entreprise = :id_entreprise', [':id_entreprise' => $entrepriseId]);

                foreach ($offerIds as $offerId) {
                    db_execute(
                        'UPDATE etudiant SET id_tableau_candidatures = NULL WHERE id_tableau_candidatures IN (SELECT id_tableau_candidatures FROM tableau_candidatures WHERE id_offre = :id_offre)',
                        [':id_offre' => $offerId]
                    );
                    db_execute('DELETE FROM wishlist WHERE id_offre = :id_offre', [':id_offre' => $offerId]);
                    db_execute('DELETE FROM requerir WHERE id_offre = :id_offre', [':id_offre' => $offerId]);
                    db_execute('DELETE FROM tableau_candidatures WHERE id_offre = :id_offre', [':id_offre' => $offerId]);
                    db_execute('DELETE FROM offre WHERE id_offre = :id_offre', [':id_offre' => $offerId]);
                }
            }

            db_execute('DELETE FROM compte_entreprise WHERE id_utilisateur = :id_utilisateur', [':id_utilisateur' => $userId]);
        }

        db_execute('DELETE FROM utilisateur WHERE id_utilisateur = :id_utilisateur', [':id_utilisateur' => $userId]);

        $database->commit();

        return true;
    } catch (Throwable $exception) {
        if ($database->inTransaction()) {
            $database->rollBack();
        }

        return false;
    }
}

function get_students_by_pilot_user_id(int $pilotUserId): array
{
    $rows = db_fetch_all(
        user_select_sql() . ' WHERE e.id_etudiant IS NOT NULL AND e.id_pilote = (SELECT id_pilotes FROM pilotes WHERE id_utilisateur = :id_utilisateur) ORDER BY u.nom, u.prenom',
        [':id_utilisateur' => $pilotUserId]
    );

    return array_map('normalize_user_row', $rows);
}

function get_all_pilots(): array
{
    $rows = db_fetch_all(user_select_sql() . ' WHERE pilot_role.id_pilotes IS NOT NULL AND ce.id_entreprise IS NULL AND admin_role.id_admin IS NULL ORDER BY u.nom, u.prenom');

    return array_map('normalize_user_row', $rows);
}

function get_all_admins(): array
{
    $rows = db_fetch_all(user_select_sql() . ' WHERE admin_role.id_admin IS NOT NULL ORDER BY u.nom, u.prenom');

    return array_map('normalize_user_row', $rows);
}

function get_entreprises_map(): array
{
    $rows = db_fetch_all(
        'SELECT ce.id_entreprise,
                ce.ville,
                ce.description_ AS description,
                ce.secteur,
                ce.email AS entreprise_email,
                ce.telephone,
                u.nom,
                u.prenom,
                COALESCE(notes.note, 0) AS note
         FROM compte_entreprise ce
         INNER JOIN utilisateur u ON u.id_utilisateur = ce.id_utilisateur
         LEFT JOIN (
             SELECT id_entreprise, ROUND(AVG(note), 1) AS note
             FROM evaluation
             GROUP BY id_entreprise
         ) notes ON notes.id_entreprise = ce.id_entreprise
         ORDER BY ce.id_entreprise ASC'
    );

    $entreprises = [];

    foreach ($rows as $row) {
        $id = (int) $row['id_entreprise'];
        $entreprises[$id] = [
            'id' => $id,
            'nom' => resolve_company_display_name([
                'nom' => $row['nom'],
                'prenom' => $row['prenom'],
                'entreprise_email' => $row['entreprise_email'],
                'id_entreprise' => $id,
            ]),
            'note' => (float) $row['note'],
            'secteur' => (string) ($row['secteur'] ?? ''),
            'ville' => (string) ($row['ville'] ?? ''),
            'description' => (string) ($row['description'] ?? ''),
            'email' => (string) ($row['entreprise_email'] ?? ''),
            'telephone' => (string) ($row['telephone'] ?? ''),
        ];
    }

    return $entreprises;
}

function get_all_enterprises(): array
{
    return array_values(get_entreprises_map());
}

function get_pilot_company_evaluations(int $pilotUserId): array
{
    $pilotRecordId = find_pilot_record_id_by_user_id($pilotUserId);

    if ($pilotRecordId === null) {
        return [];
    }

    $rows = db_fetch_all(
        'SELECT ce.id_utilisateur AS company_user_id,
                e.note,
                e.commentaire,
                e.date_publication
         FROM evaluation e
         INNER JOIN compte_entreprise ce ON ce.id_entreprise = e.id_entreprise
         WHERE e.id_pilotes = :id_pilotes
         ORDER BY ce.id_utilisateur ASC',
        [':id_pilotes' => $pilotRecordId]
    );

    $evaluations = [];

    foreach ($rows as $row) {
        $evaluations[(int) $row['company_user_id']] = [
            'note' => isset($row['note']) ? (int) $row['note'] : null,
            'commentaire' => (string) ($row['commentaire'] ?? ''),
            'date_publication' => (string) ($row['date_publication'] ?? ''),
        ];
    }

    return $evaluations;
}

function upsert_company_evaluation(int $pilotUserId, int $companyUserId, int $note, string $commentaire = ''): bool
{
    $pilotRecordId = find_pilot_record_id_by_user_id($pilotUserId);
    $company = get_user_by_id($companyUserId);

    if ($pilotRecordId === null || !$company || $company['role'] !== 'entreprise') {
        return false;
    }

    $entrepriseId = (int) ($company['entreprise_id'] ?? 0);
    if ($entrepriseId <= 0) {
        return false;
    }

    $existing = db_fetch_one(
        'SELECT id_evaluation
         FROM evaluation
         WHERE id_pilotes = :id_pilotes AND id_entreprise = :id_entreprise',
        [':id_pilotes' => $pilotRecordId, ':id_entreprise' => $entrepriseId]
    );

    if ($existing) {
        return db_execute(
            'UPDATE evaluation
             SET note = :note, commentaire = :commentaire, date_publication = CURDATE()
             WHERE id_evaluation = :id_evaluation',
            [':note' => $note, ':commentaire' => $commentaire, ':id_evaluation' => $existing['id_evaluation']]
        );
    }

    return db_execute(
        'INSERT INTO evaluation (note, commentaire, date_publication, id_pilotes, id_entreprise)
         VALUES (:note, :commentaire, CURDATE(), :id_pilotes, :id_entreprise)',
        [':note' => $note, ':commentaire' => $commentaire, ':id_pilotes' => $pilotRecordId, ':id_entreprise' => $entrepriseId]
    );
}

function get_student_count(): int
{
    $row = db_fetch_one('SELECT COUNT(*) AS total FROM etudiant');

    return (int) ($row['total'] ?? 0);
}

function normalize_offer_row(array $row): array
{
    $titre = (string) ($row['titre'] ?? '');
    $contrat = (string) ($row['contrat'] ?? '');

    if ($contrat === '') {
        $contrat = stripos($titre, 'alternance') !== false ? 'alternance' : 'stage';
    }

    return [
        'id' => (int) $row['id_offre'],
        'entreprise_id' => (int) $row['id_entreprise'],
        'titre' => $titre,
        'description' => (string) ($row['description'] ?? ''),
        'contrat' => $contrat,
        'statut' => ((int) ($row['statut'] ?? 0)) === 1 ? 'active' : 'inactive',
        'date_creation' => (string) ($row['date_creation'] ?? ($row['date_publication'] ?? '')),
        'date_publication' => (string) ($row['date_publication'] ?? ''),
        'localisation' => (string) ($row['localisation'] ?? ''),
        'remuneration' => (string) ($row['remuneration'] ?? ''),
        'niveau_etude' => (string) ($row['niveau_etude'] ?? ''),
    ];
}

function charger_offres(): array
{
    $rows = db_fetch_all(
        'SELECT o.id_offre,
                o.description,
                o.remuneration,
                o.niveau_etude,
                o.date_publication,
                o.date_creation,
                o.statut,
                o.titre,
                o.localisation,
                o.contrat,
                proposer.id_entreprise
         FROM offre o
         INNER JOIN proposer ON proposer.id_offre = o.id_offre
         ORDER BY o.id_offre ASC'
    );

    return array_map('normalize_offer_row', $rows);
}

function get_offre_by_id(int $offreId): ?array
{
    $row = db_fetch_one(
        'SELECT o.id_offre,
                o.description,
                o.remuneration,
                o.niveau_etude,
                o.date_publication,
                o.date_creation,
                o.statut,
                o.titre,
                o.localisation,
                o.contrat,
                proposer.id_entreprise
         FROM offre o
         INNER JOIN proposer ON proposer.id_offre = o.id_offre
         WHERE o.id_offre = :id_offre',
        [':id_offre' => $offreId]
    );

    return $row ? normalize_offer_row($row) : null;
}

function get_company_offres(int $entrepriseId, bool $includeInactive = true): array
{
    $sql = 'SELECT o.id_offre,
                   o.description,
                   o.remuneration,
                   o.niveau_etude,
                   o.date_publication,
                   o.date_creation,
                   o.statut,
                   o.titre,
                   o.localisation,
                   o.contrat,
                   proposer.id_entreprise
            FROM offre o
            INNER JOIN proposer ON proposer.id_offre = o.id_offre
            WHERE proposer.id_entreprise = :id_entreprise';

    if (!$includeInactive) {
        $sql .= ' AND o.statut = 1';
    }

    $sql .= ' ORDER BY o.id_offre ASC';

    $rows = db_fetch_all($sql, [':id_entreprise' => $entrepriseId]);

    return array_map('normalize_offer_row', $rows);
}

function creer_offre(int $entrepriseId, string $titre, string $description = '', string $contrat = 'stage', string $remuneration = '', string $niveauEtude = ''): int
{
    $entreprises = get_entreprises_map();
    $localisation = $entreprises[$entrepriseId]['ville'] ?? '';

    $database = db();
    $database->beginTransaction();

    try {
        db_execute(
            'INSERT INTO offre (description, remuneration, niveau_etude, date_publication, date_creation, statut, titre, localisation, contrat)
             VALUES (:description, :remuneration, :niveau_etude, CURDATE(), NOW(), 1, :titre, :localisation, :contrat)',
            [':description' => $description, ':remuneration' => $remuneration, ':niveau_etude' => $niveauEtude, ':titre' => $titre, ':localisation' => $localisation, ':contrat' => $contrat]
        );

        $offreId = (int) $database->lastInsertId();

        db_execute('INSERT INTO proposer (id_offre, id_entreprise) VALUES (:id_offre, :id_entreprise)', [':id_offre' => $offreId, ':id_entreprise' => $entrepriseId]);

        $database->commit();

        return $offreId;
    } catch (Throwable $exception) {
        if ($database->inTransaction()) {
            $database->rollBack();
        }

        return 0;
    }
}

function modifier_offre(int $offreId, int $entrepriseId, string $titre, string $description = '', string $contrat = 'stage', string $remuneration = '', string $niveauEtude = ''): bool
{
    $exists = db_fetch_one(
        'SELECT 1
         FROM proposer
         WHERE id_offre = :id_offre AND id_entreprise = :id_entreprise',
        [':id_offre' => $offreId, ':id_entreprise' => $entrepriseId]
    );

    if (!$exists) {
        return false;
    }

    return db_execute(
        'UPDATE offre SET titre = :titre, description = :description, contrat = :contrat, remuneration = :remuneration, niveau_etude = :niveau_etude WHERE id_offre = :id_offre',
        [':titre' => $titre, ':description' => $description, ':contrat' => $contrat, ':remuneration' => $remuneration, ':niveau_etude' => $niveauEtude, ':id_offre' => $offreId]
    );
}

function changer_statut_offre(int $offreId, int $entrepriseId): ?string
{
    $row = db_fetch_one(
        'SELECT o.statut
         FROM offre o
         INNER JOIN proposer ON proposer.id_offre = o.id_offre
         WHERE o.id_offre = :id_offre AND proposer.id_entreprise = :id_entreprise',
        [':id_offre' => $offreId, ':id_entreprise' => $entrepriseId]
    );

    if (!$row) {
        return null;
    }

    $newStatus = ((int) $row['statut']) === 1 ? 0 : 1;
    db_execute('UPDATE offre SET statut = :statut WHERE id_offre = :id_offre', [':statut' => $newStatus, ':id_offre' => $offreId]);

    return $newStatus === 1 ? 'active' : 'inactive';
}

function charger_candidatures(): array
{
    $rows = db_fetch_all(
        'SELECT e.id_utilisateur,
                tc.id_offre,
                tc.date_candidature,
                tc.cv,
                tc.lettre_motivation
         FROM tableau_candidatures tc
         INNER JOIN etudiant e ON e.id_etudiant = tc.id_etudiant
         ORDER BY tc.date_candidature DESC, tc.id_tableau_candidatures DESC'
    );

    $candidatures = [];

    foreach ($rows as $row) {
        $userId = (string) $row['id_utilisateur'];
        $candidatures[$userId] ??= [];
        $candidatures[$userId][] = [
            'offre_id' => (int) $row['id_offre'],
            'date' => (string) $row['date_candidature'],
            'cv' => (string) ($row['cv'] ?? ''),
            'lm' => (string) ($row['lettre_motivation'] ?? ''),
        ];
    }

    return $candidatures;
}

function get_candidatures_utilisateur(int $utilisateurId): array
{
    $studentId = find_student_record_id_by_user_id($utilisateurId);

    if ($studentId === null) {
        return [];
    }

    $rows = db_fetch_all(
        'SELECT id_offre, date_candidature, cv, lettre_motivation
         FROM tableau_candidatures
         WHERE id_etudiant = :id_etudiant
         ORDER BY date_candidature DESC, id_tableau_candidatures DESC',
        [':id_etudiant' => $studentId]
    );

    return array_map(function (array $row): array {
        return [
            'offre_id' => (int) $row['id_offre'],
            'date' => (string) $row['date_candidature'],
            'cv' => (string) ($row['cv'] ?? ''),
            'lm' => (string) ($row['lettre_motivation'] ?? ''),
        ];
    }, $rows);
}

function get_candidatures_pour_offre(int $offreId, int $entrepriseId): array
{
    $offer = get_offre_by_id($offreId);

    if (!$offer || $offer['entreprise_id'] !== $entrepriseId) {
        return [];
    }

    $rows = db_fetch_all(
        'SELECT e.id_utilisateur,
                tc.cv,
                tc.lettre_motivation,
                tc.date_candidature
         FROM tableau_candidatures tc
         INNER JOIN etudiant e ON e.id_etudiant = tc.id_etudiant
         WHERE tc.id_offre = :id_offre
         ORDER BY tc.date_candidature DESC, tc.id_tableau_candidatures DESC',
        [':id_offre' => $offreId]
    );

    return array_map(function (array $row): array {
        return [
            'user_id' => (int) $row['id_utilisateur'],
            'cv' => (string) ($row['cv'] ?? ''),
            'lm' => (string) ($row['lettre_motivation'] ?? ''),
            'date' => (string) ($row['date_candidature'] ?? ''),
        ];
    }, $rows);
}

function ajouter_candidature(int $utilisateurId, int $offreId, string $cvChemin, ?string $lmChemin = null): bool
{
    $studentId = find_student_record_id_by_user_id($utilisateurId);

    if ($studentId === null) {
        return false;
    }

    $existing = db_fetch_one(
        'SELECT id_tableau_candidatures
         FROM tableau_candidatures
         WHERE id_etudiant = :id_etudiant AND id_offre = :id_offre',
        [':id_etudiant' => $studentId, ':id_offre' => $offreId]
    );

    if ($existing) {
        return db_execute(
            'UPDATE tableau_candidatures
             SET cv = :cv, lettre_motivation = :lettre_motivation, date_candidature = CURDATE()
             WHERE id_tableau_candidatures = :id_tableau_candidatures',
            [':cv' => $cvChemin, ':lettre_motivation' => $lmChemin, ':id_tableau_candidatures' => $existing['id_tableau_candidatures']]
        );
    }

    return db_execute(
        'INSERT INTO tableau_candidatures (cv, lettre_motivation, date_candidature, id_etudiant, id_offre)
         VALUES (:cv, :lettre_motivation, CURDATE(), :id_etudiant, :id_offre)',
        [':cv' => $cvChemin, ':lettre_motivation' => $lmChemin, ':id_etudiant' => $studentId, ':id_offre' => $offreId]
    );
}

function supprimer_candidature(int $utilisateurId, int $offreId): bool
{
    $studentId = find_student_record_id_by_user_id($utilisateurId);

    if ($studentId === null) {
        return false;
    }

    return db_execute(
        'DELETE FROM tableau_candidatures WHERE id_etudiant = :id_etudiant AND id_offre = :id_offre',
        [':id_etudiant' => $studentId, ':id_offre' => $offreId]
    );
}

function charger_favoris(): array
{
    $rows = db_fetch_all(
        'SELECT e.id_utilisateur, w.id_offre
         FROM wishlist w
         INNER JOIN etudiant e ON e.id_etudiant = w.id_etudiant
         ORDER BY w.id_wishlist ASC'
    );

    $favoris = [];

    foreach ($rows as $row) {
        $userId = (string) $row['id_utilisateur'];
        $favoris[$userId] ??= [];
        $favoris[$userId][] = (int) $row['id_offre'];
    }

    return $favoris;
}

function get_favoris_utilisateur(int $utilisateurId): array
{
    $studentId = find_student_record_id_by_user_id($utilisateurId);

    if ($studentId === null) {
        return [];
    }

    $rows = db_fetch_all('SELECT id_offre FROM wishlist WHERE id_etudiant = :id_etudiant ORDER BY id_wishlist ASC', [':id_etudiant' => $studentId]);

    return array_map(static fn(array $row): int => (int) $row['id_offre'], $rows);
}

function ajouter_favori(int $utilisateurId, int $offreId): bool
{
    $studentId = find_student_record_id_by_user_id($utilisateurId);

    if ($studentId === null) {
        return false;
    }

    $existing = db_fetch_one('SELECT id_wishlist FROM wishlist WHERE id_etudiant = :id_etudiant AND id_offre = :id_offre', [':id_etudiant' => $studentId, ':id_offre' => $offreId]);

    if ($existing) {
        return true;
    }

    return db_execute('INSERT INTO wishlist (id_etudiant, id_offre) VALUES (:id_etudiant, :id_offre)', [':id_etudiant' => $studentId, ':id_offre' => $offreId]);
}

function supprimer_favori(int $utilisateurId, int $offreId): bool
{
    $studentId = find_student_record_id_by_user_id($utilisateurId);

    if ($studentId === null) {
        return false;
    }

    return db_execute('DELETE FROM wishlist WHERE id_etudiant = :id_etudiant AND id_offre = :id_offre', [':id_etudiant' => $studentId, ':id_offre' => $offreId]);
}

function delete_student_account(int $userId): bool
{
    $studentId = find_student_record_id_by_user_id($userId);

    if ($studentId === null) {
        return false;
    }

    $database = db();
    $database->beginTransaction();

    try {
        db_execute('DELETE FROM wishlist WHERE id_etudiant = :id_etudiant', [':id_etudiant' => $studentId]);
        db_execute('DELETE FROM tableau_candidatures WHERE id_etudiant = :id_etudiant', [':id_etudiant' => $studentId]);
        db_execute('DELETE FROM etudiant WHERE id_etudiant = :id_etudiant', [':id_etudiant' => $studentId]);
        db_execute('DELETE FROM utilisateur WHERE id_utilisateur = :id_utilisateur', [':id_utilisateur' => $userId]);

        $database->commit();

        return true;
    } catch (Throwable $exception) {
        if ($database->inTransaction()) {
            $database->rollBack();
        }

        return false;
    }
}

function delete_student_by_pilot(int $pilotUserId, int $studentUserId): bool
{
    $pilotRecordId = find_pilot_record_id_by_user_id($pilotUserId);
    $studentRecordId = find_student_record_id_by_user_id($studentUserId);

    if ($pilotRecordId === null || $studentRecordId === null) {
        return false;
    }

    $row = db_fetch_one(
        'SELECT 1
         FROM etudiant
         WHERE id_etudiant = :id_etudiant AND id_pilote = :id_pilote',
        [':id_etudiant' => $studentRecordId, ':id_pilote' => $pilotRecordId]
    );

    if (!$row) {
        return false;
    }

    return delete_student_account($studentUserId);
}

function delete_company_account(int $userId): bool
{
    $company = get_user_by_id($userId);

    if (!$company || $company['role'] !== 'entreprise') {
        return false;
    }

    $entrepriseId = (int) $company['entreprise_id'];
    $offerIds = array_map(static fn(array $offre): int => $offre['id'], get_company_offres($entrepriseId, true));

    $database = db();
    $database->beginTransaction();

    try {
        db_execute('DELETE FROM evaluation WHERE id_entreprise = :id_entreprise', [':id_entreprise' => $entrepriseId]);

        foreach ($offerIds as $offerId) {
            db_execute('DELETE FROM wishlist WHERE id_offre = :id_offre', [':id_offre' => $offerId]);
            db_execute('DELETE FROM tableau_candidatures WHERE id_offre = :id_offre', [':id_offre' => $offerId]);
            db_execute('DELETE FROM proposer WHERE id_offre = :id_offre', [':id_offre' => $offerId]);
            db_execute('DELETE FROM offre WHERE id_offre = :id_offre', [':id_offre' => $offerId]);
        }

        db_execute('DELETE FROM compte_entreprise WHERE id_entreprise = :id_entreprise', [':id_entreprise' => $entrepriseId]);
        db_execute('DELETE FROM utilisateur WHERE id_utilisateur = :id_utilisateur', [':id_utilisateur' => $userId]);

        $database->commit();

        return true;
    } catch (Throwable $exception) {
        if ($database->inTransaction()) {
            $database->rollBack();
        }

        return false;
    }
}

function delete_pilot_account(int $userId): int|false
{
    $pilotRecordId = find_pilot_record_id_by_user_id($userId);

    if ($pilotRecordId === null) {
        return false;
    }

    $students = get_students_by_pilot_user_id($userId);
    $studentIds = array_map(static fn(array $student): int => $student['id'], $students);

    $database = db();
    $database->beginTransaction();

    try {
        foreach ($studentIds as $studentUserId) {
            $studentRecordId = find_student_record_id_by_user_id($studentUserId);
            if ($studentRecordId !== null) {
                db_execute('DELETE FROM wishlist WHERE id_etudiant = :id_etudiant', [':id_etudiant' => $studentRecordId]);
                db_execute('DELETE FROM tableau_candidatures WHERE id_etudiant = :id_etudiant', [':id_etudiant' => $studentRecordId]);
                db_execute('DELETE FROM etudiant WHERE id_etudiant = :id_etudiant', [':id_etudiant' => $studentRecordId]);
            }
            db_execute('DELETE FROM utilisateur WHERE id_utilisateur = :id_utilisateur', [':id_utilisateur' => $studentUserId]);
        }

        db_execute('DELETE FROM pilotes WHERE id_pilotes = :id_pilotes', [':id_pilotes' => $pilotRecordId]);
        db_execute('DELETE FROM utilisateur WHERE id_utilisateur = :id_utilisateur', [':id_utilisateur' => $userId]);

        $database->commit();

        return count($studentIds);
    } catch (Throwable $exception) {
        if ($database->inTransaction()) {
            $database->rollBack();
        }

        return false;
    }
}

