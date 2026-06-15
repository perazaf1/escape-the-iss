<?php
$pageTitle = 'Controle Game Master';
$extraCss = 'admin.css';
$extraJs = 'admin.js';

require_once __DIR__ . '/../includes/auth.php';
authRequireGM(); // Seuls les GM peuvent accéder à cette page

require_once __DIR__ . '/../includes/header.php';
?>

<!-- Header Section -->
<section class="admin-header animate-in">
    <div class="admin-header-left">
        <div class="admin-section-tag">
            <span class="tag-marker" aria-hidden="true"></span>
            SYSTEME GLOBAL — ADMINISTRATION
        </div>
        <h1 class="admin-title">Panneau Game Master</h1>
    </div>
    <div class="admin-header-right">
        <button class="iss-btn btn-reset-danger" id="btn-admin-reset" title="Remettre à zéro la salle G5E">RESET G5E</button>
    </div>
</section>

<div class="admin-grid">
    <!-- LEFT: Global Station Overview -->
    <div class="admin-col animate-in" style="animation-delay: 0.1s">
        <div class="iss-panel">
            <div class="panel-header">
                <div class="panel-header-marker" aria-hidden="true"></div>
                <h2 class="panel-title">Vue Globale Station</h2>
                <span class="panel-subtitle">PROGRESSION EQUIPES</span>
            </div>
            
            <div class="admin-rooms-list" id="admin-rooms-list">
                <!-- Fetché en JS depuis /api/dashboard.php -->
                <div class="loading-text">Synchronisation...</div>
            </div>
        </div>
    </div>

    <!-- RIGHT: Bypass & Controls -->
    <div class="admin-col animate-in" style="animation-delay: 0.2s">
        <div class="iss-panel">
            <div class="panel-header">
                <div class="panel-header-marker" aria-hidden="true"></div>
                <h2 class="panel-title">Forçage Enigme G5E</h2>
                <span class="panel-subtitle">SECOURS CAPTEUR</span>
            </div>
            
            <div class="admin-warning-box">
                <div class="warning-icon">⚠</div>
                <div class="warning-text">Ces contrôles permettent de contourner la logique physique du capteur en cas de panne matérielle.</div>
            </div>
            
            <div class="admin-steps-list" id="admin-steps-list">
                <!-- Généré en JS -->
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
