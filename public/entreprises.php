<?php require 'pagination.php'; ?>

<?php
$entreprisesArray = array_values($entreprises);
$entreprisesParPage = 12;
$totalEntreprises = count($entreprisesArray);
$totalPages = ceil($totalEntreprises / $entreprisesParPage);

$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1]
]);

if (!$page) {
    $page = 1;
}

if ($page > $totalPages) {
    $page = $totalPages;
}

$debut = ($page - 1) * $entreprisesParPage;
$entreprisesPage = array_slice($entreprisesArray, $debut, $entreprisesParPage);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Entreprises</title>
<link rel="stylesheet" href="style.css?v=13">
</head>

<body>

<?php include 'header.php'; ?>

<section class="container">

<h2>Liste des entreprises</h2>

<div class="cards">

<?php foreach ($entreprisesPage as $entreprise): ?>

<div class="card">

<h3><?= htmlspecialchars($entreprise['nom']) ?></h3>

<p>
⭐ <?= htmlspecialchars($entreprise['note']) ?><br>
<?= htmlspecialchars($entreprise['secteur']) ?> - <?= htmlspecialchars($entreprise['ville']) ?>
</p>

<a class="postuler-btn" href="profilentreprise.php?id=<?= $entreprise['id'] ?>">
Voir le profil
</a>

</div>

<?php endforeach; ?>

</div>

<div class="pagination">

<?php if ($page > 1): ?>
<a href="?page=<?= $page - 1 ?>">Précédent</a>
<?php endif; ?>

<?php for ($i = 1; $i <= $totalPages; $i++): ?>
    <a href="?page=<?= $i ?>" class="<?= ($i === $page) ? 'active' : '' ?>"><?= $i ?></a>
<?php endfor; ?>

<?php if ($page < $totalPages): ?>
<a href="?page=<?= $page + 1 ?>">Suivant</a>
<?php endif; ?>

</div>

</section>

<?php include 'footer.php'; ?>

</body>
</html>