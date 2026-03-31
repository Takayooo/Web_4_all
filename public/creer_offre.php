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
$remuneration = $offreEnCours['remuneration'] ?? '';
$niveauEtude = $offreEnCours['niveau_etude'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedOffreId = filter_input(INPUT_POST, 'offre_id', FILTER_VALIDATE_INT);
    if ($postedOffreId) {
        $offreId = $postedOffreId;
    }

    $titre = trim($_POST['titre'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $contrat = $_POST['contrat'] ?? 'stage';
    $remuneration = trim($_POST['remuneration'] ?? '');
    $niveauEtude = trim($_POST['niveau_etude'] ?? '');
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
            $success = modifier_offre($offreId, $entrepriseId, $titre, $description, $contrat, $remuneration, $niveauEtude);
            $message = $success
                ? 'Offre modifiée avec succès : ' . htmlspecialchars($titre, ENT_QUOTES, 'UTF-8')
                : 'La modification de l\'offre a échoué.';
        } else {
            creer_offre($entrepriseId, $titre, $description, $contrat, $remuneration, $niveauEtude);
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
<link rel="stylesheet" href="style.css?v=23">
</head>
<body>
<?php include 'header.php'; ?>
<main>
<section class="creer-offre-hero">
    <div class="container">
        <h1><?= $isEditMode ? 'Modifier l\'offre' : 'Créer une nouvelle offre' ?></h1>
        <p><?= $isEditMode ? 'Modifiez les informations de votre offre ci-dessous.' : 'Publiez une offre de stage ou d\'alternance pour trouver le candidat idéal.' ?></p>
    </div>
</section>
<section class="container creer-offre-wrapper">
    <div class="creer-offre-card">
        <?php if ($message): ?>
            <div class="alert <?= $success ? 'alert-success' : 'alert-error' ?>"><?= $message ?></div>
            <?php if ($success): ?>
                <p style="text-align:center;"><a href="dashboard.php" class="button">Retour au tableau de bord</a></p>
            <?php endif; ?>
        <?php endif; ?>
        <form action="creer_offre.php<?= $isEditMode ? '?id=' . $offreId : '' ?>" method="post" class="creer-offre-form" novalidate>
            <?php if ($isEditMode): ?>
                <input type="hidden" name="offre_id" value="<?= $offreId ?>">
            <?php endif; ?>
            <div class="creer-offre-field">
                <label for="titre">Titre de l'offre <span class="required">*</span></label>
                <input type="text" id="titre" name="titre" placeholder="Ex: Stage Développeur Web" minlength="5" value="<?= htmlspecialchars($titre, ENT_QUOTES, 'UTF-8') ?>" required>
            </div>
            <div class="creer-offre-field">
                <label for="contrat">Type de contrat</label>
                <select id="contrat" name="contrat">
                    <option value="stage" <?= $contrat === 'stage' ? 'selected' : '' ?>>Stage</option>
                    <option value="alternance" <?= $contrat === 'alternance' ? 'selected' : '' ?>>Alternance</option>
                </select>
            </div>
            <div class="creer-offre-field">
                <label for="remuneration">Rémunération</label>
                <input type="text" id="remuneration" name="remuneration" placeholder="Ex: 600€/mois, 800€/mois..." value="<?= htmlspecialchars($remuneration, ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="creer-offre-field">
                <label for="niveau_etude">Niveau d'études requis</label>
                <input type="text" id="niveau_etude" name="niveau_etude" placeholder="Ex: Bac+3, Bac+5, Master..." value="<?= htmlspecialchars($niveauEtude, ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="creer-offre-field">
                <label for="description">Description</label>
                <textarea id="description" name="description" placeholder="Décrivez l'offre, les missions, les compétences requises..." rows="6"><?= htmlspecialchars($description, ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>
            <button type="submit" class="creer-offre-submit"><?= $isEditMode ? 'Enregistrer les modifications' : 'Créer l\'offre' ?></button>
        </form>
    </div>
</section>
</main>
<?php include 'footer.php'; ?>
</body>
</html>