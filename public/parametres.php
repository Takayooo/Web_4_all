<?php
session_start();

require_once __DIR__ . '/../vendor/autoload.php';

use Twig\Loader\FilesystemLoader;
use Twig\Environment;

$loader = new FilesystemLoader(__DIR__ . '/../templates');
$twig = new Environment($loader);

$user = $_SESSION['user'] ?? null;
$message = $_SESSION['settings_message'] ?? '';
$messageType = $_SESSION['settings_message_type'] ?? 'success';
unset($_SESSION['settings_message'], $_SESSION['settings_message_type']);

function saveUsers(string $usersFile, array $users): bool
{
    $json = json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

    if ($json === false) {
        return false;
    }

    return file_put_contents($usersFile, $json, LOCK_EX) !== false;
}

// Vérifier si l'utilisateur est connecté et a le rôle approprié (pilote, eleve ou entreprise)
if (!$user || !in_array($user['role'], ['pilote', 'eleve', 'entreprise'])) {
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
        } elseif (($acc['pilote_id'] ?? null) === $user['id']) {
            $students[] = $acc;
        }
    }
    $canDelete = true;
} elseif ($user['role'] === 'entreprise') {
    $own = array_filter($users, function($u) use ($user) { 
        return $u['role'] === 'entreprise' && $u['email'] === $user['email']; 
    });
    $own = $own ? $own[array_key_first($own)] : null;
    $students = [];
    $canDelete = false;
} else { // eleve
    $own = array_filter($users, function($u) use ($user) { 
        return $u['role'] === 'eleve' && $u['email'] === $user['email']; 
    });
    $own = $own ? $own[array_key_first($own)] : null;
    $students = [];
    $canDelete = false;
}

// Récupérer les entreprises si l'utilisateur est pilote
$enterprises = [];
$pilots = [];
if ($user['role'] === 'pilote') {
    $enterprises = array_filter($users, function($u) { 
        return $u['role'] === 'entreprise'; 
    });
    $enterprises = array_values($enterprises);

    $pilots = array_filter($users, function($u) use ($user) {
        return $u['role'] === 'pilote' && $u['id'] !== $user['id'];
    });
    $pilots = array_values($pilots);
}

