<?php
session_start();
require 'data_helpers.php';
require 'pagination.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'eleve') {
    header('Location: index.php');
    exit;
}

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

$message = null;
$success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérification session utilisateur
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'eleve') {
        $message = "Accès refusé.";
    } else {
        $userId = (int)$_SESSION['user']['id'];
        $maxSize = 2 * 1024 * 1024;
        function validateUpload($file, $maxSize) {
            if ($file['error'] !== UPLOAD_ERR_OK) {
                return 'Erreur lors du téléversement.';
            }
            if ($file['size'] > $maxSize) {
                return 'Le fichier dépasse 2 Mo.';
            }
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            if ($finfo->file($file['tmp_name']) !== 'application/pdf') {
                return 'Le fichier doit être un PDF.';
            }
            return null;
        }
        if (!isset($_FILES['cv']) || !isset($_FILES['lm'])) {
            $message = "CV et lettre de motivation sont requis.";
        } else {
            $errorCv = validateUpload($_FILES['cv'], $maxSize);
            $errorLm = validateUpload($_FILES['lm'], $maxSize);
            if ($errorCv || $errorLm) {
                $message = $errorCv ?? $errorLm;
            } else {
                $uploadDir = __DIR__ . '/uploads/' . $userId . '/' . $id . '/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                function storeFile($file, $uploadDir, $prefix) {
                    $name = basename($file['name']);
                    $safeName = preg_replace('/[^a-zA-Z0-9_\-.]/', '_', $name);
                    $dest = $uploadDir . $prefix . '_' . uniqid() . '_' . $safeName;
                    if (move_uploaded_file($file['tmp_name'], $dest)) {
                        return $dest;
                    }
                    return null;
                }
                $cvPath = storeFile($_FILES['cv'], $uploadDir, 'cv');
                $lmPath = storeFile($_FILES['lm'], $uploadDir, 'lm');
                if (!$cvPath || !$lmPath) {
                    $message = 'Erreur lors de l’enregistrement des fichiers.';
                } else {
                    ajouter_candidature($userId, $id, $cvPath, $lmPath);
                    ajouter_favori($userId, $id);
                    $success = true;
                    $message = 'Candidature enregistrée avec succès.';
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Postuler - <?= htmlspecialchars($offreTrouvee['titre']) ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="Déposez votre candidature pour l'offre <?= htmlspecialchars($offreTrouvee['titre'], ENT_QUOTES, 'UTF-8') ?> sur Web4All.">

<!-- FEUILLES DE STYLE -->
<link rel="stylesheet" href="/style.css?v=4">
</head>

<body>

<?php include 'header.php'; ?>

<main>

<section class="container">

<h1><?= htmlspecialchars($offreTrouvee['titre']) ?></h1>

<h2>Postuler chez <?= htmlspecialchars($entreprise['nom']) ?></h2>

<p>
⭐ <?= htmlspecialchars($entreprise['note']) ?><br>
<?= htmlspecialchars($entreprise['secteur']) ?> - <?= htmlspecialchars($entreprise['ville']) ?>
</p>

<br>

<h3>Déposez votre candidature</h3>

<div id="form-message" class="form-message<?php if ($message) echo $success ? ' success' : ' error'; ?><?php if (!$message) echo ' is-hidden'; ?>">
    <?php if ($message) echo htmlspecialchars($message); ?>
</div>

<form action="postuler.php?id=<?= $offreTrouvee['id'] ?>" method="POST" enctype="multipart/form-data" class="postuler-form">
    <div class="form-group">
        <label for="cv">CV (PDF - 2 Mo max)</label>
        <input type="file" id="cv" name="cv" accept="application/pdf" required>
    </div>
    <div class="form-group">
        <label for="lm">Lettre de motivation (PDF - 2 Mo max)</label>
        <input type="file" id="lm" name="lm" accept="application/pdf" required>
    </div>
    <button type="submit" class="postuler-btn postuler-btn-large">Envoyer ma candidature</button>
</form>

</section>

</main>

<?php include 'footer.php'; ?>
<script src="js/loginmodal.js"></script>
<script src="js/postuler.js"></script>

</body>
</html>