<?php
session_start();

// Si déjà connecté, rediriger vers l'accueil
if (isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

$usersFile = __DIR__ . '/users.json';
$users = json_decode(file_get_contents($usersFile), true);
$errors = [];
$success = '';

function findUserByEmail($users, $email) {
    foreach ($users as $u) {
        if (strcasecmp($u['email'], $email) === 0) {
            return $u;
        }
    }
    return null;
}

function findPilotByEmail($users, $email) {
    foreach ($users as $u) {
        if (isset($u['role']) && $u['role'] === 'pilote' && strcasecmp($u['email'], $email) === 0) {
            return $u;
        }
    }
    return null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role = $_POST['role'] ?? '';
    $role = in_array($role, ['eleve', 'pilote', 'entreprise']) ? $role : '';

    if (!$role) {
        $errors[] = 'Veuillez choisir un type de compte valide.';
    }

    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Email invalide.";
    }

    if (!$password || strlen($password) < 4) {
        $errors[] = "Le mot de passe doit contenir au moins 4 caractères.";
    }

    if (findUserByEmail($users, $email)) {
        $errors[] = "Un compte existe déjà avec cet email.";
    }

    $newUser = [];
    $now = date('Y-m-d H:i:s');

    if ($role === 'eleve') {
        $nom = trim($_POST['nom'] ?? '');
        $prenom = trim($_POST['prenom'] ?? '');
        $piloteEmail = trim($_POST['pilote_email'] ?? '');

        if (!$nom || !$prenom) {
            $errors[] = "Nom et prénom sont obligatoires pour un élève.";
        }

        if (!$piloteEmail || !filter_var($piloteEmail, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Email du pilote invalide.";
        }

        $pilote = findPilotByEmail($users, $piloteEmail);
        if (!$pilote) {
            $errors[] = "Aucun pilote trouvé pour cet email.";
        }

        if (empty($errors)) {
            $newId = max(array_column($users, 'id')) + 1;
            $newUser = [
                'id' => $newId,
                'email' => $email,
                'password' => $password,
                'role' => 'eleve',
                'nom' => $nom,
                'prenom' => $prenom,
                'pilote_id' => $pilote['id'],
                'created_at' => $now
            ];

            foreach ($users as &$u) {
                if ($u['id'] === $pilote['id']) {
                    if (!isset($u['eleves']) || !is_array($u['eleves'])) {
                        $u['eleves'] = [];
                    }
                    $u['eleves'][] = $newId;
                    break;
                }
            }
            unset($u);

            $users[] = $newUser;
            if (!is_writable($usersFile)) {
                $errors[] = 'Erreur serveur : impossible d’écrire dans users.json (permission refusée).';
            } else {
                $written = file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                if ($written === false) {
                    $errors[] = 'Erreur serveur : échec de sauvegarde users.json.';
                } else {
                    $_SESSION['success'] = 'Compte élève créé avec succès. Vous pouvez maintenant vous connecter.';
                    header('Location: index.php');
                    exit;
                }
            }
        }

    } elseif ($role === 'pilote') {
        $nom = trim($_POST['nom'] ?? '');
        $prenom = trim($_POST['prenom'] ?? '');

        if (!$nom || !$prenom) {
            $errors[] = "Nom et prénom sont obligatoires pour un pilote.";
        }

        if (empty($errors)) {
            $newId = max(array_column($users, 'id')) + 1;
            $newUser = [
                'id' => $newId,
                'email' => $email,
                'password' => $password,
                'role' => 'pilote',
                'nom' => $nom,
                'prenom' => $prenom,
                'eleves' => [],
                'created_at' => $now
            ];

            $users[] = $newUser;
            if (!is_writable($usersFile)) {
                $errors[] = 'Erreur serveur : impossible d’écrire dans users.json (permission refusée).';
            } else {
                $written = file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                if ($written === false) {
                    $errors[] = 'Erreur serveur : échec de sauvegarde users.json.';
                } else {
                    $_SESSION['success'] = 'Compte pilote créé avec succès. Vous pouvez maintenant vous connecter.';
                    header('Location: index.php');
                    exit;
                }
            }
        }

    } elseif ($role === 'entreprise') {
        $companyName = trim($_POST['company_name'] ?? '');
        $secteur = trim($_POST['secteur'] ?? '');
        $ville = trim($_POST['ville'] ?? '');

        if (!$companyName || !$secteur || !$ville) {
            $errors[] = "Nom de l'entreprise, secteur et ville sont obligatoires pour une entreprise.";
        }

        if (empty($errors)) {
            $newId = max(array_column($users, 'id')) + 1;
            $newUser = [
                'id' => $newId,
                'email' => $email,
                'password' => $password,
                'role' => 'entreprise',
                'nom' => $companyName,
                'prenom' => '',
                'entreprise_id' => $newId,
                'secteur' => $secteur,
                'ville' => $ville,
                'note' => 0,
                'created_at' => $now
            ];

            $users[] = $newUser;
            if (!is_writable($usersFile)) {
                $errors[] = 'Erreur serveur : impossible d’écrire dans users.json (permission refusée).';
            } else {
                $written = file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                if ($written === false) {
                    $errors[] = 'Erreur serveur : échec de sauvegarde users.json.';
                } else {
                    $_SESSION['success'] = 'Compte entreprise créé avec succès. Vous pouvez maintenant vous connecter.';
                    header('Location: index.php');
                    exit;
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
    <title>Créer un compte | Web4All</title>
    <link rel="stylesheet" href="style.css?v=18">
</head>
<body>
<?php include 'header.php'; ?>

<section class="container" style="padding: 40px 0;">
    <h2>Créer un compte</h2>

    <?php if ($success): ?>
        <div style="margin-bottom:20px;padding:15px;border:1px solid #0d0c6e;color:#0d0c6e;border-radius:8px;"> <?= e($success) ?> </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div style="margin-bottom:20px;padding:15px;border:1px solid #dc3545;color:#dc3545;border-radius:8px;">
            <ul>
            <?php foreach ($errors as $err): ?>
                <li><?= e($err) ?></li>
            <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="register-role-switch" style="margin-bottom:20px;">
        <button type="button" class="register-role-btn active" data-role="eleve">Élève</button>
        <button type="button" class="register-role-btn" data-role="pilote">Pilote</button>
        <button type="button" class="register-role-btn" data-role="entreprise">Entreprise</button>
    </div>

    <form method="POST" action="create_account.php" class="login-form" id="create-account-form">
        <input type="hidden" name="role" id="register-role-input" value="eleve"
               data-validate="role">

        <div class="form-section" data-role="eleve">
            <input type="text" name="nom" placeholder="Nom" required>
            <input type="text" name="prenom" placeholder="Prénom" required>
            <input type="email" name="pilote_email" placeholder="Email du pilote" required>
        </div>

        <div class="form-section" data-role="pilote" style="display:none;">
            <input type="text" name="nom" placeholder="Nom" required>
            <input type="text" name="prenom" placeholder="Prénom" required>
        </div>

        <div class="form-section" data-role="entreprise" style="display:none;">
            <input type="text" name="company_name" placeholder="Nom de l'entreprise" required>
            <input type="text" name="secteur" placeholder="Secteur" required>
            <input type="text" name="ville" placeholder="Ville" required>
        </div>

        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Mot de passe" required>

        <button type="submit" class="create-account-button">Créer mon compte</button>
    </form>

    <p><a href="#" id="open-login-inline" style="margin-top:15px;color:#0d0c6e;text-decoration:underline;">Déjà membre ? Se connecter</a></p>
</section>

<script>
    const registerRoleSwitch = document.querySelector('.register-role-switch');
    const roleButtons = registerRoleSwitch.querySelectorAll('.register-role-btn');
    const roleInput = document.getElementById('register-role-input');
    const sections = document.querySelectorAll('.form-section');

    function updateFormFields(selectedRole) {
        sections.forEach(sec => {
            const isActive = sec.dataset.role === selectedRole;
            sec.style.display = isActive ? 'block' : 'none';
            const inputs = sec.querySelectorAll('input');
            inputs.forEach(input => {
                input.disabled = !isActive;
                input.required = isActive;
            });
        });
    }

    roleButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            roleButtons.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            roleInput.value = btn.dataset.role;
            updateFormFields(btn.dataset.role);
        });
    });

    updateFormFields(roleInput.value);

    document.getElementById('open-login-inline').addEventListener('click', (e) => {
        e.preventDefault();
        window.location.href = 'index.php';
    });
</script>

</body>

<?php include 'footer.php'; ?>

</html>