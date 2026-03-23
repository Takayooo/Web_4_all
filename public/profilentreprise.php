<?php
require 'pagination.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || !isset($entreprises[$id])) {
    die("Entreprise introuvable");
}

$entreprise = $entreprises[$id];
?>

<h1><?= $entreprise['nom'] ?></h1>

<div class="entreprise-header">
<p>⭐ <?= $entreprise['note'] ?></p>
<p><?= $entreprise['secteur'] ?> - <?= $entreprise['ville'] ?></p>
<p><?= $entreprise['description'] ?></p>
</div>

<h2>Offres de cette entreprise</h2>

<div class="cards">

<?php foreach ($offres as $offre): ?>

<?php if ($offre['entreprise_id'] == $id): ?>

<div class="card">
<h3><?= $offre['titre'] ?></h3>

<a href="postuler.php?id=<?= $offre['id'] ?>">
Postuler
</a>
</div>

<?php endif; ?>

<?php endforeach; ?>

</div>