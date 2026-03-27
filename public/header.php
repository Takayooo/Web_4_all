<?php
session_start();

// récupérer erreur
$error = $_SESSION['error'] ?? null;

// IMPORTANT : ne supprimer qu'après usage
$hasError = !empty($error);

// supprimer après
unset($_SESSION['error']);

require_once __DIR__ . '/../vendor/autoload.php';

use Twig\Loader\FilesystemLoader;
use Twig\Environment;

$loader = new FilesystemLoader(__DIR__ . '/../templates');
$twig = new Environment($loader);

$user = $_SESSION['user'] ?? null;

echo $twig->render('partials/header.twig', [
    'user' => $user,
    'error' => $error,
    'hasError' => $hasError,
    'current_page' => $current_page ?? null
]);