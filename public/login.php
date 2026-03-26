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
    }

    if ($foundUser['role'] === 'eleve') {
        $_SESSION['user']['pilote_id'] = $foundUser['pilote_id'] ?? null;
    }

    header("Location: index.php");
    exit;

} else {

    $_SESSION['error'] = "Email, mot de passe ou rôle incorrect.";
    header("Location: index.php");
    exit;
}