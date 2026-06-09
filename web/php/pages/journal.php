<?php
$pageTitle = 'Journal';
$extraCss = 'journal.css';
$extraJs = 'journal.js';
require_once __DIR__ . '/../includes/auth.php';
authStart();
authRequire();
require_once __DIR__ . '/../includes/header.php';
?>

<section class="journal-page">

    <!-- Header -->
    <div class="journal-header animate-in">
        <div class="journal-header-left">
            <div class="journal-title-row">
                <div class="journal-icon">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M4 19.5A2.5 2.5 0 016.5 17H20"/>
                        <path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"/>
                        <line x1="8" y1="7" x2="16" y2="7"/>
                        <line x1="8" y1="11" x2="14" y2="11"/>
                    </svg>
                </div>
                <h1 class="journal-title">JOURNAL DE BORD</h1>
            </div>
            <p class="journal-subtitle">SYSTEM EVENT LOG &mdash; ISS G5E CARGO MODULE</p>
        </div>
        <div class="journal-header-right">
            <div class="journal-counter-box">
                <span class="counter-label">EVENTS</span>
                <span class="counter-value" id="event-count">---</span>
            </div>
            <div class="journal-live-dot">
                <span class="live-dot"></span>
                <span class="live-label">LIVE</span>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="journal-filters animate-in" style="animation-delay: .08s">
        <button class="filter-btn active" data-severity="all">
            <span class="filter-dot dot-all"></span>
            TOUS
            <span class="filter-count" id="count-all">0</span>
        </button>
        <button class="filter-btn" data-severity="info">
            <span class="filter-dot dot-info"></span>
            INFO
            <span class="filter-count" id="count-info">0</span>
        </button>
        <button class="filter-btn" data-severity="warning">
            <span class="filter-dot dot-warning"></span>
            WARNING
            <span class="filter-count" id="count-warning">0</span>
        </button>
        <button class="filter-btn" data-severity="critical">
            <span class="filter-dot dot-critical"></span>
            CRITICAL
            <span class="filter-count" id="count-critical">0</span>
        </button>
        <div class="filter-spacer"></div>
        <button class="filter-btn scroll-toggle" id="auto-scroll-btn" title="Auto-scroll">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 5v14M5 12l7 7 7-7"/>
            </svg>
            AUTO-SCROLL
        </button>
    </div>

    <!-- Terminal -->
    <div class="journal-terminal animate-in" style="animation-delay: .16s">
        <div class="terminal-chrome">
            <div class="terminal-dots">
                <span class="t-dot t-red"></span>
                <span class="t-dot t-yellow"></span>
                <span class="t-dot t-green"></span>
            </div>
            <span class="terminal-path">iss-g5e://cargo-bay/events.log</span>
            <span class="terminal-status" id="terminal-status">CONNECTING...</span>
        </div>
        <div class="terminal-body" id="terminal-body">
            <div class="terminal-loading" id="terminal-loading">
                <span class="loading-line">[SYS] Initialisation du journal de bord...</span>
                <span class="loading-line">[SYS] Connexion au module G5E...</span>
                <span class="loading-cursor">_</span>
            </div>
        </div>
    </div>

</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
