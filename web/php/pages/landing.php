<?php
/**
 * Landing page immersive — accessible sans authentification
 * Page narrative standalone avec intro au scénario
 */
require_once __DIR__ . '/../includes/auth.php';
authStart();

// Si déjà connecté, rediriger vers le dashboard
if (authCheck()) {
    header('Location: /php/pages/dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ESCAPE THE ISS — G5E Cargo Bay</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;600;700;800;900&family=Rajdhani:wght@300;400;500;600;700&family=Share+Tech+Mono&family=Exo+2:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/landing.css">
</head>
<body>

    <canvas id="stars-canvas"></canvas>
    <div class="scanlines"></div>

    <!-- HERO SECTION -->
    <section class="hero-section" id="hero">
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <div class="hero-alert-badge">
                <span class="alert-pulse"></span>
                <span class="alert-text">/// TRANSMISSION PRIORITAIRE ///</span>
            </div>

            <h1 class="hero-title">
                <span class="title-line title-line-1">ALERTE</span>
                <span class="title-line title-line-2">G&Eacute;N&Eacute;RALE</span>
            </h1>

            <div class="hero-terminal" id="hero-terminal">
                <span class="terminal-cursor">_</span>
            </div>

            <div class="hero-scroll-hint" id="scroll-hint">
                <span class="scroll-text">LIRE LE BRIEFING</span>
                <svg class="scroll-arrow" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 5v14M5 12l7 7 7-7"/>
                </svg>
            </div>
        </div>
    </section>

    <!-- BRIEFING SECTION -->
    <section class="briefing-section" id="briefing">
        <div class="briefing-container">
            <div class="briefing-header reveal">
                <div class="briefing-marker"></div>
                <h2 class="briefing-title">RAPPORT D'INCIDENT</h2>
                <span class="briefing-class">CLASSIFICATION : OMEGA-7</span>
            </div>

            <div class="briefing-content">
                <div class="briefing-block reveal">
                    <div class="block-timestamp">03:47 UTC &mdash; D&eacute;tection de br&egrave;che</div>
                    <p class="block-text">
                        Cette nuit, une br&egrave;che a &eacute;t&eacute; d&eacute;tect&eacute;e dans le module de stockage de l'ISS.
                        Une bande d'aliens a infiltr&eacute; la station et a d&eacute;rob&eacute; l'int&eacute;gralit&eacute; du <strong class="text-highlight">stock de brioches</strong>.
                    </p>
                </div>

                <div class="briefing-block reveal">
                    <div class="block-timestamp">03:52 UTC &mdash; Analyse x&eacute;nobiologique</div>
                    <p class="block-text">
                        D'apr&egrave;s nos analystes, ces cr&eacute;atures souffrent d'une forme rare de <strong class="text-highlight">dyslexie intergalactique</strong> :
                        elles auraient confondu <span class="text-code">PESQUET</span> avec <span class="text-code">PASQUIER</span>.
                        L'erreur n'a toujours pas &eacute;t&eacute; remarqu&eacute;e de leur c&ocirc;t&eacute;.
                    </p>
                </div>

                <div class="briefing-block block-critical reveal">
                    <div class="block-timestamp">04:15 UTC — Alerte critique</div>
                    <p class="block-text">
                        Un astronaute &agrave; bord consommait environ <strong class="text-critical">98%</strong> de ces brioches.
                        Elles servaient de contrepoids naturel &agrave; la station.
                        Depuis leur disparition, <strong class="text-critical">l'ISS penche dangereusement sur tribord</strong>,
                        les crayons flottent &agrave; l'envers et le caf&eacute; refuse cat&eacute;goriquement de rester dans les tasses.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- MISSION SECTION -->
    <section class="mission-section" id="mission">
        <div class="mission-container">
            <div class="mission-header reveal">
                <div class="mission-marker"></div>
                <h2 class="mission-title">VOTRE MISSION</h2>
                <span class="mission-sub">SI VOUS L'ACCEPTEZ</span>
            </div>

            <p class="mission-desc reveal">
                R&eacute;organiser la salle de stockage pour r&eacute;&eacute;quilibrer la station.
                Placez les cargaisons aux distances exactes indiqu&eacute;es par l'ordinateur de bord.
            </p>

            <div class="mission-steps">
                <div class="step-card reveal">
                    <div class="step-number">01</div>
                    <div class="step-icon">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <circle cx="12" cy="12" r="10"/>
                            <path d="M12 6v6l4 2"/>
                        </svg>
                    </div>
                    <h3 class="step-name">R&eacute;servoir O2</h3>
                    <p class="step-desc">L'oxyg&egrave;ne de secours doit &ecirc;tre repositionn&eacute; &agrave; la distance exacte.</p>
                    <div class="step-status">DISTANCE : CLASSIFI&Eacute;E</div>
                </div>

                <div class="step-card reveal">
                    <div class="step-number">02</div>
                    <div class="step-icon">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <rect x="2" y="7" width="20" height="14" rx="2"/>
                            <path d="M16 7V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v3"/>
                        </svg>
                    </div>
                    <h3 class="step-name">Caisse de rations</h3>
                    <p class="step-desc">La nourriture de l'&eacute;quipage doit &ecirc;tre &agrave; port&eacute;e de main.</p>
                    <div class="step-status">DISTANCE : CLASSIFI&Eacute;E</div>
                </div>

                <div class="step-card reveal">
                    <div class="step-number">03</div>
                    <div class="step-icon">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M22 12h-4l-3 9L9 3l-3 9H2"/>
                        </svg>
                    </div>
                    <h3 class="step-name">Module de communication</h3>
                    <p class="step-desc">Les transmissions n&eacute;cessitent de l'espace pour &eacute;viter les interf&eacute;rences.</p>
                    <div class="step-status">DISTANCE : CLASSIFI&Eacute;E</div>
                </div>
            </div>

            <div class="mission-warning reveal">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                    <line x1="12" y1="9" x2="12" y2="13"/>
                    <line x1="12" y1="17" x2="12.01" y2="17"/>
                </svg>
                <span>Une erreur de quelques centim&egrave;tres pourrait envoyer l'ISS dans une vrille spatiale incontr&ocirc;lable.</span>
            </div>
        </div>
    </section>

    <!-- CTA SECTION -->
    <section class="cta-section" id="cta">
        <div class="cta-container reveal">
            <div class="cta-border-top"></div>
            <p class="cta-text">Ce message ne s'autod&eacute;truira pas &mdash; le budget ne le permet pas.</p>
            <h2 class="cta-title">BONNE CHANCE, ASTRONAUTES.</h2>
            <div class="cta-buttons">
                <a href="/php/auth/login.php" class="cta-btn cta-btn-primary">
                    <span class="btn-icon">&#9654;</span>
                    COMMENCER LA MISSION
                </a>
                <a href="/php/auth/register.php" class="cta-btn cta-btn-secondary">
                    CR&Eacute;ER UN COMPTE
                </a>
            </div>
            <div class="cta-border-bottom"></div>
        </div>
    </section>

    <!-- FOOTER -->
    <footer class="landing-footer">
        <span class="footer-brand">ISS G5E CARGO BAY</span>
        <span class="footer-sep">&mdash;</span>
        <span class="footer-info">ISEP A1 2025-2026</span>
    </footer>

    <script src="/js/landing.js"></script>
</body>
</html>
