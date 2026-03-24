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
<link rel="stylesheet" href="style.css?v=16">
</head>

<body>

<header>
    <?php include 'header.php'; ?>
</header>

<?php if (isset($_SESSION['success'])): ?>
    <div style="margin:20px auto; width:90%; max-width:1200px; padding:15px; border:1px solid #28a745; color:#155724; background:#d4edda; border-radius:8px;">
        <?= htmlspecialchars($_SESSION['success'], ENT_QUOTES, 'UTF-8') ?>
    </div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

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
Voir l'offre
</a>

</div>

<?php endforeach; ?>

</div>

</section>

<?php include 'footer.php'; ?>
<script src="/js/loginmodal.js?v=3"></script>
</body>
</html>
