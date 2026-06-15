<?php
require_once __DIR__ . '/auth.php';
authStart();
$currentUser = authUser();
$currentPage = basename($_SERVER['SCRIPT_NAME'], '.php');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? h($pageTitle) . ' — ' : '' ?>ISS G5E Cargo Bay</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;600;700;800;900&family=Rajdhani:wght@300;400;500;600;700&family=Share+Tech+Mono&family=Exo+2:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/main.css">
    <?php if (isset($extraCss)): ?>
        <link rel="stylesheet" href="/css/<?= $extraCss ?>">
    <?php endif; ?>
</head>
<body>
    <div class="scanlines" aria-hidden="true"></div>
    <canvas id="stars-canvas" aria-hidden="true"></canvas>

    <nav class="iss-nav">
        <div class="nav-left">
            <div class="nav-logo">
                <div class="logo-indicator" aria-hidden="true"></div>
                <span class="logo-text">ISS</span>
                <span class="logo-sub">G5E CARGO BAY</span>
            </div>
        </div>

        <div class="nav-center">
            <?php if ($currentUser): ?>
            <a href="/index.php" class="nav-link <?= $currentPage === 'index' ? 'active' : '' ?>">
                <span class="nav-link-indicator" aria-hidden="true"></span>
                DASHBOARD
            </a>
            <a href="/php/pages/cargo.php" class="nav-link <?= $currentPage === 'cargo' ? 'active' : '' ?>">
                <span class="nav-link-indicator" aria-hidden="true"></span>
                CARGO
            </a>
            <a href="/php/pages/iss-tracker.php" class="nav-link <?= $currentPage === 'iss-tracker' ? 'active' : '' ?>">
                <span class="nav-link-indicator" aria-hidden="true"></span>
                ISS TRACKER
            </a>
            <a href="/php/pages/journal.php" class="nav-link <?= $currentPage === 'journal' ? 'active' : '' ?>">
                <span class="nav-link-indicator" aria-hidden="true"></span>
                JOURNAL
            </a>
            <?php if ($currentUser['role'] === 'game_master'): ?>
            <a href="/php/pages/admin.php" class="nav-link <?= $currentPage === 'admin' ? 'active' : '' ?>">
                <span class="nav-link-indicator" aria-hidden="true"></span>
                CONTROLE GM
            </a>
            <?php endif; ?>
            <?php endif; ?>
        </div>

        <div class="nav-right">
            <?php if ($currentUser): ?>
            <div class="nav-user">
                <div class="user-role-badge role-<?= $currentUser['role'] ?>">
                    <?= $currentUser['role'] === 'game_master' ? 'GM' : 'CREW' ?>
                </div>
                <span class="user-name"><?= h($currentUser['username']) ?></span>
            </div>
            <a href="/php/auth/logout.php" class="nav-btn-logout" title="Deconnexion" aria-label="Deconnexion">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                    <path d="M6 2H3C2.4 2 2 2.4 2 3V13C2 13.6 2.4 14 3 14H6M11 11L14 8L11 5M6 8H14" stroke="currentColor" stroke-width="1.5" stroke-linecap="square"/>
                </svg>
            </a>
            <?php else: ?>
            <a href="/php/auth/login.php" class="nav-link <?= $currentPage === 'login' ? 'active' : '' ?>">CONNEXION</a>
            <a href="/php/auth/register.php" class="nav-link <?= $currentPage === 'register' ? 'active' : '' ?>">INSCRIPTION</a>
            <?php endif; ?>
        </div>
    </nav>

    <main class="iss-main">