// Gestion des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        if ($action === 'modifier' && isset($_POST['id'])) {
            $id = (int)$_POST['id'];
            $nom = trim($_POST['nom'] ?? '');
            $email = trim($_POST['email'] ?? '');

            // Vérifier les permissions
            $allowed = false;
            if ($user['role'] === 'pilote') {
                $allowed = true;
            } elseif ($user['role'] === 'eleve') {
                // Vérifier que c'est son propre compte
                $allowed = ($own && $own['id'] === $id);
            } elseif ($user['role'] === 'entreprise') {
                // Vérifier que c'est son propre compte
                $allowed = ($own && $own['id'] === $id);
            }

            if ($allowed && $nom && $email) {
                foreach ($users as &$u) {
                    if ($u['id'] === $id) {
                        if ($u['role'] === 'eleve') {
                            $prenom = trim($_POST['prenom'] ?? '');
                            if ($prenom) {
                                $u['nom'] = $nom;
                                $u['prenom'] = $prenom;
                                $u['email'] = $email;
                            }
                        } elseif ($u['role'] === 'entreprise') {
                            $secteur = trim($_POST['secteur'] ?? '');
                            $ville = trim($_POST['ville'] ?? '');
                            if ($secteur && $ville) {
                                $u['nom'] = $nom;
                                $u['secteur'] = $secteur;
                                $u['ville'] = $ville;
                                $u['email'] = $email;
                            }
                        }
                        if (!saveUsers($usersFile, $users)) {
                            $message = 'Impossible d\'enregistrer la modification dans users.json.';
                            $messageType = 'danger';
                            break;
                        }
                        // Mettre à jour la session si c'est son propre compte
                        if ($u['email'] === $user['email']) {
                            $_SESSION['user']['nom'] = $nom;
                            $_SESSION['user']['email'] = $email;
                            if ($u['role'] === 'entreprise') {
                                $_SESSION['user']['secteur'] = $secteur;
                                $_SESSION['user']['ville'] = $ville;
                            } elseif ($u['role'] === 'eleve') {
                                $_SESSION['user']['prenom'] = $prenom;
                            }
                        }
                        $_SESSION['settings_message'] = 'Compte modifié avec succès.';
                        $_SESSION['settings_message_type'] = 'success';
                        header("Location: parametres.php");
                        exit;
                    }
                }
            } else {
                $message = $allowed ? 'Tous les champs sont requis.' : 'Action non autorisée.';
                $messageType = 'danger';
            }
        } elseif ($action === 'supprimer' && isset($_POST['id']) && $canDelete) {
            $id = (int)$_POST['id'];
            $filteredUsers = array_values(array_filter($users, function($u) use ($id) {
                return !($u['id'] === $id && $u['role'] === 'eleve');
            }));

            if (count($filteredUsers) === count($users)) {
                $message = 'Compte étudiant introuvable.';
                $messageType = 'danger';
            } elseif (!saveUsers($usersFile, $filteredUsers)) {
                $message = 'Impossible de supprimer le compte étudiant dans users.json.';
                $messageType = 'danger';
            } else {
                $_SESSION['settings_message'] = 'Compte étudiant supprimé avec succès.';
                $_SESSION['settings_message_type'] = 'success';
                header("Location: parametres.php");
                exit;
            }
        } elseif ($action === 'supprimer_entreprise' && isset($_POST['id'])) {
            $id = (int)$_POST['id'];
            $allowed = false;
            
            // Vérifier les permissions
            if ($user['role'] === 'pilote') {
                $allowed = true;
            } elseif ($user['role'] === 'entreprise' && $own && $own['id'] === $id) {
                $allowed = true;
            }
            
            if ($allowed) {
                $filteredUsers = array_values(array_filter($users, function($u) use ($id) {
                    return !($u['id'] === $id && $u['role'] === 'entreprise');
                }));

                if (count($filteredUsers) === count($users)) {
                    $message = 'Entreprise introuvable.';
                    $messageType = 'danger';
                } elseif (!saveUsers($usersFile, $filteredUsers)) {
                    $message = 'Impossible de supprimer l\'entreprise dans users.json.';
                    $messageType = 'danger';
                } elseif ($user['role'] === 'entreprise' && $own && $own['id'] === $id) {
                    session_destroy();
                    header('Location: index.php');
                    exit;
                } else {
                    $_SESSION['settings_message'] = 'Entreprise supprimée avec succès.';
                    $_SESSION['settings_message_type'] = 'success';
                    header("Location: parametres.php");
                    exit;
                }
            } else {
                $message = 'Vous n\'êtes pas autorisé à supprimer cette entreprise (rôle: ' . $user['role'] . ').';
                $messageType = 'danger';
            }
        } elseif ($action === 'supprimer_pilote' && isset($_POST['id'])) {
            $id = (int)$_POST['id'];
            $allowed = false;

            if ($user['role'] === 'pilote') {
                $allowed = true;
            }

            if ($allowed) {
                $studentsToDelete = [];
                foreach ($users as $account) {
                    if ($account['role'] === 'eleve' && (($account['pilote_id'] ?? null) === $id)) {
                        $studentsToDelete[] = $account['id'];
                    }
                }

                $filteredUsers = array_values(array_filter($users, function($account) use ($id, $studentsToDelete) {
                    if ($account['role'] === 'pilote' && $account['id'] === $id) {
                        return false;
                    }

                    if ($account['role'] === 'eleve' && in_array($account['id'], $studentsToDelete, true)) {
                        return false;
                    }

                    return true;
                }));

                if (count($filteredUsers) === count($users)) {
                    $message = 'Pilote introuvable.';
                    $messageType = 'danger';
                } elseif (!saveUsers($usersFile, $filteredUsers)) {
                    $message = 'Impossible de supprimer le pilote dans users.json.';
                    $messageType = 'danger';
                } elseif ($own && $own['id'] === $id) {
                    session_destroy();
                    header('Location: index.php');
                    exit;
                } else {
                    $deletedStudentsCount = count($studentsToDelete);
                    $_SESSION['settings_message'] = $deletedStudentsCount > 0
                        ? 'Pilote supprimé avec succès. ' . $deletedStudentsCount . ' étudiant(s) lié(s) ont aussi été supprimé(s).'
                        : 'Pilote supprimé avec succès.';
                    $_SESSION['settings_message_type'] = 'success';
                    header("Location: parametres.php");
                    exit;
                }
            } else {
                $message = 'Vous n\'êtes pas autorisé à supprimer ce pilote.';
                $messageType = 'danger';
            }
        }
    }
}

// Préparer les données pour Twig
echo $twig->render('parametres.twig', [
    'user' => $user,
    'own' => $own,
    'students' => $students,
    'enterprises' => $enterprises,
    'pilots' => $pilots,
    'message' => $message,
    'messageType' => $messageType,
    'canDelete' => $canDelete,
    'current_page' => 'parametres'
]);


?>