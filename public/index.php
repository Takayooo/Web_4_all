<?php
session_start();
require 'data_helpers.php';
require 'pagination.php';

$current_page = 'accueil';

$nbEleves = get_student_count();

$activeOffres = array_filter($offres, function($o) {
    return isset($o['statut']) && $o['statut'] === 'active';
});
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Web4All</title>
<link rel="stylesheet" href="style.css?v=19">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="Web4All - Trouvez vos offres de stage et d'alternance. Découvrez nos offres, nos entreprises partenaires et inscrivez-vous gratuitement.">
</head>

<body>

<main>
<div role="banner">
    <?php include 'header.php'; ?>

</div>
<?php if (isset($_SESSION['success'])): ?>
    <div class="container alert alert-success" role="status">
        <?= htmlspecialchars($_SESSION['success'], ENT_QUOTES, 'UTF-8') ?>
    </div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<section class="stats container" aria-label="Statistiques">
    <a href="offres.php" class="stats-box stats-box-featured">
        <span class="stats-box-icon-wrap" aria-hidden="true">
            <img src="assets/offres.png" alt="" class="stats-box-icon">
        </span>
        <span class="stats-box-text">
            <p><?= count($activeOffres) ?></p>
            <h3>Offres</h3>
        </span>
    </a>
    <a href="entreprises.php" class="stats-box stats-box-featured">
        <span class="stats-box-icon-wrap" aria-hidden="true">
            <img src="assets/valise.png" alt="" class="stats-box-icon">
        </span>
        <span class="stats-box-text">
            <p><?= count($entreprises) ?></p>
            <h3>Entreprises</h3>
        </span>
    </a>
    <a href="login.php" class="stats-box stats-box-featured">
        <span class="stats-box-icon-wrap" aria-hidden="true">
            <img src="assets/user.png" alt="" class="stats-box-icon">
        </span>
        <span class="stats-box-text">
            <p><?= $nbEleves ?></p>
            <h3>Étudiants inscrits</h3>
        </span>
    </a>

</section>

<section class="annonces container">

<h2>Annonces récentes :</h2>

<?php
usort($activeOffres, function($a, $b) {
    return $b['id'] <=> $a['id'];
});

$last8 = array_slice($activeOffres, 0, 8);
?>

<?php if (empty($last8)): ?>
<p>Aucune annonce disponible pour le moment.</p>
<?php else: ?>

<div class="carousel-section annonces-carousel-section" aria-label="Carrousel des dernières annonces">
    <div class="carousel-wrapper annonces-carousel-wrapper">
        <div id="carousel-track" class="carousel-track annonces-carousel-track" aria-live="off">
            <?php foreach ($last8 as $offre):
            $entreprise = $entreprises[$offre['entreprise_id']];
            ?>
            <article class="card carousel-card annonce-carousel-card">
                <h3><?= htmlspecialchars($offre['titre']) ?></h3>
                <p class="company">
                    <?= htmlspecialchars($entreprise['nom']) ?> ⭐ <?= htmlspecialchars($entreprise['note']) ?>
                </p>
                <p>
                    Secteur : <?= htmlspecialchars($entreprise['secteur']) ?><br>
                    Ville : <?= htmlspecialchars($entreprise['ville']) ?>
                </p>
                <a class="postuler-btn" href="offre.php?id=<?= $offre['id'] ?>">Voir l'offre</a>
            </article>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="carousel-controls" role="group" aria-label="Contrôles du carrousel annonces">
        <button id="carousel-prev" class="carousel-btn" aria-label="Déplacer vers la gauche">&#8249;</button>
        <button id="carousel-next" class="carousel-btn" aria-label="Déplacer vers la droite">&#8250;</button>
        <button id="carousel-pause" class="carousel-pause" aria-label="Pause/Reprendre">&#9208; Pause</button>
    </div>
</div>

<?php endif; ?>

</section>

</main>
<?php include 'footer.php'; ?>
<script src="js/loginmodal.js?v=3"></script>
<script src="js/carousel.js?v=3"></script>
</body>
</html>
