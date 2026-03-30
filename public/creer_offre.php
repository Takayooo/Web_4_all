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

$offreId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$offreEnCours = null;

if ($offreId) {
    foreach (charger_offres() as $offre) {
        if (($offre['id'] ?? null) === $offreId && ($offre['entreprise_id'] ?? null) === $entrepriseId) {
            $offreEnCours = $offre;
            break;
        }
    }

    if (!$offreEnCours) {
        die("Offre introuvable ou accès non autorisé.");
    }
}

$isEditMode = $offreEnCours !== null;
$message = '';
$success = false;
$titre = $offreEnCours['titre'] ?? '';
$description = $offreEnCours['description'] ?? '';
$contrat = $offreEnCours['contrat'] ?? 'stage';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedOffreId = filter_input(INPUT_POST, 'offre_id', FILTER_VALIDATE_INT);
    if ($postedOffreId) {
        $offreId = $postedOffreId;
    }

    $titre = trim($_POST['titre'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $contrat = $_POST['contrat'] ?? 'stage';
    $isEditMode = $offreId !== null;

    if ($isEditMode) {
        $offreEnCours = null;
        foreach (charger_offres() as $offre) {
            if (($offre['id'] ?? null) === $offreId && ($offre['entreprise_id'] ?? null) === $entrepriseId) {
                $offreEnCours = $offre;
                break;
            }
        }

        if (!$offreEnCours) {
            die("Offre introuvable ou accès non autorisé.");
        }
    }

    if ($titre) {
        if ($isEditMode) {
            $success = modifier_offre($offreId, $entrepriseId, $titre, $description, $contrat);
            $message = $success
                ? 'Offre modifiée avec succès : ' . htmlspecialchars($titre, ENT_QUOTES, 'UTF-8')
                : 'La modification de l\'offre a échoué.';
        } else {
            creer_offre($entrepriseId, $titre, $description, $contrat);
            $message = 'Offre créée avec succès : ' . htmlspecialchars($titre, ENT_QUOTES, 'UTF-8');
            $success = true;
        }
    } else {
        $message = 'Le titre est requis.';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title><?= $isEditMode ? 'Modifier une offre' : 'Créer une offre' ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="<?= $isEditMode ? 'Modifiez votre offre de stage ou d\'alternance sur Web4All.' : 'Publiez une nouvelle offre de stage ou d\'alternance sur Web4All.' ?>">
<link rel="stylesheet" href="style.css?v=22">
</head>
<body>
<?php include 'header.php'; ?>
<main>
<section class="container">
    <h1><?= $isEditMode ? 'Modifier l\'offre' : 'Créer une nouvelle offre' ?></h1>
    <?php if ($message): ?>
        <div class="alert <?= $success ? 'alert-success' : 'alert-error' ?>"><?= $message ?></div>
        <?php if ($success): ?>
            <p><a href="dashboard.php" class="button">Retour au tableau de bord</a></p>
        <?php endif; ?>
    <?php endif; ?>
    <form action="creer_offre.php<?= $isEditMode ? '?id=' . $offreId : '' ?>" method="post" class="form-section" novalidate>
        <?php if ($isEditMode): ?>
            <input type="hidden" name="offre_id" value="<?= $offreId ?>">
        <?php endif; ?>
        <div class="form-group">
            <label for="titre">Titre de l'offre *</label>
            <input type="text" id="titre" name="titre" placeholder="Ex: Stage Développeur Web" minlength="5" value="<?= htmlspecialchars($titre, ENT_QUOTES, 'UTF-8') ?>" required>
        </div>
        <div class="form-group">
            <label for="contrat">Type de contrat</label>
            <select id="contrat" name="contrat">
                <option value="stage" <?= $contrat === 'stage' ? 'selected' : '' ?>>Stage</option>
                <option value="alternance" <?= $contrat === 'alternance' ? 'selected' : '' ?>>Alternance</option>
            </select>
        </div>
        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" placeholder="Décrivez l'offre, les missions, les compétences requises..." rows="6"><?= htmlspecialchars($description, ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>
        <button type="submit" class="button"><?= $isEditMode ? 'Enregistrer les modifications' : 'Créer l\'offre' ?></button>
    </form>
</section>
</main>
<?php include 'footer.php'; ?>
</body>
</html>