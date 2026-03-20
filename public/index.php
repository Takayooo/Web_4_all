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

<section class="hero">
<div class="container hero-content">
<h2>Faites votre recherche avec VOS critères :</h2>
<form class="search-form" action="index.php" method="get">
<input type="text" name="search" placeholder="Rechercher une annonce...">
<button type="submit">Rechercher</button>
</form>
</div>
</section>


<section class="annonces container">

<h2>Annonces récentes :</h2>

<div class="cards">

<?php foreach ($annoncesPage as $index => $annonce): ?>

<div class="card">

<h3><?= htmlspecialchars($annonce['titre']) ?></h3>

<p class="company">
<?= htmlspecialchars($annonce['nom']) ?> ⭐ <?= htmlspecialchars($annonce['note']) ?>
</p>

<p>
Secteur : <?= htmlspecialchars($annonce['secteur']) ?><br>
Ville : <?= htmlspecialchars($annonce['ville']) ?>
</p>

<a class="postuler-btn" href="postuler.php?id=<?= $debut + $index ?>">
Postuler
</a>

</div>

<?php endforeach; ?>

</div>


<div class="pagination">

<?php if ($page > 1): ?>
<a href="?page=<?= $page - 1 ?>">Précédent</a>
<?php endif; ?>

<?php for ($i = 1; $i <= $totalPages; $i++): ?>

<a href="?page=<?= $i ?>" class="<?= ($i === $page) ? 'active' : '' ?>">
<?= $i ?>
</a>

<?php endfor; ?>

<?php if ($page < $totalPages): ?>
<a href="?page=<?= $page + 1 ?>">Suivant</a>
<?php endif; ?>

</div>

</section>

<?php include 'footer.php'; ?>

</body>
</html>
