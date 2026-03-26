<?php
session_start();
require 'data_helpers.php';
require 'pagination.php';

$user = $_SESSION['user'] ?? null;
if (!$user) {
    header('Location: index.php');
    exit;
}

$users = json_decode(file_get_contents(__DIR__ . '/users.json'), true);
$allUsersById = [];
foreach ($users as $u) {
    $allUsersById[$u['id']] = $u;
}

$currentUserId = (int)$user['id'];
$role = $user['role'];

// Pilote: vue d'un élève sélectionné
$selectedEleveId = null;
if ($role === 'pilote' && isset($_GET['eleve_id'])) {
    $candidate = (int)$_GET['eleve_id'];
    if (isset($allUsersById[$candidate]) && $allUsersById[$candidate]['role'] === 'eleve') {
        $isLinkedEleve = in_array($candidate, $allUsersById[$currentUserId]['eleves'] ?? [], true);
        if ($isLinkedEleve) {
            $selectedEleveId = $candidate;
        }
    }
}

// Entreprise: offre sélectionnée
$selectedOffreId = null;
if ($role === 'entreprise' && isset($_GET['offre_id'])) {
    $selectedOffreId = (int)$_GET['offre_id'];
}

function findOffer(array $offres, int $id) {
    foreach ($offres as $offre) {
        if ($offre['id'] === $id) {
            return $offre;
        }
    }
    return null;
}

function downloadLink(?string $path): string {
    if (!$path) return '#';
    return 'download.php?f=' . urlencode(base64_encode($path));
}

