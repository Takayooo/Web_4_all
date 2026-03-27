<?php

session_start();
require 'data_helpers.php';
require 'pagination.php';

function isAjax() {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

function sendJson($success, $message) {
    header('Content-Type: application/json');
    echo json_encode(['success' => $success, 'message' => $message]);
    exit;
}

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'eleve') {
    if (isAjax()) sendJson(false, "Accès refusé.");
    die("Accès refusé.");
}

$userId = (int)$_SESSION['user']['id'];
$offreId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$offreId) {
    if (isAjax()) sendJson(false, "Offre introuvable.");
    die("Offre introuvable.");
}

// retrouver l'offre
$offreTrouvee = null;
foreach ($offres as $offre) {
    if ($offre['id'] === $offreId) {
        $offreTrouvee = $offre;
        break;
    }
}

if (!$offreTrouvee || !isset($offreTrouvee['statut']) || $offreTrouvee['statut'] !== 'active') {
    if (isAjax()) sendJson(false, "Offre introuvable.");
    die("Offre introuvable.");
}

$maxSize = 2 * 1024 * 1024;

if (!isset($_FILES['cv']) || !isset($_FILES['lm'])) {
    if (isAjax()) sendJson(false, "CV et lettre de motivation sont requis.");
    die("CV et lettre de motivation sont requis.");
}

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

$errorCv = validateUpload($_FILES['cv'], $maxSize);
$errorLm = validateUpload($_FILES['lm'], $maxSize);
if ($errorCv || $errorLm) {
    if (isAjax()) sendJson(false, $errorCv ?? $errorLm);
    die($errorCv ?? $errorLm);
}

$uploadDir = __DIR__ . '/uploads/' . $userId . '/' . $offreId . '/';
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
    if (isAjax()) sendJson(false, 'Erreur lors de l’enregistrement des fichiers.');
    die('Erreur lors de l’enregistrement des fichiers.');
}

ajouter_candidature($userId, $offreId, $cvPath, $lmPath);
ajouter_favori($userId, $offreId);

if (isAjax()) {
    sendJson(true, 'Candidature enregistrée avec succès.');
}

$_SESSION['success'] = 'Candidature enregistrée avec succès.';
header('Location: dashboard.php');
exit;
