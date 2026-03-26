<?php
session_start();

$f = $_GET['f'] ?? '';
if (!$f) {
    die('Fichier invalide.');
}

$decoded = base64_decode($f);
if (!$decoded) {
    die('Fichier invalide.');
}

// Sécuriser le chemin de base : uniquement dossier uploads
$base = realpath(__DIR__ . '/uploads');
$path = realpath($decoded);

if (!$path || strpos($path, $base) !== 0 || !is_file($path)) {
    die('Fichier introuvable.');
}

$filename = basename($path);
header('Content-Description: File Transfer');
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($path));
readfile($path);
exit;
