<?php
require 'data_helpers.php';
require 'pagination.php';

$current_page = 'offres';

$secteurs_list = array_unique(array_filter(array_column($entreprises, 'secteur')));
$villes_list = array_unique(array_filter(array_column($entreprises, 'ville')));
sort($secteurs_list, SORT_LOCALE_STRING);
sort($villes_list, SORT_LOCALE_STRING);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Offres</title>
<link rel="stylesheet" href="style.css?v=16">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="Parcourez toutes les offres de stage et d'alternance disponibles sur Web4All. Recherchez par mot-clé, contrat et localisation.">
</head>

<body>

<?php include 'header.php'; ?>
<main>

<section class="hero">
<div class="container hero-content">
<h2>Trouvez l'offre de VOS rêves avec VOS critères !</h2>
<form class="search-form" action="offres.php" method="get" aria-label="Rechercher une offre">
<input type="text" name="search" placeholder="Mot-clé (titre, entreprise, secteur, ville)" value="<?= htmlspecialchars($search ?? '') ?>">
<select name="contrat">
    <option value="">Tous contrats</option>
    <option value="stage" <?= (isset($contrat) && $contrat === 'stage') ? 'selected' : '' ?>>Stage</option>
    <option value="alternance" <?= (isset($contrat) && $contrat === 'alternance') ? 'selected' : '' ?>>Alternance</option>
</select>
<select name="secteur">
    <option value="">Tous secteurs</option>
    <?php foreach ($secteurs_list as $s): ?>
    <option value="<?= htmlspecialchars($s) ?>" <?= (isset($secteur) && $secteur === $s) ? 'selected' : '' ?>><?= htmlspecialchars($s) ?></option>
    <?php endforeach; ?>
</select>
<select name="ville">
    <option value="">Toutes villes</option>
    <?php foreach ($villes_list as $v): ?>
    <option value="<?= htmlspecialchars($v) ?>" <?= (isset($ville) && $ville === $v) ? 'selected' : '' ?>><?= htmlspecialchars($v) ?></option>
    <?php endforeach; ?>
</select>
<button type="submit">Rechercher</button>
</form>
</div>
</section>

<section class="annonces container">

<h2>Offres disponibles</h2>
<div class="cards">

<?php if (empty($offresPage)): ?>
<p>Aucune offre ne correspond à vos critères.</p>
<?php else: ?>

<?php foreach ($offresPage as $offre): ?>
    <?php $entreprise = $entreprises[$offre['entreprise_id']]; ?>
    <div class="card">
        <h3><?= htmlspecialchars($offre['titre']) ?></h3>
        <p class="company"><?= htmlspecialchars($entreprise['nom']) ?> ⭐ <?= htmlspecialchars($entreprise['note']) ?></p>
        <p>Type: <?= htmlspecialchars($offre['contrat']) ?> | Secteur: <?= htmlspecialchars($entreprise['secteur']) ?> | Ville: <?= htmlspecialchars($entreprise['ville']) ?></p>
        <a class="postuler-btn" href="offre.php?id=<?= $offre['id'] ?>">Voir l'offre</a>
    </div>
<?php endforeach; ?>

<?php endif; ?>

</div>

<div class="pagination">

<?php if ($page > 1): ?>
<a href="?page=<?= $page - 1 ?><?= $qs ?>">Précédent</a>
<?php endif; ?>

<?php
$qs = ($search  ? '&search='  . urlencode($search)  : '')
    . ($contrat ? '&contrat=' . urlencode($contrat) : '')
    . ($secteur ? '&secteur=' . urlencode($secteur) : '')
    . ($ville   ? '&ville='   . urlencode($ville)   : '');

$delta = 2; // pages affichées de chaque côté de la page courante
$range = [];
for ($i = max(1, $page - $delta); $i <= min($totalPages, $page + $delta); $i++) {
    $range[] = $i;
}

$dots_left  = ($range[0] > 2);
$dots_right = ($range[count($range) - 1] < $totalPages - 1);

// Première page
if (!in_array(1, $range)): ?>
    <a href="?page=1<?= $qs ?>" class="">1</a>
<?php endif; ?>
<?php if ($dots_left): ?>
    <span class="pagination-dots">…</span>
<?php endif; ?>
<?php foreach ($range as $i): ?>
    <a href="?page=<?= $i ?><?= $qs ?>" class="<?= ($i === $page) ? 'active' : '' ?>"><?= $i ?></a>
<?php endforeach; ?>
<?php if ($dots_right): ?>
    <span class="pagination-dots">…</span>
<?php endif; ?>
<?php if (!in_array($totalPages, $range) && $totalPages > 1): ?>
    <a href="?page=<?= $totalPages ?><?= $qs ?>" class=""><?= $totalPages ?></a>
<?php endif; ?>

<?php if ($page < $totalPages): ?>
<a href="?page=<?= $page + 1 ?><?= $qs ?>">Suivant</a>
<?php endif; ?>

</div>

</section>

</main>

<?php include 'footer.php'; ?>

</body>
</html>