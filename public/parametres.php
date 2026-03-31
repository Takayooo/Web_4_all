<?php
session_start();

require_once __DIR__ . '/../vendor/autoload.php';
require_once 'data_helpers.php';

use Twig\Loader\FilesystemLoader;
use Twig\Environment;

$loader = new FilesystemLoader(__DIR__ . '/../templates');
$twig = new Environment($loader);

$user = $_SESSION['user'] ?? null;
$message = $_SESSION['settings_message'] ?? '';
$messageType = $_SESSION['settings_message_type'] ?? 'success';
unset($_SESSION['settings_message'], $_SESSION['settings_message_type']);

if (!$user || !in_array($user['role'], ['pilote', 'eleve', 'entreprise'], true)) {
    header('Location: index.php');
    exit;
}

$user = get_user_by_id((int) $user['id']);
if (!$user) {
    session_destroy();
    header('Location: index.php');
    exit;
}

$_SESSION['user'] = $user;

$searchStudents = trim($_GET['search_students'] ?? '');
$searchEnterprises = trim($_GET['search_enterprises'] ?? '');
$searchPilots = trim($_GET['search_pilots'] ?? '');

function containsSearch(array $account, string $query, array $fields): bool
{
    if ($query === '') {
        return true;
    }

    $needle = mb_strtolower($query, 'UTF-8');
    foreach ($fields as $field) {
        $value = (string) ($account[$field] ?? '');
        if ($value !== '' && mb_strpos(mb_strtolower($value, 'UTF-8'), $needle) !== false) {
            return true;
        }
    }

    return false;
}

$own = get_user_by_id((int) $user['id']);
$students = [];
$enterprises = [];
$pilots = [];
$canDelete = $user['role'] === 'pilote';

if ($user['role'] === 'pilote') {
    $students = array_values(array_filter(
        get_students_by_pilot_user_id((int) $user['id']),
        fn(array $account): bool => containsSearch($account, $searchStudents, ['nom', 'prenom', 'email'])
    ));

    $enterprises = array_values(array_filter(
        get_all_enterprises(),
        fn(array $account): bool => containsSearch($account, $searchEnterprises, ['nom', 'secteur', 'ville', 'email'])
    ));

    $pilots = array_values(array_filter(
        get_all_pilots(),
        fn(array $account): bool => $account['id'] !== $user['id'] && containsSearch($account, $searchPilots, ['nom', 'prenom', 'email'])
    ));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'modifier' && isset($_POST['id'])) {
        $id = (int) $_POST['id'];
        $targetUser = get_user_by_id($id);
        $nom = trim($_POST['nom'] ?? '');
        $email = trim($_POST['email'] ?? '');

        $allowed = false;
        if ($user['role'] === 'pilote') {
            $allowed = true;
        } elseif ($own && $own['id'] === $id) {
            $allowed = true;
        }

        if (!$allowed || !$targetUser) {
            $message = 'Action non autorisée.';
            $messageType = 'danger';
        } elseif (!$nom || !$email) {
            $message = 'Tous les champs sont requis.';
            $messageType = 'danger';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = 'Email invalide.';
            $messageType = 'danger';
        } elseif (email_exists($email, $id)) {
            $message = 'Un autre compte utilise déjà cet email.';
            $messageType = 'danger';
        } else {
            $updated = false;

            if ($targetUser['role'] === 'entreprise') {
                $secteur = trim($_POST['secteur'] ?? '');
                $ville = trim($_POST['ville'] ?? '');
                if ($secteur && $ville) {
                    $updated = update_company_account($id, $nom, $secteur, $ville, $email);
                }
            } else {
                $prenom = trim($_POST['prenom'] ?? '');
                if ($prenom) {
                    $updated = update_basic_account($id, $nom, $prenom, $email);
                }
            }

            if ($updated) {
                if ($own && $own['id'] === $id) {
                    $_SESSION['user'] = get_user_by_id($id) ?? $_SESSION['user'];
                }
                $_SESSION['settings_message'] = 'Compte modifié avec succès.';
                $_SESSION['settings_message_type'] = 'success';
                header('Location: parametres.php');
                exit;
            }

            $message = 'Impossible d\'enregistrer la modification dans la base de données.';
            $messageType = 'danger';
        }
    } elseif ($action === 'supprimer' && isset($_POST['id']) && $canDelete) {
        $id = (int) $_POST['id'];
        if (delete_student_account($id)) {
            $_SESSION['settings_message'] = 'Compte étudiant supprimé avec succès.';
            $_SESSION['settings_message_type'] = 'success';
            header('Location: parametres.php');
            exit;
        }
        $message = 'Compte étudiant introuvable ou suppression impossible.';
        $messageType = 'danger';
    } elseif ($action === 'supprimer_entreprise' && isset($_POST['id'])) {
        $id = (int) $_POST['id'];
        $allowed = $user['role'] === 'pilote' || ($user['role'] === 'entreprise' && $own && $own['id'] === $id);

        if (!$allowed) {
            $message = 'Vous n\'êtes pas autorisé à supprimer cette entreprise.';
            $messageType = 'danger';
        } elseif (delete_company_account($id)) {
            if ($user['role'] === 'entreprise' && $own && $own['id'] === $id) {
                session_destroy();
                header('Location: index.php');
                exit;
            }

            $_SESSION['settings_message'] = 'Entreprise supprimée avec succès.';
            $_SESSION['settings_message_type'] = 'success';
            header('Location: parametres.php');
            exit;
        } else {
            $message = 'Entreprise introuvable ou suppression impossible.';
            $messageType = 'danger';
        }
    } elseif ($action === 'supprimer_pilote' && isset($_POST['id'])) {
        $id = (int) $_POST['id'];

        if ($user['role'] !== 'pilote') {
            $message = 'Vous n\'êtes pas autorisé à supprimer ce pilote.';
            $messageType = 'danger';
        } else {
            $deletedStudentsCount = delete_pilot_account($id);
            if ($deletedStudentsCount === false) {
                $message = 'Pilote introuvable ou suppression impossible.';
                $messageType = 'danger';
            } elseif ($own && $own['id'] === $id) {
                session_destroy();
                header('Location: index.php');
                exit;
            } else {
                $_SESSION['settings_message'] = $deletedStudentsCount > 0
                    ? 'Pilote supprimé avec succès. ' . $deletedStudentsCount . ' étudiant(s) lié(s) ont aussi été supprimé(s).'
                    : 'Pilote supprimé avec succès.';
                $_SESSION['settings_message_type'] = 'success';
                header('Location: parametres.php');
                exit;
            }
        }
    }
}

echo $twig->render('parametres.twig', [
    'user' => $user,
    'own' => $own,
    'students' => $students,
    'enterprises' => $enterprises,
    'pilots' => $pilots,
    'searchStudents' => $searchStudents,
    'searchEnterprises' => $searchEnterprises,
    'searchPilots' => $searchPilots,
    'message' => $message,
    'messageType' => $messageType,
    'canDelete' => $canDelete,
    'current_page' => 'parametres'
]);
