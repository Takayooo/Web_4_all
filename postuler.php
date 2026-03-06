<?php
require 'pagination.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($id === null || !isset($entreprises[$id])) {
    die("Annonce introuvable.");
}

$entreprise = $entreprises[$id];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Postuler</title>
<link rel="stylesheet" href="style.css">
</head>

<body>

<header>

<div class="container header-content">
<h1 class="logo"><a href="index.php?page=1">Web4All.</a></h1>
<button class="login-btn">Se connecter</button>
</div>

</header>

<section class="container">

<h2>Postuler chez <?= htmlspecialchars($entreprise['nom']) ?></h2>

<p>
Secteur : <?= htmlspecialchars($entreprise['secteur']) ?><br>
Ville : <?= htmlspecialchars($entreprise['ville']) ?>
</p>

<br>

<h3>Téléverser votre CV (PDF - 2 Mo max)</h3>

<form action="upload.php?id=<?= $id ?>" method="POST" enctype="multipart/form-data">

<input type="file" name="fichier" accept="application/pdf" required>

<br><br>

<button type="submit">Envoyer mon CV</button>

</form>

</section>

<footer>

<div class="container footer">

<p>WEB4ALL - Tous droits réservés</p>

<div>
<a href="#">Mentions légales</a>
<a href="#">Politique de cookies</a>
<a href="#">Nous contacter</a>
</div>

</div>

</footer>

</body>
</html>