<?php require 'pagination.php'; ?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Offres</title>
<link rel="stylesheet" href="style.css?v=15">
</head>

<body>

<?php include 'header.php'; ?>

<section class="hero">
<div class="container hero-content">
<h2>Trouvez l'offre de VOS rêves avec VOS critères !</h2>
<form class="search-form" action="offres.php" method="get">
<input type="text" name="search" placeholder="Mot-clé (titre, entreprise, secteur, ville)" value="<?= htmlspecialchars($search ?? '') ?>">
<select name="contrat">
    <option value="">Tous contrats</option>
    <option value="stage" <?= (isset($contrat) && $contrat === 'stage') ? 'selected' : '' ?>>Stage</option>
    <option value="alternance" <?= (isset($contrat) && $contrat === 'alternance') ? 'selected' : '' ?>>Alternance</option>
</select>
<select name="secteur">
    <option value="">Tous secteurs</option>
    <option value="Technologie" <?= (isset($secteur) && $secteur === 'Technologie') ? 'selected' : '' ?>>Technologie</option>
    <option value="Finance" <?= (isset($secteur) && $secteur === 'Finance') ? 'selected' : '' ?>>Finance</option>
    <option value="Énergie" <?= (isset($secteur) && $secteur === 'Énergie') ? 'selected' : '' ?>>Énergie</option>
    <option value="Santé" <?= (isset($secteur) && $secteur === 'Santé') ? 'selected' : '' ?>>Santé</option>
</select>
<select name="ville">
    <option value="">Toutes villes</option>
    <option value="Paris" <?= (isset($ville) && $ville === 'Paris') ? 'selected' : '' ?>>Paris</option>
    <option value="Londres" <?= (isset($ville) && $ville === 'Londres') ? 'selected' : '' ?>>Londres</option>
    <option value="Berlin" <?= (isset($ville) && $ville === 'Berlin') ? 'selected' : '' ?>>Berlin</option>
    <option value="Madrid" <?= (isset($ville) && $ville === 'Madrid') ? 'selected' : '' ?>>Madrid</option>
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
<a href="?page=<?= $page - 1 ?><?= ($search ? '&search=' . urlencode($search) : '') ?><?= ($contrat ? '&contrat=' . urlencode($contrat) : '') ?><?= ($secteur ? '&secteur=' . urlencode($secteur) : '') ?><?= ($ville ? '&ville=' . urlencode($ville) : '') ?>">Précédent</a>
<?php endif; ?>

<?php for ($i = 1; $i <= $totalPages; $i++): ?>
    <a href="?page=<?= $i ?><?= ($search ? '&search=' . urlencode($search) : '') ?><?= ($contrat ? '&contrat=' . urlencode($contrat) : '') ?><?= ($secteur ? '&secteur=' . urlencode($secteur) : '') ?><?= ($ville ? '&ville=' . urlencode($ville) : '') ?>" class="<?= ($i === $page) ? 'active' : '' ?>"><?= $i ?></a>
<?php endfor; ?>

<?php if ($page < $totalPages): ?>
<a href="?page=<?= $page + 1 ?><?= ($search ? '&search=' . urlencode($search) : '') ?><?= ($contrat ? '&contrat=' . urlencode($contrat) : '') ?><?= ($secteur ? '&secteur=' . urlencode($secteur) : '') ?><?= ($ville ? '&ville=' . urlencode($ville) : '') ?>">Suivant</a>
<?php endif; ?>

</div>

</section>

<?php include 'footer.php'; ?>

</body>
</html>