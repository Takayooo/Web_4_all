<?php

session_start();
require 'data_helpers.php';

/* ------------------------
   RÉCUPÉRATION DES DONNÉES
------------------------ */

$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

$foundUser = authenticate_user_auto($email, $password);

/* ------------------------
   RÉSULTAT
------------------------ */

if ($foundUser) {

    $_SESSION['user'] = [
        'id' => $foundUser['id'],
        'email' => $foundUser['email'],
        'nom' => $foundUser['nom'],
        'prenom' => $foundUser['prenom'] ?? '',
        'role' => $foundUser['role']
    ];

    // Ajouter les champs spécifiques selon le rôle
    if ($foundUser['role'] === 'entreprise') {
        $_SESSION['user']['secteur'] = $foundUser['secteur'] ?? '';
        $_SESSION['user']['ville'] = $foundUser['ville'] ?? '';
        $_SESSION['user']['entreprise_id'] = $foundUser['entreprise_id'] ?? $foundUser['id'];
        $_SESSION['user']['note'] = $foundUser['note'] ?? 0;
    }

    if ($foundUser['role'] === 'eleve') {
        $_SESSION['user']['pilote_id'] = $foundUser['pilote_id'] ?? null;
    }

    header("Location: index.php");
    exit;

} else {

    $_SESSION['error'] = "Email ou mot de passe incorrect.";
    header("Location: index.php");
    exit;
}