<?php
/**
 * ISS Escape Game G5E — Fonctions d'authentification
 */

require_once __DIR__ . '/db.php';

function authStart(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start([
            'cookie_httponly' => true,
            'cookie_samesite' => 'Strict',
            'use_strict_mode' => true
        ]);
    }
}

function authRegister(string $username, string $email, string $password, string $role = 'joueur'): array {
    $username = trim($username);
    $email = trim($email);

    if (strlen($username) < 3 || strlen($username) > 50) {
        return ['ok' => false, 'error' => 'Identifiant : 3 a 50 caracteres requis.'];
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'error' => 'Adresse email invalide.'];
    }
    if (strlen($password) < 6) {
        return ['ok' => false, 'error' => 'Mot de passe : 6 caracteres minimum.'];
    }
    if (!in_array($role, ['joueur', 'game_master'], true)) {
        $role = 'joueur';
    }

    $pdo = getDB();

    $stmt = $pdo->prepare('SELECT id FROM g5e_users WHERE username = :u OR email = :e');
    $stmt->execute([':u' => $username, ':e' => $email]);
    if ($stmt->fetch()) {
        return ['ok' => false, 'error' => 'Identifiant ou email deja utilise.'];
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare(
        'INSERT INTO g5e_users (username, email, password_hash, role) VALUES (:u, :e, :h, :r)'
    );
    $stmt->execute([':u' => $username, ':e' => $email, ':h' => $hash, ':r' => $role]);

    return ['ok' => true, 'user_id' => (int)$pdo->lastInsertId()];
}

function authLogin(string $username, string $password): array {
    $pdo = getDB();
    $stmt = $pdo->prepare('SELECT id, username, email, role, password_hash FROM g5e_users WHERE username = :u');
    $stmt->execute([':u' => trim($username)]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        return ['ok' => false, 'error' => 'Identifiant ou mot de passe incorrect.'];
    }

    authStart();
    session_regenerate_id(true);
    $_SESSION['user_id']   = $user['id'];
    $_SESSION['username']  = $user['username'];
    $_SESSION['role']      = $user['role'];

    return ['ok' => true, 'user' => $user];
}

function authLogout(): void {
    authStart();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

function authCheck(): bool {
    authStart();
    return isset($_SESSION['user_id']);
}

function authRequire(): void {
    if (!authCheck()) {
        header('Location: /php/auth/login.php');
        exit;
    }
}

function authRequireGM(): void {
    authRequire();
    if ($_SESSION['role'] !== 'game_master') {
        header('Location: /index.php');
        exit;
    }
}

function authUser(): ?array {
    authStart();
    if (!isset($_SESSION['user_id'])) return null;
    return [
        'id'       => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'role'     => $_SESSION['role']
    ];
}

function h(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}
