/* ============================================================
   ISS G5E — Dashboard Real-time Controller
   ============================================================ */

(function () {
    'use strict';

    const POLL_FAST = 200;      // capteur live (fichier local, ultra rapide)
    const POLL_SLOW = 5000;     // reste (salles, alertes, BDD distante)
    const API_URL = '/php/api/dashboard.php';
    const API_LIVE = '/php/api/sensor_live.php';

    // --- Gauge geometry ---
    const GAUGE_ARC_LENGTH = 377; // approximate arc length of the SVG path
    const DIST_MIN = 10;
    const DIST_MAX = 80;

    let lastData = null;
    let knownAlertIds = new Set();
    let firstAlertLoad = true;

    // --- Toast container ---
    const toastContainer = document.createElement('div');
    toastContainer.className = 'toast-container';
    document.body.appendChild(toastContainer);

    function showToast(alert) {
        const el = document.createElement('div');
        el.className = `toast severity-${alert.severity || 'info'}`;
        el.innerHTML =
            '<div class="toast-icon"></div>' +
            '<div class="toast-body">' +
            '<div class="toast-tag">' + (alert.severity || 'info').toUpperCase() + '</div>' +
            '<div class="toast-msg">' + escapeHtml(alert.message) + '</div>' +
            '</div>';
        el.addEventListener('click', () => {
            el.classList.add('toast-out');
            setTimeout(() => el.remove(), 300);
        });
        toastContainer.appendChild(el);
        setTimeout(() => {
            el.classList.add('toast-out');
            setTimeout(() => el.remove(), 300);
        }, 6000);
    }

    // --- Init gauge ticks ---
    function initGaugeTicks() {
        const ticksGroup = document.getElementById('gauge-ticks');
        if (!ticksGroup) return;

        const cx = 150, cy = 170, r = 120;
        const startAngle = -180;
        const endAngle = 0;
        const steps = [10, 20, 30, 40, 50, 60, 70, 80];

        steps.forEach(val => {
            const pct = (val - DIST_MIN) / (DIST_MAX - DIST_MIN);
            const angle = (startAngle + pct * (endAngle - startAngle)) * Math.PI / 180;
            const x1 = cx + (r - 12) * Math.cos(angle);
            const y1 = cy + (r - 12) * Math.sin(angle);
            const x2 = cx + (r + 2) * Math.cos(angle);
            const y2 = cy + (r + 2) * Math.sin(angle);
            const xLabel = cx + (r - 26) * Math.cos(angle);
            const yLabel = cy + (r - 26) * Math.sin(angle);

            const line = document.createElementNS('http://www.w3.org/2000/svg', 'line');
            line.setAttribute('x1', x1);
            line.setAttribute('y1', y1);
            line.setAttribute('x2', x2);
            line.setAttribute('y2', y2);
            ticksGroup.appendChild(line);

            if (val % 20 === 0 || val === 10) {
                const text = document.createElementNS('http://www.w3.org/2000/svg', 'text');
                text.setAttribute('x', xLabel);
                text.setAttribute('y', yLabel + 3);
                text.textContent = val;
                ticksGroup.appendChild(text);
            }
        });
    }

    // --- Update gauge ---
    function updateGauge(distance) {
        const pct = Math.max(0, Math.min(1, (distance - DIST_MIN) / (DIST_MAX - DIST_MIN)));

        // Fill arc
        const fill = document.getElementById('gauge-fill');
        if (fill) {
            const offset = GAUGE_ARC_LENGTH * (1 - pct);
            fill.style.strokeDashoffset = offset;

            // Color zones
            fill.classList.remove('zone-close', 'zone-mid', 'zone-ok', 'zone-far');
            if (distance < 20) fill.classList.add('zone-close');
            else if (distance < 35) fill.classList.add('zone-mid');
            else if (distance < 55) fill.classList.add('zone-ok');
            else fill.classList.add('zone-far');
        }

        // Needle rotation: -90deg at min, 90deg at max
        const needle = document.getElementById('gauge-needle');
        if (needle) {
            const angle = -90 + pct * 180;
            needle.setAttribute('transform', `rotate(${angle}, 150, 170)`);
        }

        // Digital readout
        const readoutDist = document.getElementById('readout-distance');
        if (readoutDist) {
            readoutDist.textContent = Math.round(distance);
            if (distance < 20) readoutDist.style.color = 'var(--red-critical)';
            else if (distance < 35) readoutDist.style.color = 'var(--orange-alert)';
            else if (distance < 55) readoutDist.style.color = 'var(--white-cold)';
            else readoutDist.style.color = 'var(--green-sys)';
        }
    }

    // --- Update sparkline ---
    function drawSparkline(history) {
        const canvas = document.getElementById('sparkline-canvas');
        if (!canvas || !history || history.length === 0) return;

        const ctx = canvas.getContext('2d');
        const dpr = window.devicePixelRatio || 1;
        const rect = canvas.getBoundingClientRect();
        canvas.width = rect.width * dpr;
        canvas.height = rect.height * dpr;
        ctx.scale(dpr, dpr);

        const w = rect.width;
        const h = rect.height;
        const padding = { top: 4, bottom: 4, left: 0, right: 0 };
        const plotW = w - padding.left - padding.right;
        const plotH = h - padding.top - padding.bottom;

        ctx.clearRect(0, 0, w, h);

        // Grid lines
        ctx.strokeStyle = 'rgba(0, 212, 255, 0.06)';
        ctx.lineWidth = 0.5;
        for (let i = 0; i <= 4; i++) {
            const y = padding.top + (plotH / 4) * i;
            ctx.beginPath();
            ctx.moveTo(padding.left, y);
            ctx.lineTo(w - padding.right, y);
            ctx.stroke();
        }

        const values = history.map(h => parseInt(h.distance_cm) || 0);
        const max = Math.max(...values, 80);
        const min = Math.min(...values, 10);
        const range = max - min || 1;

        // Fill gradient
        const gradient = ctx.createLinearGradient(0, padding.top, 0, h - padding.bottom);
        gradient.addColorStop(0, 'rgba(0, 212, 255, 0.15)');
        gradient.addColorStop(1, 'rgba(0, 212, 255, 0.0)');

        ctx.beginPath();
        ctx.moveTo(padding.left, h - padding.bottom);

        values.forEach((val, i) => {
            const x = padding.left + (i / (values.length - 1)) * plotW;
            const y = padding.top + plotH - ((val - min) / range) * plotH;
            if (i === 0) ctx.lineTo(x, y);
            else ctx.lineTo(x, y);
        });

        ctx.lineTo(w - padding.right, h - padding.bottom);
        ctx.closePath();
        ctx.fillStyle = gradient;
        ctx.fill();

        // Line
        ctx.beginPath();
        values.forEach((val, i) => {
            const x = padding.left + (i / (values.length - 1)) * plotW;
            const y = padding.top + plotH - ((val - min) / range) * plotH;
            if (i === 0) ctx.moveTo(x, y);
            else ctx.lineTo(x, y);
        });
        ctx.strokeStyle = 'rgba(0, 212, 255, 0.7)';
        ctx.lineWidth = 1.5;
        ctx.stroke();

        // Last point dot
        if (values.length > 0) {
            const lastX = w - padding.right;
            const lastY = padding.top + plotH - ((values[values.length - 1] - min) / range) * plotH;
            ctx.beginPath();
            ctx.arc(lastX, lastY, 3, 0, Math.PI * 2);
            ctx.fillStyle = 'var(--cyan)';
            ctx.fillStyle = '#00d4ff';
            ctx.fill();
        }
    }

    // --- Update rooms ---
    function updateRooms(rooms) {
        if (!rooms) return;
        rooms.forEach(room => {
            const card = document.querySelector(`.module-card[data-room="${room.salle}"]`);
            if (!card) return;
            const fill = card.querySelector('.module-bar-fill');
            const pct = card.querySelector('.module-pct');
            const name = card.querySelector('.module-name');
            if (fill) fill.style.width = room.progress + '%';
            if (pct) pct.textContent = room.progress + '%';
            if (name && room.nom_usage) name.textContent = room.nom_usage;
        });
    }

    // --- Update steps ---
    function updateSteps(steps, currentDistance) {
        if (!steps) return;
        const list = document.getElementById('steps-list');
        if (!list) return;

        let firstUnvalidated = -1;

        steps.forEach((step, i) => {
            const el = list.children[i];
            if (!el) return;

            el.classList.remove('is-validated', 'is-current');

            const label = el.querySelector('.step-label');
            const target = el.querySelector('.step-target');
            const status = el.querySelector('.step-status');

            if (label) label.textContent = step.label;
            if (target) target.textContent = `Cible : ${step.target_distance_cm} cm (± ${step.tolerance_cm} cm)`;

            if (step.validated) {
                el.classList.add('is-validated');
                if (status) status.textContent = 'VALIDE';
            } else {
                if (firstUnvalidated < 0) {
                    firstUnvalidated = i;
                    el.classList.add('is-current');
                    if (status) {
                        if (currentDistance !== null) {
                            const diff = Math.abs(currentDistance - step.target_distance_cm);
                            if (diff <= step.tolerance_cm) {
                                status.textContent = 'EN ZONE';
                                status.style.color = 'var(--green-sys)';
                            } else {
                                status.textContent = diff + ' cm ecart';
                                status.style.color = 'var(--orange-alert)';
                            }
                        } else {
                            status.textContent = 'ACTIF';
                            status.style.color = '';
                        }
                    }
                } else {
                    if (status) {
                        status.textContent = 'EN ATTENTE';
                        status.style.color = '';
                    }
                }
            }
        });
    }

    // --- Update session ---
    function updateSession(session) {
        const statusEl = document.getElementById('session-status');
        const timerEl = document.getElementById('session-timer');
        const metaEl = document.getElementById('session-meta');

        if (!session) {
            if (statusEl) statusEl.textContent = 'AUCUNE';
            if (timerEl) timerEl.textContent = '00:00:00';
            if (metaEl) metaEl.textContent = 'En attente d\'une session...';
            return;
        }

        if (statusEl) {
            statusEl.textContent = session.status.toUpperCase().replace('_', ' ');
        }

        if (session.status === 'en_cours' && session.started_at) {
            const start = new Date(session.started_at.replace(' ', 'T') + 'Z');
            const now = new Date();
            const diff = Math.floor((now - start) / 1000);
            const h = String(Math.floor(diff / 3600)).padStart(2, '0');
            const m = String(Math.floor((diff % 3600) / 60)).padStart(2, '0');
            const s = String(diff % 60).padStart(2, '0');
            if (timerEl) timerEl.textContent = `${h}:${m}:${s}`;
            if (metaEl) metaEl.textContent = `Session #${session.id} en cours`;
        } else if (session.duration_seconds) {
            const d = session.duration_seconds;
            const h = String(Math.floor(d / 3600)).padStart(2, '0');
            const m = String(Math.floor((d % 3600) / 60)).padStart(2, '0');
            const s = String(d % 60).padStart(2, '0');
            if (timerEl) timerEl.textContent = `${h}:${m}:${s}`;
            if (metaEl) metaEl.textContent = `Session #${session.id} — ${session.status}`;
        }
    }

    // --- Update alerts ---
    function updateAlerts(alerts) {
        const terminal = document.getElementById('alerts-terminal');
        if (!terminal || !alerts) return;

        if (alerts.length === 0) {
            terminal.innerHTML = '<div class="alert-line dim">&gt; Aucun événement enregistré</div>';
            return;
        }

        // Detect new alerts → show toasts
        if (firstAlertLoad) {
            alerts.forEach(a => knownAlertIds.add(String(a.id)));
            firstAlertLoad = false;
        } else {
            alerts.forEach(a => {
                if (!knownAlertIds.has(String(a.id))) {
                    knownAlertIds.add(String(a.id));
                    showToast(a);
                }
            });
        }

        const html = alerts.map(a => {
            const time = a.created_at ? a.created_at.substring(11, 19) : '--:--:--';
            const tagClass = a.severity || 'info';
            const tagText = a.severity ? a.severity.toUpperCase() : 'INFO';
            return `<div class="alert-line">` +
                `<span class="time">${time}</span>` +
                `<span class="tag ${tagClass}">${tagText}</span>` +
                `<span class="msg">${escapeHtml(a.message)}</span>` +
                `</div>`;
        }).join('');

        terminal.innerHTML = html;
    }

    // --- Fetch & update ---
    function fetchDashboard() {
        fetch(API_URL)
            .then(res => {
                if (!res.ok) throw new Error(res.status);
                return res.json();
            })
            .then(data => {
                lastData = data;

                // Sensor status indicator
                const sensorStatus = document.getElementById('sensor-status');
                if (sensorStatus) {
                    if (data.sensor && data.sensor.created_at) {
                        const lastRead = new Date(data.sensor.created_at.replace(' ', 'T'));
                        const age = (Date.now() - lastRead.getTime()) / 1000;
                        if (age < 30) {
                            sensorStatus.textContent = 'ONLINE';
                            sensorStatus.style.color = 'var(--green-sys)';
                        } else {
                            sensorStatus.textContent = 'STALE (' + Math.round(age) + 's)';
                            sensorStatus.style.color = 'var(--orange-alert)';
                        }
                    } else {
                        sensorStatus.textContent = 'NO DATA';
                        sensorStatus.style.color = 'var(--red-critical)';
                    }
                }

                // Gauge
                const dist = data.sensor ? parseInt(data.sensor.distance_cm) : null;
                if (dist !== null) {
                    updateGauge(dist);
                    const adcEl = document.getElementById('readout-adc');
                    if (adcEl) adcEl.textContent = data.sensor.adc_value;
                }

                // Stats
                if (data.sensorStats) {
                    const avg = document.getElementById('stat-avg');
                    const min = document.getElementById('stat-min');
                    const max = document.getElementById('stat-max');
                    const readings = document.getElementById('stat-readings');
                    if (avg) avg.textContent = data.sensorStats.avg_dist ? Math.round(data.sensorStats.avg_dist) : '--';
                    if (min) min.textContent = data.sensorStats.min_dist ?? '--';
                    if (max) max.textContent = data.sensorStats.max_dist ?? '--';
                    if (readings) readings.textContent = data.sensorStats.readings ?? '0';
                }

                // Sparkline
                drawSparkline(data.history);

                // Rooms
                updateRooms(data.rooms);

                // Steps
                updateSteps(data.steps, dist);

                // Session
                updateSession(data.session);

                // Alerts
                updateAlerts(data.alerts);
            })
            .catch(err => {
                console.error('[Dashboard] Fetch error:', err);
                const sensorStatus = document.getElementById('sensor-status');
                if (sensorStatus) {
                    sensorStatus.textContent = 'ERREUR';
                    sensorStatus.style.color = 'var(--red-critical)';
                }
            });
    }

    // --- Utility ---
    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    // --- Fast sensor poll (file-based, ~200ms) ---
    let liveDist = null;

    function fetchSensorLive() {
        fetch(API_LIVE)
            .then(res => res.json())
            .then(data => {
                if (!data) return;

                const dist = parseInt(data.distance_cm);
                const adc = parseInt(data.adc_value);
                liveDist = dist;

                // Gauge
                if (!isNaN(dist)) updateGauge(dist);

                // ADC readout
                const adcEl = document.getElementById('readout-adc');
                if (adcEl && !isNaN(adc)) adcEl.textContent = adc;

                // Status
                const sensorStatus = document.getElementById('sensor-status');
                if (sensorStatus) {
                    if (data.online) {
                        sensorStatus.textContent = 'ONLINE';
                        sensorStatus.style.color = 'var(--green-sys)';
                    } else {
                        sensorStatus.textContent = 'OFFLINE';
                        sensorStatus.style.color = 'var(--red-critical)';
                    }
                }

                // Update steps with live distance
                if (lastData && lastData.steps) {
                    updateSteps(lastData.steps, dist);
                }
            })
            .catch(() => {});
    }

    // --- Init ---
    initGaugeTicks();
    fetchDashboard();
    fetchSensorLive();

    // Fast loop: capteur (200ms, fichier local = instantane)
    setInterval(fetchSensorLive, POLL_FAST);

    // Slow loop: salles, alertes, historique (5s, BDD distante)
    setInterval(fetchDashboard, POLL_SLOW);

    // Session timer tick
    setInterval(() => {
        if (lastData && lastData.session && lastData.session.status === 'en_cours') {
            updateSession(lastData.session);
        }
    }, 1000);

})();
