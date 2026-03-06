<?php

require 'pagination.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($id === null || !isset($entreprises[$id])) {
    die("Annonce introuvable.");
}

$entreprise = $entreprises[$id];

$maxSize = 2 * 1024 * 1024;
$uploadDir = "uploads/";

if (!isset($_FILES['fichier'])) {
    die("Aucun fichier n'a été envoyé.");
}

$fichier = $_FILES['fichier'];

if ($fichier['error'] !== UPLOAD_ERR_OK) {
    die("Erreur lors du téléversement.");
}

if ($fichier['size'] > $maxSize) {
    die("Le fichier dépasse 2 Mo.");
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mimeType = $finfo->file($fichier['tmp_name']);

if ($mimeType !== 'application/pdf') {
    die("Le fichier doit être un PDF.");
}

$nomOriginal = basename($fichier['name']);
$nomSecurise = htmlspecialchars($nomOriginal, ENT_QUOTES, 'UTF-8');

$nomFinal = uniqid() . "_" . $nomOriginal;

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$destination = $uploadDir . $nomFinal;

if (move_uploaded_file($fichier['tmp_name'], $destination)) {

echo "<h2>Candidature envoyée !</h2>";
echo "Entreprise : " . htmlspecialchars($entreprise['nom']) . "<br>";
echo "Fichier : " . $nomSecurise;

} else {
echo "Erreur lors de l'enregistrement.";
}