<?php
require 'data_helpers.php';
require 'pagination.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || !isset($entreprises[$id])) {
    die("Entreprise introuvable");
}

$entreprise = $entreprises[$id];

$offresEntreprise = array_filter($offres, function($o) use ($id) {
    return $o['entreprise_id'] == $id && isset($o['statut']) && $o['statut'] === 'active';
});

$offresParPage = 12;
$totalOffres = count($offresEntreprise);
$totalPages = ceil($totalOffres / $offresParPage);

$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1]
]);

if (!$page) {
    $page = 1;
}

if ($page > $totalPages) {
    $page = $totalPages;
}

$debut = ($page - 1) * $offresParPage;
$offresPage = array_slice(array_values($offresEntreprise), $debut, $offresParPage);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($entreprise['nom']) ?></title>

<link rel="stylesheet" href="/style.css?v=13">

</head>

<body>

<?php include 'header.php'; ?>

<section class="container">

<div class="entreprise-header">

<h1><?= htmlspecialchars($entreprise['nom']) ?></h1>

<div class="entreprise-meta">
⭐ <?= $entreprise['note'] ?> |
<?= $entreprise['secteur'] ?> - <?= $entreprise['ville'] ?>
</div>

<div class="entreprise-description">
<?= htmlspecialchars($entreprise['description']) ?>
</div>

</div>

<h2 class="offres-title">Offres disponibles</h2>

<div class="cards">

<?php foreach ($offresPage as $offre): ?>

<div class="card">

<h3><?= htmlspecialchars($offre['titre']) ?></h3>

<a class="postuler-btn" href="offre.php?id=<?= $offre['id'] ?>">
Voir l'offre
</a>

</div>

<?php endforeach; ?>

</div>

<div class="pagination">

<?php if ($page > 1): ?>
<a href="?id=<?= $id ?>&page=<?= $page - 1 ?>">Précédent</a>
<?php endif; ?>

<?php for ($i = 1; $i <= $totalPages; $i++): ?>
    <a href="?id=<?= $id ?>&page=<?= $i ?>" class="<?= ($i === $page) ? 'active' : '' ?>"><?= $i ?></a>
<?php endfor; ?>

<?php if ($page < $totalPages): ?>
<a href="?id=<?= $id ?>&page=<?= $page + 1 ?>">Suivant</a>
<?php endif; ?>

</div>

</section>

<?php include 'footer.php'; ?>
<script src="js/loginmodal.js"></script>

</body>
</html>