// Actions POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'supprimer_candidature') {
        $isPilote = $role === 'pilote';
        $ownerId = $isPilote && isset($_POST['eleve_id']) ? (int)$_POST['eleve_id'] : $currentUserId;
        if ($ownerId === $currentUserId || ($isPilote && in_array($ownerId, $allUsersById[$currentUserId]['eleves'] ?? [], true))) {
            $offreId = (int)($_POST['offre_id'] ?? 0);
            supprimer_candidature($ownerId, $offreId);
            $_SESSION['success'] = 'Candidature supprimée.';
        }
    } elseif ($action === 'supprimer_favori') {
        $ownerId = $currentUserId;
        $offreId = (int)($_POST['offre_id'] ?? 0);
        supprimer_favori($ownerId, $offreId);
        $_SESSION['success'] = 'Offre retirée des favoris.';
    } elseif ($action === 'ajouter_favori') {
        $ownerId = $currentUserId;
        $offreId = (int)($_POST['offre_id'] ?? 0);
        ajouter_favori($ownerId, $offreId);
        $_SESSION['success'] = 'Offre ajoutée dans les favoris.';
    } elseif ($action === 'changer_statut_offre') {
        if ($role === 'entreprise') {
            $offreId = (int)($_POST['offre_id'] ?? 0);
            $nouvelStatut = changer_statut_offre($offreId, $user['entreprise_id'] ?? $currentUserId);
            if ($nouvelStatut !== null) {
                $_SESSION['success'] = 'Statut de l\'offre changé : ' . ($nouvelStatut === 'active' ? 'Active' : 'Inactive');
            } else {
                $_SESSION['error'] = 'Erreur : offre introuvable ou accès non autorisé.';
            }
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

$targetEleveId = $currentUserId;
if ($role === 'pilote' && $selectedEleveId) {
    $targetEleveId = $selectedEleveId;
}

$applications = get_candidatures_utilisateur($targetEleveId);
$wishlist = get_favoris_utilisateur($targetEleveId);

$pilotEleves = [];
if ($role === 'pilote') {
    $pilotEleves = array_filter($users, function($u) use ($user) {
        return $u['role'] === 'eleve' && isset($u['pilote_id']) && $u['pilote_id'] === $user['id'];
    });
}

$companyOffers = [];
if ($role === 'entreprise') {
    $companyId = $user['entreprise_id'] ?? $currentUserId;
    $companyOffers = array_filter($offres, function($o) use ($companyId) {
        return $o['entreprise_id'] === $companyId;
    });
}

$selectedOffre = null;
$selectedAppList = [];
if ($role === 'entreprise' && $selectedOffreId) {
    $selectedOffre = findOffer($offres, $selectedOffreId);
    if ($selectedOffre && $selectedOffre['entreprise_id'] === ($user['entreprise_id'] ?? $currentUserId)) {
        $allApps = charger_candidatures();
        foreach ($allApps as $teacherId => $studentApps) {
            foreach ($studentApps as $a) {
                if ($a['offre_id'] === $selectedOffreId) {
                    $selectedAppList[] = array_merge($a, ['user_id' => (int)$teacherId]);
                }
            }
        }
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
<link rel="stylesheet" href="style.css?v=22">
</head>
<body>
<?php include 'header.php'; ?>

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
            <h2>Vue élève : <?= htmlspecialchars($allUsersById[$targetEleveId]['prenom'] . ' ' . $allUsersById[$targetEleveId]['nom']) ?></h2>
        <?php endif; ?>
    <?php endif; ?>

    <?php if ($role === 'eleve' || ($role === 'pilote' && $selectedEleveId)): ?>
        <div class="dashboard-grid" style="display:grid;grid-template-columns:2.2fr 0.8fr;gap:28px;align-items:start;">
            <div class="card" style="min-height:420px;">
                <h3>Candidatures</h3>
                <?php if (empty($applications)): ?>
                    <p>Aucune candidature déposée.</p>
                <?php else: ?>
                    <div class="candidatures-list">
                    <?php foreach ($applications as $i => $app):
                        $offre = findOffer($offres, $app['offre_id']);
                        $entreprise = $offre ? $entreprises[$offre['entreprise_id']] ?? null : null;
                        $bg = ($i % 2 === 0) ? '#f0f0f0' : '#fafbfc';
                    ?>
                        <div class="candidature-bloc" style="background:<?= $bg ?>;padding:22px 28px 18px 28px;border-radius:14px;margin-bottom:18px;box-shadow:0 2px 8px rgba(13,12,110,0.04);display:flex;flex-direction:column;min-width:0;position:relative;">
                            <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:18px;">
                                <div style="flex:2;min-width:180px;">
                                    <div style="font-weight:600;font-size:1.1em;">Offre : <?= htmlspecialchars($offre['titre'] ?? 'Offre supprimée') ?></div>
                                    <div style="color:#555;">Entreprise : <?= htmlspecialchars($entreprise['nom'] ?? 'Inconnu') ?></div>
                                    <div style="color:#888;">Date : <?= htmlspecialchars($app['date']) ?></div>
                                </div>
                                <div style="flex:1;min-width:120px;display:flex;gap:10px;align-items:center;justify-content:flex-end;">
                                    <a class="button btn-sm" href="<?= downloadLink($app['cv']) ?>">CV</a>
                                    <a class="button btn-sm" href="<?= downloadLink($app['lm']) ?>">LM</a>
                                </div>
                            </div>
                            <div style="display:flex;justify-content:flex-end;align-items:center;margin-top:18px;">
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="supprimer_candidature">
                                    <input type="hidden" name="offre_id" value="<?= $app['offre_id'] ?>">
                                    <?php if ($role === 'pilote' && $selectedEleveId): ?>
                                        <input type="hidden" name="eleve_id" value="<?= $selectedEleveId ?>">
                                    <?php endif; ?>
                                    <button type="submit" class="button btn-sm" style="background:#b12a2a;">Supprimer</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="card" style="padding:12px 10px;max-height:420px;overflow-y:auto;">
                <h3 style="margin-bottom:14px;">Wishlist</h3>
                <?php if (empty($wishlist)): ?>
                    <p>Liste vide.</p>
                <?php else: ?>
                    <div class="wishlist-list">
                    <?php foreach ($wishlist as $i => $offreId):
                        $offre = findOffer($offres, $offreId);
                        $entreprise = $offre ? $entreprises[$offre['entreprise_id']] ?? null : null;
                        $bg = ($i % 2 === 0) ? '#f7f8fa' : '#e9e9ee';
                    ?>
                        <div class="wishlist-bloc" style="background:<?= $bg ?>;border-radius:10px;margin-bottom:10px;padding:13px 16px 13px 16px;display:flex;align-items:center;justify-content:space-between;gap:10px;">
                            <div style="flex:1;min-width:0;">
                                <div style="font-weight:600;font-size:1.05em;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                    <?= htmlspecialchars($offre['titre'] ?? 'Offre supprimée') ?>
                                </div>
                                <div style="color:#555;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                    <?= htmlspecialchars($entreprise['nom'] ?? 'Inconnu') ?>
                                </div>
                            </div>
                            <form method="POST" style="display:inline;margin-left:10px;">
                                <input type="hidden" name="action" value="supprimer_favori">
                                <input type="hidden" name="offre_id" value="<?= $offreId ?>">
                                <button type="submit" class="button btn-sm" style="background:#b12a2a;">Supprimer</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($role === 'entreprise'): ?>
        <div class="dashboard-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
            <div class="card">
                <h3>Mes offres</h3>
                <?php if (empty($companyOffers)): ?>
                    <p>Aucune offre pour le moment.</p>
                <?php else: ?>
                    <div class="offers-list">
                    <?php foreach ($companyOffers as $offre): ?>
                        <div class="offer-item">
                            <span class="offer-title"><?= htmlspecialchars($offre['titre']) ?> <span class="status-badge status-<?= $offre['statut'] ?? 'active' ?>">(<?= ($offre['statut'] ?? 'active') === 'active' ? 'Active' : 'Inactive' ?>)</span></span>
                            <div class="offer-actions">
                                <a href="dashboard.php?offre_id=<?= $offre['id'] ?>" class="button">Voir candidats</a>
                                <form method="POST" style="display:inline;">
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

            <div class="card">
                <h3>Actions</h3>
                <a href="creer_offre.php" class="button">Créer une nouvelle offre d'emploi</a>
            </div>
        </div>

        <?php if ($selectedOffre): ?>
            <h3>Candidats pour <?= htmlspecialchars($selectedOffre['titre']) ?></h3>
            <?php if (empty($selectedAppList)): ?>
                <p>Aucun candidat pour cette offre.</p>
            <?php else: ?>
                <table style="width:100%;border-collapse:collapse;">
                    <thead>
                        <tr><th>Élève</th><th>CV</th><th>LM</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($selectedAppList as $app):
                        $eleve = $allUsersById[$app['user_id']] ?? null;
                    ?>
                        <tr>
                            <td><?= htmlspecialchars(($eleve['prenom'] ?? '') . ' ' . ($eleve['nom'] ?? '')) ?></td>
                            <td><a class="button" href="<?= downloadLink($app['cv']) ?>">Télécharger CV</a></td>
                            <td><a class="button" href="<?= downloadLink($app['lm']) ?>">Télécharger LM</a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        <?php endif; ?>
    <?php endif; ?>

</section>

<?php include 'footer.php'; ?>
</body>
</html>