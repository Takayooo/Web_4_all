<?php require 'pagination.php'; ?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Entreprises</title>
<link rel="stylesheet" href="style.css">
</head>

<body>

<?php include 'header.php'; ?>

<section class="container">

<h2>Liste des entreprises</h2>

<div class="cards">

<?php foreach ($entreprises as $entreprise): ?>

<div class="card">

<h3><?= htmlspecialchars($entreprise['nom']) ?></h3>

<p>
⭐ <?= htmlspecialchars($entreprise['note']) ?><br>
<?= htmlspecialchars($entreprise['secteur']) ?> - <?= htmlspecialchars($entreprise['ville']) ?>
</p>

<a class="postuler-btn" href="entreprise.php?id=<?= $entreprise['id'] ?>">
Voir le profil
</a>

</div>

<?php endforeach; ?>

</div>

</section>

<?php include 'footer.php'; ?>

</body>
</html>