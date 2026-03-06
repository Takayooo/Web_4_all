<?php require 'pagination.php'; ?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Web4All</title>
<link rel="stylesheet" href="style.css">
</head>

<body>

<header>

<div class="container header-content">
<h1 class="logo">Web4All.</h1>
<button class="login-btn">Se connecter</button>
</div>

</header>


<section class="annonces container">

<h2>Annonces récentes :</h2>

<div class="cards">

<?php foreach ($annoncesPage as $annonce): ?>

<div class="card">

<h3>Stage - Développeur Web</h3>

<p class="company">
<?= htmlspecialchars($annonce['nom']) ?> ⭐ 4.0
</p>

<p>
Secteur : <?= htmlspecialchars($annonce['secteur']) ?><br>
Ville : <?= htmlspecialchars($annonce['ville']) ?>
</p>

<button>Postuler</button>

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

</body>
</html>
