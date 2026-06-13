<?php
$pageTitle = 'Inscription';
$extraCss = 'auth.css';

require_once __DIR__ . '/../includes/auth.php';
authStart();

if (authCheck()) {
    header('Location: /index.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $email    = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['password_confirm'] ?? '';
    $role     = $_POST['role'] ?? 'joueur';

    if ($password !== $confirm) {
        $error = 'Les codes d\'acces ne correspondent pas.';
    } else {
        $result = authRegister($username, $email, $password, $role);
        if ($result['ok']) {
            $success = 'Acces accorde. Redirection vers l\'identification...';
            header('Refresh: 2; URL=/php/auth/login.php');
        } else {
            $error = $result['error'];
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="auth-container">
    <div class="auth-panel">

        <div class="auth-terminal">
            <div class="line"><span class="prompt">&gt;</span> ISS-G5E CREW REGISTRATION MODULE</div>
            <div class="line"><span class="prompt">&gt;</span> Initialisation du protocole d'enregistrement...</div>
            <div class="line"><span class="ok">[OK]</span> Base de donnees equipage connectee</div>
            <div class="line"><span class="warn">[REG]</span> Nouveau profil requis</div>
            <div class="line"><span class="prompt">&gt;</span> Saisir les informations<span class="cursor-blink"></span></div>
        </div>

        <div class="iss-panel auth-form-panel">
            <div class="panel-header">
                <div class="panel-header-marker"></div>
                <h1 class="panel-title">Enregistrement</h1>
                <span class="panel-subtitle">NEW CREW</span>
            </div>

            <?php if ($error): ?>
                <div class="iss-alert iss-alert-error" role="alert">&gt; ERREUR : <?= h($error) ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="iss-alert iss-alert-success" role="status">&gt; <?= h($success) ?></div>
            <?php endif; ?>

            <form method="POST" action="" autocomplete="off">
                <div class="iss-field">
                    <label class="iss-label" for="username">Identifiant equipage</label>
                    <input class="iss-input" type="text" id="username" name="username"
                           placeholder="call_sign" required minlength="3" maxlength="50" aria-required="true"
                           value="<?= isset($username) ? h($username) : '' ?>">
                </div>

                <div class="iss-field">
                    <label class="iss-label" for="email">Canal de communication</label>
                    <input class="iss-input" type="email" id="email" name="email"
                           placeholder="equipage@iss.space" required aria-required="true"
                           value="<?= isset($email) ? h($email) : '' ?>">
                </div>

                <div class="iss-field">
                    <label class="iss-label" for="role">Niveau d'habilitation</label>
                    <select class="iss-select" id="role" name="role">
                        <option value="joueur" <?= (isset($role) && $role === 'joueur') ? 'selected' : '' ?>>
                            Membre d'equipage (Joueur)
                        </option>
                        <option value="game_master" <?= (isset($role) && $role === 'game_master') ? 'selected' : '' ?>>
                            Directeur de mission (Game Master)
                        </option>
                    </select>
                </div>

                <div class="iss-field">
                    <label class="iss-label" for="password">Code acces</label>
                    <input class="iss-input" type="password" id="password" name="password"
                           placeholder="6 caracteres minimum" required minlength="6" aria-required="true">
                </div>

                <div class="iss-field">
                    <label class="iss-label" for="password_confirm">Confirmer le code</label>
                    <input class="iss-input" type="password" id="password_confirm" name="password_confirm"
                           placeholder="Repeter le code" required minlength="6" aria-required="true">
                </div>

                <button type="submit" class="iss-btn iss-btn-primary auth-submit">
                    Demander l'acces
                </button>
            </form>

            <div class="auth-divider">OU</div>

            <div class="auth-footer">
                Deja enregistre ?
                <a href="/php/auth/login.php">S'identifier</a>
            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
