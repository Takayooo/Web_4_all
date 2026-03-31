<?php
session_start();
require 'data_helpers.php';

$user = $_SESSION['user'] ?? null;

// Seuls les pilotes et administrateurs peuvent accéder
if (!$user || !in_array($user['role'], ['pilote', 'administrateur'], true)) {
    header('Location: index.php');
    exit;
}

$errors = [];
$success = '';
$current_page = 'create_account';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role = $_POST['role'] ?? '';
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // Valider les rôles autorisés selon l'utilisateur connecté
    $allowedRoles = [];
    if ($user['role'] === 'pilote') {
        $allowedRoles = ['eleve', 'entreprise'];
    } elseif ($user['role'] === 'administrateur') {
        $allowedRoles = ['eleve', 'pilote', 'entreprise'];
    }

    if (!in_array($role, $allowedRoles, true)) {
        $errors[] = 'Type de compte non autorisé.';
    }

    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email invalide.';
    }

    if (!$password || strlen($password) < 4) {
        $errors[] = 'Le mot de passe doit contenir au moins 4 caractères.';
    }

    if (empty($errors) && email_exists($email)) {
        $errors[] = 'Un compte existe déjà avec cet email.';
    }

    if (empty($errors)) {
        if ($role === 'eleve') {
            $nom = trim($_POST['nom'] ?? '');
            $prenom = trim($_POST['prenom'] ?? '');

            if (!$nom || !$prenom) {
                $errors[] = 'Nom et prénom sont obligatoires.';
            }

            if (empty($errors)) {
                if ($user['role'] === 'pilote') {
                    // Pilote : l'étudiant est automatiquement relié à lui
                    if (create_student_account($email, $password, $nom, $prenom, (int) $user['id'])) {
                        $success = 'Compte étudiant créé avec succès (relié à votre compte pilote).';
                    } else {
                        $errors[] = 'Erreur serveur : échec de création du compte étudiant.';
                    }
                } else {
                    // Admin : doit fournir l'email du pilote
                    $piloteEmail = trim($_POST['pilote_email'] ?? '');
                    if (!$piloteEmail || !filter_var($piloteEmail, FILTER_VALIDATE_EMAIL)) {
                        $errors[] = 'Email du pilote invalide.';
                    } else {
                        $pilote = get_pilot_by_email($piloteEmail);
                        if (!$pilote) {
                            $errors[] = 'Aucun pilote trouvé pour cet email.';
                        } elseif (create_student_account($email, $password, $nom, $prenom, $pilote['id'])) {
                            $success = 'Compte étudiant créé avec succès.';
                        } else {
                            $errors[] = 'Erreur serveur : échec de création du compte étudiant.';
                        }
                    }
                }
            }

        } elseif ($role === 'pilote') {
            $nom = trim($_POST['nom'] ?? '');
            $prenom = trim($_POST['prenom'] ?? '');

            if (!$nom || !$prenom) {
                $errors[] = 'Nom et prénom sont obligatoires.';
            }

            if (empty($errors)) {
                if (create_pilot_account($email, $password, $nom, $prenom)) {
                    $success = 'Compte pilote créé avec succès.';
                } else {
                    $errors[] = 'Erreur serveur : échec de création du compte pilote.';
                }
            }

        } elseif ($role === 'entreprise') {
            $companyName = trim($_POST['company_name'] ?? '');
            $secteur = trim($_POST['secteur'] ?? '');
            $ville = trim($_POST['ville'] ?? '');

            if (!$companyName || !$secteur || !$ville) {
                $errors[] = "Nom de l'entreprise, secteur et ville sont obligatoires.";
            }

            if (empty($errors)) {
                if (create_company_account($email, $password, $companyName, $secteur, $ville)) {
                    $success = 'Compte entreprise créé avec succès.';
                } else {
                    $errors[] = 'Erreur serveur : échec de création du compte entreprise.';
                }
            }
        }
    }
}

function e($value) {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Créez votre compte Web4All en tant qu'élève, pilote ou entreprise pour accéder aux offres et au tableau de bord.">
    <title>Créer un compte | Web4All</title>
    <link rel="stylesheet" href="style.css?v=18">
</head>
<body>
<?php include 'header.php'; ?>

<main>

<section class="container create-account-section">
    <h2>Créer un compte</h2>

    <?php if ($success): ?>
        <div class="alert alert-success">
            <?= e($success) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <ul>
            <?php foreach ($errors as $err): ?>
                <li><?= e($err) ?></li>
            <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="register-role-switch">
        <button type="button" class="register-role-btn active" data-role="eleve">Étudiant</button>
        <?php if ($user['role'] === 'administrateur'): ?>
            <button type="button" class="register-role-btn" data-role="pilote">Pilote</button>
        <?php endif; ?>
        <button type="button" class="register-role-btn" data-role="entreprise">Entreprise</button>
    </div>

    <form method="POST" action="create_account.php" class="login-form" id="create-account-form" novalidate>
        <input type="hidden" name="role" id="register-role-input" value="eleve">

        <div class="form-section form-section-eleve" data-role="eleve">
            <input type="text" name="nom" placeholder="Nom" autocomplete="family-name" minlength="2" required>
            <input type="text" name="prenom" placeholder="Prénom" autocomplete="given-name" minlength="2" required>
            <?php if ($user['role'] === 'administrateur'): ?>
                <input type="email" name="pilote_email" placeholder="Email du pilote" autocomplete="email" required>
            <?php endif; ?>
        </div>

        <div class="form-section form-section-pilote" data-role="pilote" style="display:none">
            <input type="text" name="nom" placeholder="Nom" autocomplete="family-name" minlength="2" required disabled>
            <input type="text" name="prenom" placeholder="Prénom" autocomplete="given-name" minlength="2" required disabled>
        </div>

        <div class="form-section form-section-entreprise" data-role="entreprise" style="display:none">
            <input type="text" name="company_name" placeholder="Nom de l'entreprise" autocomplete="organization" minlength="2" required disabled>
            <input type="text" name="secteur" placeholder="Secteur" minlength="2" required disabled>
            <input type="text" name="ville" placeholder="Ville" autocomplete="address-level2" minlength="2" required disabled>
        </div>

        <input type="email" name="email" placeholder="Email du nouveau compte" autocomplete="email" required>
        <input type="password" name="password" placeholder="Mot de passe" autocomplete="new-password" minlength="4" required>

        <button type="submit" class="create-account-button">Créer le compte</button>
    </form>
</section>

<script src="js/account-creation.js"></script>

</main>

<?php include 'footer.php'; ?>

</body>

</html>