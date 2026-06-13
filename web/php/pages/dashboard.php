<?php
$pageTitle = 'Mission Control';
$extraCss = 'dashboard.css';
$extraJs = 'dashboard.js';

require_once __DIR__ . '/../includes/auth.php';
authRequire();
$user = authUser();

require_once __DIR__ . '/../includes/header.php';
?>

<!-- Station Modules Status Bar -->
<section class="station-modules" id="station-modules">
    <div class="modules-label">
        <span class="label-marker"></span>
        STATION MODULES
    </div>
    <div class="modules-row">
        <div class="module-card" data-room="G5A">
            <div class="module-id">G5A</div>
            <div class="module-name">Salle des communications</div>
            <div class="module-bar"><div class="module-bar-fill" style="width: 0%"></div></div>
            <div class="module-pct">0%</div>
        </div>
        <div class="module-card" data-room="G5B">
            <div class="module-id">G5B</div>
            <div class="module-name">Salle des commandes</div>
            <div class="module-bar"><div class="module-bar-fill" style="width: 0%"></div></div>
            <div class="module-pct">0%</div>
        </div>
        <div class="module-card" data-room="G5C">
            <div class="module-id">G5C</div>
            <div class="module-name">Serre artificielle</div>
            <div class="module-bar"><div class="module-bar-fill" style="width: 0%"></div></div>
            <div class="module-pct">0%</div>
        </div>
        <div class="module-card" data-room="G5D">
            <div class="module-id">G5D</div>
            <div class="module-name">Non defini</div>
            <div class="module-bar"><div class="module-bar-fill" style="width: 0%"></div></div>
            <div class="module-pct">0%</div>
        </div>
        <div class="module-card is-ours" data-room="G5E">
            <div class="module-id">G5E</div>
            <div class="module-name">Salle de stockage</div>
            <div class="module-bar"><div class="module-bar-fill" style="width: 0%"></div></div>
            <div class="module-pct">0%</div>
            <div class="module-ours-tag">VOTRE MODULE</div>
        </div>
    </div>
</section>

<!-- Main Instrument Grid -->
<section class="dash-grid">

    <!-- LEFT: Proximity Sensor Gauge -->
    <div class="dash-sensor">
        <div class="iss-panel">
            <div class="panel-header">
                <div class="panel-header-marker" aria-hidden="true"></div>
                <h2 class="panel-title">Capteur Proximite</h2>
                <span class="panel-subtitle" id="sensor-status">OFFLINE</span>
            </div>

            <!-- SVG Arc Gauge -->
            <div class="gauge-container">
                <svg class="gauge-svg" viewBox="0 0 300 200" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Jauge de distance du capteur de proximite">
                    <!-- Tick marks -->
                    <g class="gauge-ticks" id="gauge-ticks"></g>

                    <!-- Background arc -->
                    <path class="gauge-track" d="M 30 170 A 120 120 0 0 1 270 170" />

                    <!-- Value arc -->
                    <path class="gauge-fill" id="gauge-fill" d="M 30 170 A 120 120 0 0 1 270 170" />

                    <!-- Needle -->
                    <g id="gauge-needle" class="gauge-needle-group" transform="rotate(-90, 150, 170)">
                        <line x1="150" y1="170" x2="150" y2="60" class="gauge-needle" />
                        <circle cx="150" cy="170" r="6" class="gauge-needle-center" />
                    </g>

                    <!-- Labels -->
                    <text x="25" y="192" class="gauge-label-text">10</text>
                    <text x="268" y="192" class="gauge-label-text">80</text>
                    <text x="150" y="38" class="gauge-label-text gauge-label-unit">CM</text>
                </svg>

                <!-- Digital readout overlay -->
                <div class="gauge-readout">
                    <div class="readout-value" id="readout-distance">--</div>
                    <div class="readout-sub">
                        <span class="readout-label">ADC</span>
                        <span class="readout-adc" id="readout-adc">----</span>
                    </div>
                </div>
            </div>

            <!-- Sensor Stats -->
            <div class="sensor-stats">
                <div class="stat-item">
                    <span class="stat-label">MOY/60s</span>
                    <span class="stat-value" id="stat-avg">--</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">MIN</span>
                    <span class="stat-value" id="stat-min">--</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">MAX</span>
                    <span class="stat-value" id="stat-max">--</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">LECTURES</span>
                    <span class="stat-value" id="stat-readings">--</span>
                </div>
            </div>

            <!-- Sparkline -->
            <div class="sparkline-container">
                <div class="sparkline-label">HISTORIQUE (30 derniers)</div>
                <canvas id="sparkline-canvas" width="400" height="60" aria-hidden="true"></canvas>
            </div>
        </div>
    </div>

    <!-- RIGHT: Enigme Progress + Session -->
    <div class="dash-right">

        <!-- Game Session -->
        <div class="iss-panel session-panel">
            <div class="panel-header">
                <div class="panel-header-marker" aria-hidden="true"></div>
                <h2 class="panel-title">Session</h2>
                <span class="panel-subtitle" id="session-status">AUCUNE</span>
            </div>
            <div class="session-info">
                <div class="session-timer" id="session-timer">00:00:00</div>
                <div class="session-meta" id="session-meta">En attente d'une session...</div>
            </div>
        </div>

        <!-- Enigme Steps -->
        <div class="iss-panel steps-panel">
            <div class="panel-header">
                <div class="panel-header-marker" aria-hidden="true"></div>
                <h2 class="panel-title">Enigme Cargo</h2>
                <span class="panel-subtitle">4 ETAPES</span>
            </div>

            <div class="steps-list" id="steps-list">
                <div class="step-item" data-step="1">
                    <div class="step-indicator"></div>
                    <div class="step-info">
                        <div class="step-label">Chargement...</div>
                        <div class="step-target">-- cm</div>
                    </div>
                    <div class="step-status">--</div>
                </div>
                <div class="step-item" data-step="2">
                    <div class="step-indicator"></div>
                    <div class="step-info">
                        <div class="step-label">Chargement...</div>
                        <div class="step-target">-- cm</div>
                    </div>
                    <div class="step-status">--</div>
                </div>
                <div class="step-item" data-step="3">
                    <div class="step-indicator"></div>
                    <div class="step-info">
                        <div class="step-label">Chargement...</div>
                        <div class="step-target">-- cm</div>
                    </div>
                    <div class="step-status">--</div>
                </div>
                <div class="step-item" data-step="4">
                    <div class="step-indicator"></div>
                    <div class="step-info">
                        <div class="step-label">Chargement...</div>
                        <div class="step-target">-- cm</div>
                    </div>
                    <div class="step-status">--</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Alerts Feed -->
<section class="dash-alerts">
    <div class="iss-panel">
        <div class="panel-header">
            <div class="panel-header-marker" aria-hidden="true"></div>
            <h2 class="panel-title">Journal Systeme</h2>
            <span class="panel-subtitle">LIVE FEED</span>
        </div>
        <div class="alerts-terminal" id="alerts-terminal">
            <div class="alert-line dim">&gt; En attente de donnees du systeme...</div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
