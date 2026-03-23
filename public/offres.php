<?php
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

$entreprise = $entreprises[$offreTrouvee['entreprise_id']];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($offreTrouvee['titre']) ?></title>
<link rel="stylesheet" href="/style.css">
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

<a class="postuler-btn" href="postuler.php?id=<?= $offreTrouvee['id'] ?>">
Postuler à cette offre
</a>

</section>

<?php include 'footer.php'; ?>

</body>
</html>