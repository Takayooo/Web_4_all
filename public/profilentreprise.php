<?php
require 'pagination.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || !isset($entreprises[$id])) {
    die("Entreprise introuvable");
}

$entreprise = $entreprises[$id];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($entreprise['nom']) ?></title>

<!-- 🔥 CSS IMPORTANT -->
<link rel="stylesheet" href="/style.css">

</head>

<body>

<?php include 'header.php'; ?>

<section class="container">

<div class="entreprise-header">

<h1><?= htmlspecialchars($entreprise['nom']) ?></h1>

<div class="entreprise-meta">
⭐ <?= $entreprise['note'] ?> |
<?= $entreprise['secteur'] ?> - <?= $entreprise['ville'] ?>
</div>

<div class="entreprise-description">
<?= htmlspecialchars($entreprise['description']) ?>
</div>

</div>

<h2 class="offres-title">Offres disponibles</h2>

<div class="cards">

<?php foreach ($offres as $offre): ?>

<?php if ($offre['entreprise_id'] == $id): ?>

<div class="card">

<h3><?= htmlspecialchars($offre['titre']) ?></h3>

<a class="postuler-btn" href="postuler.php?id=<?= $offre['id'] ?>">
Postuler
</a>

</div>

<?php endif; ?>

<?php endforeach; ?>

</div>

</section>

<?php include 'footer.php'; ?>
<script src="js/loginmodal.js"></script>

</body>
</html>