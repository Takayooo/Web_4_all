<?php

session_start();

/* ------------------------
   "FAUSSE BASE DE DONNÉES"
------------------------ */

$users = [
    [
        'email' => 'eleve@test.com',
        'password' => '1234',
        'role' => 'eleve',
        'nom' => 'Eleve Test'
    ],
    [
        'email' => 'pilote@test.com',
        'password' => '1234',
        'role' => 'pilote',
        'nom' => 'Pilote Test'
    ],
    [
        'email' => 'entreprise@test.com',
        'password' => '1234',
        'role' => 'entreprise',
        'nom' => 'Entreprise Test'
    ]
];

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
        'role' => $foundUser['role']
    ];

    header("Location: index.php");
    exit;

} else {

    $_SESSION['error'] = "Email, mot de passe ou rôle incorrect.";
    header("Location: index.php");
    exit;
}