<?php
session_start();

require_once __DIR__ . '/../vendor/autoload.php';

use Twig\Loader\FilesystemLoader;
use Twig\Environment;

$loader = new FilesystemLoader(__DIR__ . '/../templates');
$twig = new Environment($loader);

$user = $_SESSION['user'] ?? null;

// Vérifier si l'utilisateur est connecté et a le rôle approprié (pilote ou eleve)
if (!$user || !in_array($user['role'], ['pilote', 'eleve'])) {
    header("Location: index.php");
    exit;
}

// Charger la base de données depuis le fichier JSON
$usersFile = __DIR__ . '/users.json';
$users = json_decode(file_get_contents($usersFile), true);

// Filtrer les élèves selon le rôle
if ($user['role'] === 'pilote') {
    $allAccounts = array_filter($users, function($u) use ($user) { 
        return $u['role'] === 'eleve' || ($u['role'] === 'pilote' && $u['email'] === $user['email']); 
    });
    $own = null;
    $students = [];
    foreach ($allAccounts as $acc) {
        if ($acc['role'] === 'pilote' && $acc['email'] === $user['email']) {
            $own = $acc;
        } else {
            $students[] = $acc;
        }
    }
    $canDelete = true;
} else { // eleve
    $own = array_filter($users, function($u) use ($user) { 
        return $u['role'] === 'eleve' && $u['email'] === $user['email']; 
    });
    $own = $own ? $own[array_key_first($own)] : null;
    $students = [];
    $canDelete = false;
}

// Gestion des actions
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        if ($action === 'modifier' && isset($_POST['id'])) {
            $id = (int)$_POST['id'];
            $nom = trim($_POST['nom'] ?? '');
            $prenom = trim($_POST['prenom'] ?? '');
            $email = trim($_POST['email'] ?? '');

            // Vérifier les permissions
            $allowed = false;
            if ($user['role'] === 'pilote') {
                $allowed = true;
            } elseif ($user['role'] === 'eleve') {
                // Vérifier que c'est son propre compte
                $allowed = in_array($id, array_column($eleves, 'id'));
            }

            if ($allowed && $nom && $prenom && $email) {
                foreach ($users as &$u) {
                    if ($u['id'] === $id && $u['role'] === 'eleve') {
                        $u['nom'] = $nom;
                        $u['prenom'] = $prenom;
                        $u['email'] = $email;
                        file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT));
                        $message = 'Compte étudiant modifié avec succès.';
                        // Mettre à jour la session si c'est son propre compte
                        if ($user['role'] === 'eleve' && $u['email'] === $user['email']) {
                            $_SESSION['user']['nom'] = $nom;
                            $_SESSION['user']['prenom'] = $prenom;
                            $_SESSION['user']['email'] = $email;
                        }
                        header("Location: parametres.php");
                        exit;
                    }
                }
            } else {
                $message = $allowed ? 'Tous les champs sont requis.' : 'Action non autorisée.';
            }
        } elseif ($action === 'supprimer' && isset($_POST['id']) && $canDelete) {
            $id = (int)$_POST['id'];
            $users = array_filter($users, function($u) use ($id) {
                return !($u['id'] === $id && $u['role'] === 'eleve');
            });
            file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT));
            $eleves = array_filter($users, function($u) { return $u['role'] === 'eleve'; });
            $message = 'Compte étudiant supprimé avec succès.';
        }
    }
}

// Préparer les données pour Twig
echo $twig->render('parametres.twig', [
    'user' => $user,
    'own' => $own,
    'students' => $students,
    'message' => $message,
    'canDelete' => $canDelete
]);


?>