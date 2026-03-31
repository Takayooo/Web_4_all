<?php
session_start();
require 'data_helpers.php';
require 'pagination.php';

$current_page = 'dashboard';

$user = $_SESSION['user'] ?? null;
if (!$user) {
    header('Location: index.php');
    exit;
}

$currentUser = get_user_by_id((int) $user['id']);
if (!$currentUser) {
    session_destroy();
    header('Location: index.php');
    exit;
}

$_SESSION['user'] = $currentUser;
$user = $currentUser;
$allUsersById = get_all_users_indexed();
$currentUserId = (int) $user['id'];
$role = $user['role'];
$companyId = (int) ($user['entreprise_id'] ?? 0);

$pilotEleves = $role === 'pilote' ? array_values(get_students_by_pilot_user_id($currentUserId)) : [];
$pilotEleveIds = array_map(static fn(array $eleve): int => (int) $eleve['id'], $pilotEleves);

$selectedEleveId = null;
if ($role === 'pilote' && isset($_GET['eleve_id'])) {
    $candidate = (int) $_GET['eleve_id'];
    if (in_array($candidate, $pilotEleveIds, true)) {
        $selectedEleveId = $candidate;
    }
}

$selectedOffreId = null;
if ($role === 'entreprise' && isset($_GET['offre_id'])) {
    $selectedOffreId = (int) $_GET['offre_id'];
}

function findOffer(array $offres, int $id)
{
    foreach ($offres as $offre) {
        if ($offre['id'] === $id) {
            return $offre;
        }
    }

    return null;
}

function downloadLink(?string $path): string
{
    if (!$path) {
        return '#';
    }

    return 'download.php?f=' . urlencode(base64_encode($path));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'supprimer_candidature') {
        $isPilote = $role === 'pilote';
        $ownerId = $isPilote && isset($_POST['eleve_id']) ? (int) $_POST['eleve_id'] : $currentUserId;
        if ($ownerId === $currentUserId || ($isPilote && in_array($ownerId, $pilotEleveIds, true))) {
            $offreId = (int) ($_POST['offre_id'] ?? 0);
            supprimer_candidature($ownerId, $offreId);
            $_SESSION['success'] = 'Candidature supprimée.';
        }
    } elseif ($action === 'supprimer_favori') {
        if ($role === 'eleve') {
            $offreId = (int) ($_POST['offre_id'] ?? 0);
            supprimer_favori($currentUserId, $offreId);
            $_SESSION['success'] = 'Offre retirée des favoris.';
        }
    } elseif ($action === 'ajouter_favori') {
        if ($role === 'eleve') {
            $offreId = (int) ($_POST['offre_id'] ?? 0);
            ajouter_favori($currentUserId, $offreId);
            $_SESSION['success'] = 'Offre ajoutée dans les favoris.';
        }
    } elseif ($action === 'changer_statut_offre' && $role === 'entreprise') {
        $offreId = (int) ($_POST['offre_id'] ?? 0);
        $nouvelStatut = changer_statut_offre($offreId, $companyId);
        if ($nouvelStatut !== null) {
            $_SESSION['success'] = 'Statut de l\'offre changé : ' . ($nouvelStatut === 'active' ? 'Active' : 'Inactive');
        } else {
            $_SESSION['error'] = 'Erreur : offre introuvable ou accès non autorisé.';
        }
    }

    $redirect = 'dashboard.php';
    if ($role === 'pilote' && $selectedEleveId) {
        $redirect .= '?eleve_id=' . $selectedEleveId;
    } elseif ($role === 'entreprise' && $selectedOffreId) {
        $redirect .= '?offre_id=' . $selectedOffreId;
    }

    header('Location: ' . $redirect);
    exit;
}

$targetEleveId = ($role === 'pilote' && $selectedEleveId) ? $selectedEleveId : $currentUserId;
$applications = get_candidatures_utilisateur($targetEleveId);
$wishlist = get_favoris_utilisateur($targetEleveId);
$companyOffers = $role === 'entreprise' ? get_company_offres($companyId, true) : [];

