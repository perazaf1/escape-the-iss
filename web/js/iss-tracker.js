/* ============================================================
   ISS G5E — Orbital Tracking System
   Real-time ISS position via wheretheiss.at API + Leaflet
   ============================================================ */

(function () {
    'use strict';

    // --- CONFIG ---
    const API_URL = 'https://api.wheretheiss.at/v1/satellites/25544';
    const POLL_INTERVAL = 5000; // 5 seconds
    const TRACK_MAX_POINTS = 200;
    const FOOTPRINT_RADIUS_KM = 2200; // approximate ISS visibility radius

    // --- STATE ---
    let map, issMarker, footprintCircle, groundTrack;
    let trackPoints = [];
    let requestCount = 0;
    let refreshCountdown = 5;
    let countdownTimer = null;
    let lastLat = null, lastLon = null;

    // --- DOM REFS ---
    const els = {
        lat: document.getElementById('telem-lat'),
        lon: document.getElementById('telem-lon'),
        alt: document.getElementById('telem-alt'),
        vel: document.getElementById('telem-vel'),
        vis: document.getElementById('telem-vis'),
        visDetail: document.getElementById('telem-vis-detail'),
        utcTime: document.getElementById('tracker-utc-time'),
        signalStatus: document.getElementById('signal-status'),
        zoomLevel: document.getElementById('map-zoom-level'),
        refreshCountdown: document.getElementById('map-refresh-countdown'),
        lastUpdate: document.getElementById('last-update-time'),
        reqCount: document.getElementById('request-count'),
        apiStatus: document.getElementById('api-status')
    };

    // --- ISS SVG ICON ---
    const issSvg = `
        <div class="iss-icon-wrapper">
            <div class="iss-marker-ping"></div>
            <div class="iss-marker-ping"></div>
            <div class="iss-marker-ping"></div>
            <div class="iss-marker-core">
                <svg viewBox="0 0 32 32" width="32" height="32" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <!-- Solar panels -->
                    <rect x="1" y="10" width="8" height="12" rx="0.5" fill="#00d4ff" opacity="0.25" stroke="#00d4ff" stroke-width="0.7"/>
                    <rect x="23" y="10" width="8" height="12" rx="0.5" fill="#00d4ff" opacity="0.25" stroke="#00d4ff" stroke-width="0.7"/>
                    <!-- Panel lines -->
                    <line x1="5" y1="10" x2="5" y2="22" stroke="#00d4ff" stroke-width="0.4" opacity="0.5"/>
                    <line x1="27" y1="10" x2="27" y2="22" stroke="#00d4ff" stroke-width="0.4" opacity="0.5"/>
                    <!-- Truss -->
                    <rect x="9" y="15" width="14" height="2" fill="#00d4ff" opacity="0.4" stroke="#00d4ff" stroke-width="0.5"/>
                    <!-- Core module -->
                    <rect x="13" y="12" width="6" height="8" rx="1" fill="#0a1628" stroke="#00d4ff" stroke-width="0.8"/>
                    <!-- Core detail -->
                    <circle cx="16" cy="16" r="1.5" fill="#00d4ff" opacity="0.8"/>
                    <line x1="13" y1="14" x2="19" y2="14" stroke="#00d4ff" stroke-width="0.3" opacity="0.4"/>
                    <line x1="13" y1="18" x2="19" y2="18" stroke="#00d4ff" stroke-width="0.3" opacity="0.4"/>
                </svg>
            </div>
        </div>
    `;

    // --- INIT MAP ---
    function initMap() {
        map = L.map('iss-map', {
            center: [0, 0],
            zoom: 3,
            minZoom: 2,
            maxZoom: 8,
            zoomControl: true,
            attributionControl: true,
            worldCopyJump: true
        });

        // Dark tiles
        L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; <a href="https://carto.com/">CARTO</a> | ISS data: wheretheiss.at',
            subdomains: 'abcd',
            maxZoom: 19
        }).addTo(map);

        // ISS marker
        const issIcon = L.divIcon({
            html: issSvg,
            className: 'iss-leaflet-icon',
            iconSize: [40, 40],
            iconAnchor: [20, 20]
        });

        issMarker = L.marker([0, 0], { icon: issIcon, zIndexOffset: 1000 }).addTo(map);

        // Footprint circle
        footprintCircle = L.circle([0, 0], {
            radius: FOOTPRINT_RADIUS_KM * 1000,
            className: 'iss-footprint',
            weight: 1,
            fillOpacity: 0.04,
            interactive: false
        }).addTo(map);

        // Ground track polyline
        groundTrack = L.polyline([], {
            className: 'iss-ground-track',
            weight: 1.5,
            interactive: false
        }).addTo(map);

        // Update zoom display
        map.on('zoomend', function () {
            els.zoomLevel.textContent = map.getZoom();
        });
    }

    // --- FETCH ISS POSITION ---
    async function fetchISSPosition() {
        try {
            const resp = await fetch(API_URL);
            if (!resp.ok) throw new Error('HTTP ' + resp.status);
            const data = await resp.json();
            requestCount++;
            updateTelemetry(data);
            updateMap(data);
            updateTrackingPanel(true);
        } catch (err) {
            console.error('[ISS Tracker] API error:', err);
            updateTrackingPanel(false);
        }
    }

    // --- UPDATE TELEMETRY READOUTS ---
    function updateTelemetry(data) {
        const lat = parseFloat(data.latitude);
        const lon = parseFloat(data.longitude);
        const alt = parseFloat(data.altitude);
        const vel = parseFloat(data.velocity);
        const vis = data.visibility;

        // Format lat/lon with N/S E/W
        const latDir = lat >= 0 ? 'N' : 'S';
        const lonDir = lon >= 0 ? 'E' : 'W';

        els.lat.textContent = Math.abs(lat).toFixed(2) + '\u00B0' + latDir;
        els.lon.textContent = Math.abs(lon).toFixed(2) + '\u00B0' + lonDir;
        els.alt.textContent = Math.round(alt);
        els.vel.textContent = Math.round(vel).toLocaleString('fr-FR');

        // Visibility
        if (vis === 'daylight') {
            els.vis.textContent = 'JOUR';
            els.vis.className = 'telem-value telem-visibility vis-daylight has-data';
            els.visDetail.textContent = 'DAYLIGHT';
        } else {
            els.vis.textContent = 'ECLIPSE';
            els.vis.className = 'telem-value telem-visibility vis-eclipsed has-data';
            els.visDetail.textContent = 'ECLIPSED';
        }

        // Mark cells as having data
        els.lat.classList.add('has-data');
        els.lon.classList.add('has-data');
        els.alt.classList.add('has-data');
        els.vel.classList.add('has-data');

        document.querySelectorAll('.telem-cell').forEach(function (cell) {
            cell.classList.add('has-data');
        });

        // Signal
        document.querySelector('.signal-icon').classList.add('active');
        els.signalStatus.textContent = 'SIGNAL OK';
        els.signalStatus.classList.add('active');
    }

    // --- UPDATE MAP ---
    function updateMap(data) {
        const lat = parseFloat(data.latitude);
        const lon = parseFloat(data.longitude);

        // Detect orbit wrap-around (large lon jump = new orbit segment)
        const isWrap = lastLon !== null && Math.abs(lon - lastLon) > 100;

        // Move marker smoothly
        issMarker.setLatLng([lat, lon]);
        footprintCircle.setLatLng([lat, lon]);

        // Pan map to follow ISS
        map.panTo([lat, lon], { animate: true, duration: 1.5 });

        // Ground track
        if (isWrap) {
            // Start a new track segment on orbit wrap
            trackPoints = [];
            groundTrack.setLatLngs([]);
        }

        trackPoints.push([lat, lon]);
        if (trackPoints.length > TRACK_MAX_POINTS) {
            trackPoints.shift();
        }
        groundTrack.setLatLngs(trackPoints);

        lastLat = lat;
        lastLon = lon;
    }

    // --- UPDATE TRACKING PANEL ---
    function updateTrackingPanel(success) {
        const now = new Date();
        const hh = String(now.getUTCHours()).padStart(2, '0');
        const mm = String(now.getUTCMinutes()).padStart(2, '0');
        const ss = String(now.getUTCSeconds()).padStart(2, '0');

        els.lastUpdate.textContent = hh + ':' + mm + ':' + ss;
        els.reqCount.textContent = requestCount;

        if (success) {
            els.apiStatus.textContent = 'NOMINAL';
            els.apiStatus.className = 'orbit-stat-value api-status ok';
        } else {
            els.apiStatus.textContent = 'ERREUR';
            els.apiStatus.className = 'orbit-stat-value api-status error';
        }
    }

    // --- UTC CLOCK ---
    function updateUTC() {
        const now = new Date();
        const hh = String(now.getUTCHours()).padStart(2, '0');
        const mm = String(now.getUTCMinutes()).padStart(2, '0');
        const ss = String(now.getUTCSeconds()).padStart(2, '0');
        els.utcTime.textContent = hh + ':' + mm + ':' + ss;
    }

    // --- REFRESH COUNTDOWN ---
    function startCountdown() {
        refreshCountdown = 5;
        clearInterval(countdownTimer);
        countdownTimer = setInterval(function () {
            refreshCountdown--;
            if (refreshCountdown <= 0) refreshCountdown = 5;
            els.refreshCountdown.textContent = refreshCountdown + 's';
        }, 1000);
    }

    // --- MAIN LOOP ---
    function init() {
        initMap();
        updateUTC();
        setInterval(updateUTC, 1000);

        // First fetch
        fetchISSPosition();
        startCountdown();

        // Poll every 5s
        setInterval(function () {
            fetchISSPosition();
            refreshCountdown = 5;
        }, POLL_INTERVAL);
    }

    // --- START ---
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
