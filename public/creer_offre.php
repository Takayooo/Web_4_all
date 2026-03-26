<?php
session_start();
require 'data_helpers.php';
require 'pagination.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'entreprise') {
    header('Location: index.php');
    exit;
}

$user = $_SESSION['user'];
$entrepriseId = $user['entreprise_id'] ?? $user['id'];
$entreprise = $entreprises[$entrepriseId] ?? null;

if (!$entreprise) {
    die("Informations d'entreprise introuvables.");
}

$message = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre = trim($_POST['titre'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $contrat = $_POST['contrat'] ?? 'stage';

    if ($titre) {
        $nouvelId = creer_offre($entrepriseId, $titre, $description, $contrat);
        $message = 'Offre créée avec succès : ' . htmlspecialchars($titre, ENT_QUOTES, 'UTF-8');
        $success = true;
    } else {
        $message = 'Le titre est requis.';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Créer une offre</title>
<link rel="stylesheet" href="style.css?v=22">
</head>
<body>
<?php include 'header.php'; ?>
<section class="container">
    <h1>Créer une nouvelle offre</h1>
    <?php if ($message): ?>
        <div class="alert <?= $success ? 'alert-success' : 'alert-error' ?>"><?= $message ?></div>
        <?php if ($success): ?>
            <p><a href="dashboard.php" class="button">Retour au tableau de bord</a></p>
        <?php endif; ?>
    <?php endif; ?>
    <form action="creer_offre.php" method="post" class="form-section">
        <div class="form-group">
            <label for="titre">Titre de l'offre *</label>
            <input type="text" id="titre" name="titre" placeholder="Ex: Stage Développeur Web" required>
        </div>
        <div class="form-group">
            <label for="contrat">Type de contrat</label>
            <select id="contrat" name="contrat">
                <option value="stage">Stage</option>
                <option value="alternance">Alternance</option>
            </select>
        </div>
        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" placeholder="Décrivez l'offre, les missions, les compétences requises..." rows="6"></textarea>
        </div>
        <button type="submit" class="button">Créer l'offre</button>
    </form>
</section>
<?php include 'footer.php'; ?>
</body>
</html>