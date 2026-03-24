<?php require 'pagination.php'; ?>

<?php
$users = json_decode(file_get_contents(__DIR__ . '/users.json'), true);
$eleves = array_filter($users, function($u) {
    return $u['role'] === 'eleve';
});
$nbEleves = count($eleves);

$activeOffres = array_filter($offres, function($o) {
    return isset($o['statut']) && $o['statut'] === 'active';
});
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title >Web4All</title>
<link rel="stylesheet" href="style.css?v=13">
</head>

<body>

<header>
    <?php include 'header.php'; ?>
</header>

<section class="stats container">
<a href="offres.php" class="stats-box">
<p><?= count($activeOffres) ?></p>
<h3>Offres</h3>
</a>
<a href="entreprises.php" class="stats-box">
<p><?= count($entreprises) ?></p>
<h3>Entreprises</h3>
</a>
<a href="login.php" class="stats-box">
<p><?= $nbEleves ?></p>
<h3>Etudiants Inscrits</h3>
</a>
</section>

<section class="annonces container">

<h2>Annonces récentes :</h2>

<div class="cards">

<?php
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
