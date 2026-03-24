<?php
require 'pagination.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    die("Offre introuvable");
}

// retrouver l'offre
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
<title>Postuler - <?= htmlspecialchars($offreTrouvee['titre']) ?></title>

<!-- CSS -->
<link rel="stylesheet" href="/style.css?v=4">
</head>

<body>

<?php include 'header.php'; ?>

<section class="container">

<h1><?= htmlspecialchars($offreTrouvee['titre']) ?></h1>

<h2>Postuler chez <?= htmlspecialchars($entreprise['nom']) ?></h2>

<p>
⭐ <?= htmlspecialchars($entreprise['note']) ?><br>
<?= htmlspecialchars($entreprise['secteur']) ?> - <?= htmlspecialchars($entreprise['ville']) ?>
</p>

<br>

<h3>Téléverser votre CV (PDF - 2 Mo max)</h3>

<form action="upload.php?id=<?= $offreTrouvee['id'] ?>" method="POST" enctype="multipart/form-data">

<input type="file" name="fichier" accept="application/pdf" required>

<br><br>

<button type="submit">Envoyer mon CV</button>

</form>

</section>

<?php include 'footer.php'; ?>
<script src="js/loginmodal.js"></script>

</body>
</html>