$selectedOffre = null;
$selectedAppList = [];
if ($role === 'entreprise' && $selectedOffreId) {
    $selectedOffre = get_offre_by_id($selectedOffreId);
    if ($selectedOffre && $selectedOffre['entreprise_id'] === $companyId) {
        $selectedAppList = get_candidatures_pour_offre($selectedOffreId, $companyId);
    } else {
        $selectedOffre = null;
    }
}

$succesMessage = $_SESSION['success'] ?? null;
unset($_SESSION['success']);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Dashboard</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="Tableau de bord Web4All : candidatures, wishlist et gestion des offres selon votre rôle.">
<link rel="stylesheet" href="style.css?v=24">
</head>
<body>
<?php include 'header.php'; ?>

<main>

<section class="container">
    <h1>Tableau de bord</h1>

    <?php if ($succesMessage): ?>
        <div class="success-message"><?= htmlspecialchars($succesMessage, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <?php if ($role === 'pilote'): ?>
        <h2>Mes élèves</h2>
        <?php if (empty($pilotEleves)): ?>
            <p>Aucun élève rattaché pour le moment.</p>
        <?php else: ?>
            <div class="eleves-list">
                <?php foreach ($pilotEleves as $eleve): ?>
                    <div class="eleve-card">
                        <strong><?= htmlspecialchars($eleve['prenom'] . ' ' . $eleve['nom']) ?></strong><br>
                        <a href="dashboard.php?eleve_id=<?= $eleve['id'] ?>">Voir le tableau de bord de l'élève</a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($selectedEleveId): ?>
            <hr>
            <h2>Vue élève : <?= htmlspecialchars(trim(($allUsersById[$targetEleveId]['prenom'] ?? '') . ' ' . ($allUsersById[$targetEleveId]['nom'] ?? ''))) ?></h2>
        <?php endif; ?>
    <?php endif; ?>

    <?php if ($role === 'eleve' || ($role === 'pilote' && $selectedEleveId)): ?>
        <div class="dashboard-grid dashboard-grid-asym">
            <div class="card dashboard-main-card">
                <h3>Candidatures</h3>
                <?php if (empty($applications)): ?>
                    <p>Aucune candidature déposée.</p>
                <?php else: ?>
                    <div class="candidatures-list">
                    <?php foreach ($applications as $app):
                        $offre = get_offre_by_id($app['offre_id']);
                        $entreprise = $offre ? $entreprises[$offre['entreprise_id']] ?? null : null;
                    ?>
                        <div class="candidature-bloc">
                            <?php $isActive = ($offre['statut'] ?? 'active') === 'active'; ?>
                            <?php if ($isActive || $role !== 'eleve'): ?>
                            <a href="offre.php?id=<?= $app['offre_id'] ?>">
                            <?php else: ?>
                            <span class="candidature-inactive">
                            <?php endif; ?>
                                <div class="candidature-info-row">
                                    <div class="candidature-infos">
                                        <div class="titre">Offre : <?= htmlspecialchars($offre['titre'] ?? 'Offre supprimée') ?></div>
                                        <div class="entreprise">Entreprise : <?= htmlspecialchars($entreprise['nom'] ?? 'Inconnu') ?></div>
                                        <div class="date">Date : <?= htmlspecialchars($app['date']) ?></div>
                                    </div>
                                    <div class="candidature-actions-dl">
                                        <a href="<?= downloadLink($app['cv']) ?>" class="button btn-sm"><img src="/assets/telecharger.png" alt="Télécharger" class="menu-icon"> CV</a>
                                        <a href="<?= downloadLink($app['lm']) ?>" class="button btn-sm"><img src="/assets/telecharger.png" alt="Télécharger" class="menu-icon"> LM</a>
                                    </div>
                                </div>
                            <?php if ($isActive || $role !== 'eleve'): ?>
                            </a>
                            <?php else: ?>
                            </span>
                            <?php endif; ?>
                            <div class="candidature-delete">
                                <form method="POST" class="form-inline">
                                    <input type="hidden" name="action" value="supprimer_candidature">
                                    <input type="hidden" name="offre_id" value="<?= $app['offre_id'] ?>">
                                    <?php if ($role === 'pilote' && $selectedEleveId): ?>
                                        <input type="hidden" name="eleve_id" value="<?= $selectedEleveId ?>">
                                    <?php endif; ?>
                                    <button type="submit" class="button btn-sm btn-danger">Supprimer</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="card wishlist-card">
                <h3>Wishlist</h3>
                <?php if (empty($wishlist)): ?>
                    <p>Liste vide.</p>
                <?php else: ?>
                    <div class="wishlist-list">
                    <?php foreach ($wishlist as $offreId):
                        $offre = get_offre_by_id($offreId);
                        $entreprise = $offre ? $entreprises[$offre['entreprise_id']] ?? null : null;
                    ?>
                        <div class="wishlist-bloc">
                            <a href="offre.php?id=<?= $offreId ?>">
                                <div class="titre">
                                    <?= htmlspecialchars($offre['titre'] ?? 'Offre supprimée') ?>
                                </div>
                                <div class="entreprise">
                                    <?= htmlspecialchars($entreprise['nom'] ?? 'Inconnu') ?>
                                </div>
                            </a>
                            <form method="POST" class="form-inline">
                                <input type="hidden" name="action" value="supprimer_favori">
                                <input type="hidden" name="offre_id" value="<?= $offreId ?>">
                                <button type="submit" class="button btn-sm btn-danger">Supprimer</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($role === 'entreprise'): ?>
        <div class="dashboard-grid dashboard-grid-entreprise">
            <div class="card">
                <h3>Actions</h3>
                <a href="creer_offre.php" class="button">Créer une nouvelle offre d'emploi</a>
            </div>

            <div class="card">
                <h3>Mes offres</h3>
                <?php if (empty($companyOffers)): ?>
                    <p>Aucune offre pour le moment.</p>
                <?php else: ?>
                    <div class="offers-list">
                    <?php foreach ($companyOffers as $offre): ?>
                        <div class="offer-item">
                            <span class="offer-title">
                                <?= htmlspecialchars($offre['titre']) ?>
                                <?php if (($offre['statut'] ?? 'active') === 'active'): ?>
                                    <span class="status-badge status-active">Active</span>
                                <?php else: ?>
                                    <span class="status-badge status-inactive">Inactive</span>
                                <?php endif; ?>
                            </span>
                            <div class="offer-actions">
                                <a href="dashboard.php?offre_id=<?= $offre['id'] ?>" class="button">Voir candidats</a>
                                <a href="creer_offre.php?id=<?= $offre['id'] ?>" class="button">Modifier l'offre</a>
                                <form method="POST" class="form-inline">
                                    <input type="hidden" name="action" value="changer_statut_offre">
                                    <input type="hidden" name="offre_id" value="<?= $offre['id'] ?>">
                                    <button type="submit" class="button status-btn">
                                        <?= ($offre['statut'] ?? 'active') === 'active' ? 'Rendre Inactive' : 'Rendre Active' ?>
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($selectedOffre): ?>
            <h3>Candidats pour <?= htmlspecialchars($selectedOffre['titre']) ?></h3>
            <?php if (empty($selectedAppList)): ?>
                <p>Aucun candidat pour cette offre.</p>
            <?php else: ?>
                <div class="candidats-list">
                <?php foreach ($selectedAppList as $i => $app):
                    $eleve = $allUsersById[$app['user_id']] ?? null;
                    $bg = ($i % 2 === 0) ? 'ligne-candidat-bg' : '';
                ?>
                    <div class="ligne-candidat <?= $bg ?>">
                        <div class="candidat-eleve"> <?= htmlspecialchars(trim(($eleve['prenom'] ?? '') . ' ' . ($eleve['nom'] ?? ''))) ?> </div>
                        <div class="candidat-cv">
                            <a href="<?= downloadLink($app['cv']) ?>" class="button btn-sm"><img src="/assets/telecharger.png" alt="Télécharger" class="menu-icon"> CV</a>
                        </div>
                        <div class="candidat-lm">
                            <a href="<?= downloadLink($app['lm']) ?>" class="button btn-sm"><img src="/assets/telecharger.png" alt="Télécharger" class="menu-icon"> LM</a>
                        </div>
                    </div>
                <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    <?php endif; ?>

</section>

</main>

<?php include 'footer.php'; ?>
</body>
</html>
