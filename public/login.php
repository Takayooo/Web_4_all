<?php

session_start();

/* ------------------------
   "FAUSSE BASE DE DONNÉES"
------------------------ */

$users = json_decode(file_get_contents(__DIR__ . '/users.json'), true);

/* ------------------------
   RÉCUPÉRATION DES DONNÉES
------------------------ */

$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';
$role = $_POST['role'] ?? '';

/* ------------------------
   VÉRIFICATION
------------------------ */

$foundUser = null;

foreach ($users as $user) {

    if (
        $user['email'] === $email &&
        $user['password'] === $password &&
        $user['role'] === $role
    ) {
        $foundUser = $user;
        break;
    }
}

/* ------------------------
   RÉSULTAT
------------------------ */

if ($foundUser) {

    $_SESSION['user'] = [
        'email' => $foundUser['email'],
        'nom' => $foundUser['nom'],
        'prenom' => $foundUser['prenom'] ?? '',
        'role' => $foundUser['role']
    ];

    // Ajouter les champs spécifiques selon le rôle
    if ($foundUser['role'] === 'entreprise') {
        $_SESSION['user']['secteur'] = $foundUser['secteur'] ?? '';
        $_SESSION['user']['ville'] = $foundUser['ville'] ?? '';
    }

    header("Location: index.php");
    exit;

} else {

    $_SESSION['error'] = "Email, mot de passe ou rôle incorrect.";
    header("Location: index.php");
    exit;
}