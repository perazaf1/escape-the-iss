<?php
$pageTitle = 'ISS Tracker';
$extraCss = 'iss-tracker.css';
$extraJs = 'iss-tracker.js';

require_once __DIR__ . '/../includes/auth.php';
authRequire();

require_once __DIR__ . '/../includes/header.php';
?>

<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="" />

<!-- Tracker Header -->
<section class="tracker-header animate-in">
    <div class="tracker-header-left">
        <div class="tracker-section-tag">
            <span class="tag-marker"></span>
            NORAD ID 25544 // ORBITAL TRACKING SYSTEM
        </div>
        <h1 class="tracker-title">ISS Tracker</h1>
    </div>
    <div class="tracker-header-right">
        <div class="tracker-signal">
            <div class="signal-icon">
                <span class="signal-bar bar-1"></span>
                <span class="signal-bar bar-2"></span>
                <span class="signal-bar bar-3"></span>
                <span class="signal-bar bar-4"></span>
            </div>
            <span class="signal-label" id="signal-status">ACQUISITION...</span>
        </div>
        <div class="tracker-utc">
            <span class="utc-label">UTC</span>
            <span class="utc-time" id="tracker-utc-time">--:--:--</span>
        </div>
    </div>
</section>

<!-- Telemetry Readouts (above map) -->
<section class="telemetry-strip animate-in" style="animation-delay: 0.08s">
    <div class="telem-cell">
        <div class="telem-label">LATITUDE</div>
        <div class="telem-value" id="telem-lat">---.--</div>
        <div class="telem-unit">&deg; N/S</div>
    </div>
    <div class="telem-divider"></div>
    <div class="telem-cell">
        <div class="telem-label">LONGITUDE</div>
        <div class="telem-value" id="telem-lon">---.--</div>
        <div class="telem-unit">&deg; E/W</div>
    </div>
    <div class="telem-divider"></div>
    <div class="telem-cell">
        <div class="telem-label">ALTITUDE</div>
        <div class="telem-value" id="telem-alt">---</div>
        <div class="telem-unit">KM</div>
    </div>
    <div class="telem-divider"></div>
    <div class="telem-cell">
        <div class="telem-label">VITESSE</div>
        <div class="telem-value" id="telem-vel">-----</div>
        <div class="telem-unit">KM/H</div>
    </div>
    <div class="telem-divider"></div>
    <div class="telem-cell">
        <div class="telem-label">VISIBILITE</div>
        <div class="telem-value telem-visibility" id="telem-vis">---</div>
        <div class="telem-unit" id="telem-vis-detail">&nbsp;</div>
    </div>
</section>

<!-- Map Container -->
<section class="tracker-map-wrapper animate-in" style="animation-delay: 0.15s">
    <div class="map-frame">
        <!-- Technical corner accents -->
        <div class="map-corner corner-tl"></div>
        <div class="map-corner corner-tr"></div>
        <div class="map-corner corner-bl"></div>
        <div class="map-corner corner-br"></div>

        <!-- Map -->
        <div id="iss-map"></div>

        <!-- Crosshair overlay -->
        <div class="map-crosshair">
            <div class="crosshair-h"></div>
            <div class="crosshair-v"></div>
        </div>

        <!-- Map HUD overlays -->
        <div class="map-hud-topleft">
            <span class="hud-tag">LIVE FEED</span>
            <span class="hud-dot"></span>
        </div>
        <div class="map-hud-topright">
            <span class="hud-label">GROUND TRACK</span>
        </div>
        <div class="map-hud-bottomleft">
            <span class="hud-label">ZOOM</span>
            <span class="hud-val" id="map-zoom-level">3</span>
        </div>
        <div class="map-hud-bottomright">
            <span class="hud-label">REFRESH</span>
            <span class="hud-val" id="map-refresh-countdown">5s</span>
        </div>
    </div>
</section>

<!-- Orbit Data Row -->
<section class="orbit-data-row animate-in" style="animation-delay: 0.22s">
    <div class="iss-panel orbit-panel">
        <div class="orbit-panel-header">
            <div class="panel-header-marker"></div>
            <span class="orbit-panel-title">Orbite</span>
        </div>
        <div class="orbit-stats">
            <div class="orbit-stat">
                <span class="orbit-stat-label">PERIODE ORBITALE</span>
                <span class="orbit-stat-value">~92 min</span>
            </div>
            <div class="orbit-stat">
                <span class="orbit-stat-label">INCLINAISON</span>
                <span class="orbit-stat-value">51.6&deg;</span>
            </div>
            <div class="orbit-stat">
                <span class="orbit-stat-label">ORBITES / JOUR</span>
                <span class="orbit-stat-value">~15.5</span>
            </div>
        </div>
    </div>

    <div class="iss-panel orbit-panel">
        <div class="orbit-panel-header">
            <div class="panel-header-marker"></div>
            <span class="orbit-panel-title">Station</span>
        </div>
        <div class="orbit-stats">
            <div class="orbit-stat">
                <span class="orbit-stat-label">MASSE</span>
                <span class="orbit-stat-value">~420 000 kg</span>
            </div>
            <div class="orbit-stat">
                <span class="orbit-stat-label">ENVERGURE</span>
                <span class="orbit-stat-value">109 m</span>
            </div>
            <div class="orbit-stat">
                <span class="orbit-stat-label">DEPUIS</span>
                <span class="orbit-stat-value">1998</span>
            </div>
        </div>
    </div>

    <div class="iss-panel orbit-panel">
        <div class="orbit-panel-header">
            <div class="panel-header-marker"></div>
            <span class="orbit-panel-title">Tracking</span>
        </div>
        <div class="orbit-stats">
            <div class="orbit-stat">
                <span class="orbit-stat-label">DERNIERE MAJ</span>
                <span class="orbit-stat-value" id="last-update-time">--:--:--</span>
            </div>
            <div class="orbit-stat">
                <span class="orbit-stat-label">REQUETES</span>
                <span class="orbit-stat-value" id="request-count">0</span>
            </div>
            <div class="orbit-stat">
                <span class="orbit-stat-label">STATUT API</span>
                <span class="orbit-stat-value api-status" id="api-status">PENDING</span>
            </div>
        </div>
    </div>
</section>

<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
