<?php
$pageTitle = 'Connexion';
$extraCss = 'auth.css';

require_once __DIR__ . '/../includes/auth.php';
authStart();

if (authCheck()) {
    header('Location: /index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $result = authLogin($username, $password);
    if ($result['ok']) {
        header('Location: /index.php');
        exit;
    }
    $error = $result['error'];
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="auth-container">
    <div class="auth-panel">

        <div class="auth-terminal">
            <div class="line"><span class="prompt">&gt;</span> ISS-G5E CARGO BAY TERMINAL v3.7.1</div>
            <div class="line"><span class="prompt">&gt;</span> Connexion au systeme de controle...</div>
            <div class="line"><span class="ok">[OK]</span> Liaison Terre-Station etablie</div>
            <div class="line"><span class="warn">[AUTH]</span> Identification requise</div>
            <div class="line"><span class="prompt">&gt;</span> En attente des identifiants<span class="cursor-blink"></span></div>
        </div>

        <div class="iss-panel auth-form-panel">
            <div class="panel-header">
                <div class="panel-header-marker"></div>
                <h1 class="panel-title">Identification</h1>
                <span class="panel-subtitle">SEC-LEVEL 2</span>
            </div>

            <?php if ($error): ?>
                <div class="iss-alert iss-alert-error" role="alert">&gt; ERREUR : <?= h($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="" autocomplete="off">
                <div class="iss-field">
                    <label class="iss-label" for="username">Identifiant equipage</label>
                    <input class="iss-input" type="text" id="username" name="username"
                           placeholder="call_sign" required aria-required="true"
                           value="<?= isset($username) ? h($username) : '' ?>">
                </div>

                <div class="iss-field">
                    <label class="iss-label" for="password">Code acces</label>
                    <input class="iss-input" type="password" id="password" name="password"
                           placeholder="••••••••" required aria-required="true">
                </div>

                <button type="submit" class="iss-btn iss-btn-primary auth-submit">
                    Authentification
                </button>
            </form>

            <div class="auth-divider">OU</div>

            <div class="auth-footer">
                Nouveau membre d'equipage ?
                <a href="/php/auth/register.php">Demander un acces</a>
            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
