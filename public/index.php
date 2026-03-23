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

<?php foreach ($offresPage as $index => $offre): 

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

<a class="postuler-btn" href="postuler.php?id=<?= $debut + $index + 1 ?>">
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
<script src="/js/loginmodal.js?v=3"></script>
</body>
</html>
