<?php

session_start();

//simulation user
if (isset($_GET['testuser'])) {
    $_SESSION['user'] = [
        'nom' => 'Test User'
    ];
}

require_once __DIR__ . '/../vendor/autoload.php';

use Twig\Loader\FilesystemLoader;
use Twig\Environment;

$loader = new FilesystemLoader(__DIR__ . '/../templates');
$twig = new Environment($loader);

// récupérer l'utilisateur (ou null)
$user = $_SESSION['user'] ?? null;

// afficher le header
echo $twig->render('partials/header.twig', [
    'user' => $user
]);

