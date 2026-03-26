<?php
require 'data_helpers.php';
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

<!-- FEUILLES DE STYLE -->
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

<h3>Déposez votre candidature</h3>

<form action="upload.php?id=<?= $offreTrouvee['id'] ?>" method="POST" enctype="multipart/form-data">
    <label for="cv">CV (PDF - 2 Mo max)</label>
    <input type="file" id="cv" name="cv" accept="application/pdf" required>

    <label for="lm">Lettre de motivation (PDF - 2 Mo max)</label>
    <input type="file" id="lm" name="lm" accept="application/pdf" required>

    <button type="submit">Envoyer ma candidature</button>
</form>

</section>

<?php include 'footer.php'; ?>
<script src="js/loginmodal.js"></script>

</body>
</html>