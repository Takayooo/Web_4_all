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
<link rel="stylesheet" href="/style.css?v=6">
</head>

<body>

<?php include 'header.php'; ?>

<section class="container">

<h1><?= htmlspecialchars($offreTrouvee['titre']) ?></h1>

<div class="entreprise-header">

<p><strong>Entreprise :</strong> <?= htmlspecialchars($entreprise['nom']) ?></p>
<p><strong>Note :</strong> ⭐ <?= $entreprise['note'] ?></p>
<p><strong>Secteur :</strong> <?= $entreprise['secteur'] ?></p>
<p><strong>Ville :</strong> <?= $entreprise['ville'] ?></p>

</div>

<?php if ($user && $user['role'] === 'eleve'): ?>
    <?php if ($hasApplied): ?>
        <p>Vous avez déjà postulé à cette offre.</p>
    <?php else: ?>
        <a class="postuler-btn" href="postuler.php?id=<?= $offreTrouvee['id'] ?>">Postuler à cette offre</a>
    <?php endif; ?>

    <form method="POST" action="dashboard.php" style="margin-top: 15px;">
        <input type="hidden" name="action" value="<?= $inWishlist ? 'supprimer_favori' : 'ajouter_favori' ?>">
        <input type="hidden" name="offre_id" value="<?= $offreTrouvee['id'] ?>">
        <button type="submit" class="postuler-btn" style="background: #0d0c6e;">
            <?= $inWishlist ? 'Retirer de mes favoris' : 'Ajouter à mes favoris' ?>
        </button>
    </form>
<?php endif; ?>

</section>

<?php include 'footer.php'; ?>
<script src="js/loginmodal.js"></script>

</body>
</html>