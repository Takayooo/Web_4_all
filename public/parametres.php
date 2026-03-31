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

if (!$user || !in_array($user['role'], ['administrateur', 'pilote', 'eleve', 'entreprise'], true)) {
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
$searchAdmins = trim($_GET['search_admins'] ?? '');

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

$own = $user;
$students = [];
$enterprises = [];
$pilots = [];
$admins = [];
$pilotCompanyRatings = [];
$allUsers = get_all_users();

if ($user['role'] === 'administrateur') {
    $students = array_values(array_filter(
        $allUsers,
        fn(array $account): bool => $account['role'] === 'eleve' && containsSearch($account, $searchStudents, ['nom', 'prenom', 'email'])
    ));

    $enterprises = array_values(array_filter(
        $allUsers,
        fn(array $account): bool => $account['role'] === 'entreprise' && containsSearch($account, $searchEnterprises, ['nom', 'secteur', 'ville', 'email'])
    ));

    $pilots = array_values(array_filter(
        $allUsers,
        fn(array $account): bool => $account['role'] === 'pilote' && containsSearch($account, $searchPilots, ['nom', 'prenom', 'email'])
    ));

    $admins = array_values(array_filter(
        $allUsers,
        fn(array $account): bool => $account['role'] === 'administrateur' && containsSearch($account, $searchAdmins, ['nom', 'prenom', 'email'])
    ));
} elseif ($user['role'] === 'pilote') {
    $students = array_values(array_filter(
        get_students_by_pilot_user_id((int) $user['id']),
        fn(array $account): bool => containsSearch($account, $searchStudents, ['nom', 'prenom', 'email'])
    ));

    $enterprises = array_values(array_filter(
        $allUsers,
        fn(array $account): bool => $account['role'] === 'entreprise' && containsSearch($account, $searchEnterprises, ['nom', 'secteur', 'ville', 'email'])
    ));

    $pilotCompanyRatings = get_pilot_company_evaluations((int) $user['id']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'modifier' && isset($_POST['id'])) {
        $id = (int) $_POST['id'];
        $targetUser = get_user_by_id($id);
        $nom = trim($_POST['nom'] ?? '');
        $email = trim($_POST['email'] ?? '');

        $allowed = false;
        if ($user['role'] === 'administrateur') {
            $allowed = true;
        } elseif ($own['id'] === $id) {
            $allowed = true;
        } elseif ($user['role'] === 'pilote' && $targetUser && $targetUser['role'] === 'eleve') {
            $studentIds = array_map(
                static fn(array $student): int => (int) $student['id'],
                get_students_by_pilot_user_id((int) $user['id'])
            );
            $allowed = in_array($id, $studentIds, true);
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
                if ($own['id'] === $id) {
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
    } elseif ($action === 'supprimer' && isset($_POST['id'])) {
        $id = (int) $_POST['id'];
        $targetUser = get_user_by_id($id);

        if (!$targetUser) {
            $message = 'Action non autorisee.';
            $messageType = 'danger';
        } elseif ((int) $user['id'] === $id) {
            $message = 'Vous ne pouvez pas supprimer votre propre compte.';
            $messageType = 'danger';
        } elseif ($user['role'] === 'administrateur') {
            if (!in_array($targetUser['role'], ['eleve', 'entreprise', 'pilote'], true)) {
                $message = 'Action non autorisee.';
                $messageType = 'danger';
            } elseif (delete_account_by_admin($id)) {
                $_SESSION['settings_message'] = 'Compte supprime avec succes.';
                $_SESSION['settings_message_type'] = 'success';
                header('Location: parametres.php');
                exit;
            } else {
                $message = 'Impossible de supprimer ce compte.';
                $messageType = 'danger';
            }
        } elseif ($user['role'] === 'pilote') {
            if ($targetUser['role'] !== 'eleve') {
                $message = 'Action non autorisee.';
                $messageType = 'danger';
            } elseif (delete_student_by_pilot((int) $user['id'], $id)) {
                $_SESSION['settings_message'] = 'Eleve supprime avec succes.';
                $_SESSION['settings_message_type'] = 'success';
                header('Location: parametres.php');
                exit;
            } else {
                $message = 'Vous ne pouvez supprimer que vos propres eleves.';
                $messageType = 'danger';
            }
        } else {
            $message = 'Action non autorisee.';
            $messageType = 'danger';
        }
    } elseif ($action === 'modifier_note' && isset($_POST['id'])) {
        $id = (int) $_POST['id'];
        $targetUser = get_user_by_id($id);
        $note = filter_input(
            INPUT_POST,
            'note',
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 0, 'max_range' => 5]]
        );

        if ($user['role'] !== 'pilote' || !$targetUser || $targetUser['role'] !== 'entreprise') {
            $message = 'Vous n\'êtes pas autorisé à modifier cette note.';
            $messageType = 'danger';
        } elseif ($note === false) {
            $message = 'La note doit être comprise entre 0 et 5.';
            $messageType = 'danger';
        } elseif (upsert_company_evaluation((int) $user['id'], $id, $note, trim($_POST['commentaire'] ?? ''))) {
            $_SESSION['settings_message'] = 'Note de l\'entreprise mise à jour avec succès.';
            $_SESSION['settings_message_type'] = 'success';
            header('Location: parametres.php');
            exit;
        } else {
            $message = 'Impossible d\'enregistrer la note de cette entreprise.';
            $messageType = 'danger';
        }
    }
}

echo $twig->render('parametres.twig', [
    'user' => $user,
    'own' => $own,
    'students' => $students,
    'enterprises' => $enterprises,
    'pilots' => $pilots,
    'admins' => $admins,
    'pilotCompanyRatings' => $pilotCompanyRatings,
    'searchStudents' => $searchStudents,
    'searchEnterprises' => $searchEnterprises,
    'searchPilots' => $searchPilots,
    'searchAdmins' => $searchAdmins,
    'message' => $message,
    'messageType' => $messageType,
    'current_page' => 'parametres'
]);
