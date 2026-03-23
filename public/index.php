<?php require 'pagination.php'; ?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title >Web4All</title>
<link rel="stylesheet" href="style.css?v=2">
</head>

<body>

<header>
    <?php include 'header.php'; ?>
</header>



<section class="annonces container">

<h2>Annonces récentes :</h2>

<div class="cards">

<?php
$activeOffres = array_filter($offres, function($o) {
    return isset($o['statut']) && $o['statut'] === 'active';
});

usort($activeOffres, function($a, $b) {
    return $b['id'] <=> $a['id'];
});

$last4 = array_slice($activeOffres, 0, 4);
?>

<?php foreach ($last4 as $offre):

$entreprise = $entreprises[$offre['entreprise_id']];

?>

<div class="card">

<h3><?= htmlspecialchars($offre['titre']) ?></h3>

<p class="company">
<?= htmlspecialchars($entreprise['nom']) ?> ⭐ <?= htmlspecialchars($entreprise['note']) ?>
</p>

<p>
Secteur : <?= htmlspecialchars($entreprise['secteur']) ?><br>
Ville : <?= htmlspecialchars($entreprise['ville']) ?>
</p>

<a class="postuler-btn" href="offre.php?id=<?= $offre['id'] ?>">
Postuler
</a>

</div>

<?php endforeach; ?>

</div>

</section>

<?php include 'footer.php'; ?>
<script src="/js/loginmodal.js?v=3"></script>
</body>
</html>
