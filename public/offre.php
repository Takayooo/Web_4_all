<?php
require 'data_helpers.php';
require 'pagination.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    die("Offre introuvable");
}

// trouver l'offre
$offreTrouvee = null;

foreach ($offres as $offre) {
    if ($offre['id'] == $id) {
        $offreTrouvee = $offre;
        break;
    }
}

if (!$offreTrouvee) {
    die("Offre introuvable");
}

if (!isset($offreTrouvee['statut']) || $offreTrouvee['statut'] !== 'active') {
    die("Offre inactive");
}

$entreprise = $entreprises[$offreTrouvee['entreprise_id']];

session_start();

$user = $_SESSION['user'] ?? null;
$userId = $user['id'] ?? null;
$hasApplied = false;
$inWishlist = false;
if ($user && $user['role'] === 'eleve' && $userId) {
    $apps = get_candidatures_utilisateur($userId);
    foreach ($apps as $app) {
        if ($app['offre_id'] === $offreTrouvee['id']) {
            $hasApplied = true;
            break;
        }
    }
    $inWishlist = in_array($offreTrouvee['id'], get_favoris_utilisateur($userId), true);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($offreTrouvee['titre']) ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="Offre <?= htmlspecialchars($offreTrouvee['titre'], ENT_QUOTES, 'UTF-8') ?> chez <?= htmlspecialchars($entreprise['nom'], ENT_QUOTES, 'UTF-8') ?> sur Web4All.">
<link rel="stylesheet" href="/style.css?v=6">
</head>

<body>

<?php include 'header.php'; ?>

<main>


<section class="offre-layout container">
    <h1 class="offre-titre"><?= htmlspecialchars($offreTrouvee['titre']) ?></h1>
    <div class="offre-content-grid">
        <div class="offre-description-bloc">
            <div class="offre-meta">
                <?php if (!empty($offreTrouvee['remuneration'])): ?>
                    <span class="offre-meta-item"><span class="offre-meta-label">Rémunération</span> <?= htmlspecialchars($offreTrouvee['remuneration']) ?></span>
                <?php endif; ?>
                <?php if (!empty($offreTrouvee['niveau_etude'])): ?>
                    <span class="offre-meta-item"><span class="offre-meta-label">Niveau d'études</span> <?= htmlspecialchars($offreTrouvee['niveau_etude']) ?></span>
                <?php endif; ?>
            </div>
            <?php if (!empty($offreTrouvee['description'])): ?>
                <div class="offre-description">
                    <?= nl2br(htmlspecialchars($offreTrouvee['description'])) ?>
                </div>
            <?php else: ?>
                <div class="offre-description vide">Aucune description disponible pour cette offre.</div>
            <?php endif; ?>
        </div>
        <aside class="offre-aside">
            <div class="offre-card-entreprise">
                <div class="offre-card-nom">Entreprise : <strong><?= htmlspecialchars($entreprise['nom']) ?></strong></div>
                <div class="offre-card-note">Note : <span class="star">⭐</span> <?= $entreprise['note'] ?></div>
                <div class="offre-card-secteur">Secteur : <?= $entreprise['secteur'] ?></div>
                <div class="offre-card-ville">Ville : <?= $entreprise['ville'] ?></div>
                <?php if (!empty($entreprise['description'])): ?>
                    <div class="offre-card-description">Description : <br><?= nl2br(htmlspecialchars($entreprise['description'])) ?></div>
                <?php endif; ?>
                <div class="offre-card-actions">
                    <?php if ($user && $user['role'] === 'eleve'): ?>
                        <?php if ($hasApplied): ?>
                            <p class="offre-applied">Vous avez déjà postulé à cette offre.</p>
                        <?php else: ?>
                            <a class="postuler-btn" href="postuler.php?id=<?= $offreTrouvee['id'] ?>">Postuler à cette offre</a>
                        <?php endif; ?>
                        <form method="POST" action="dashboard.php" class="offre-favori-form">
                            <input type="hidden" name="action" value="<?= $inWishlist ? 'supprimer_favori' : 'ajouter_favori' ?>">
                            <input type="hidden" name="offre_id" value="<?= $offreTrouvee['id'] ?>">
                            <button type="submit" class="favori-btn">
                                <?= $inWishlist ? 'Retirer de mes favoris' : 'Ajouter à mes favoris ' ?>
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </aside>
    </div>
</section>

</main>

<?php include 'footer.php'; ?>
<script src="js/loginmodal.js"></script>

</body>
</html>