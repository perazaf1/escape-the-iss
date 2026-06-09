/* ============================================================
   ISS G5E — Cargo Bay Visualization + Chart.js
   ============================================================ */

(function () {
    'use strict';

    const API_DASHBOARD = '/php/api/dashboard.php';
    const API_LIVE = '/php/api/sensor_live.php';
    const API_HISTORY = '/php/api/history.php';

    const DIST_MIN = 10;
    const DIST_MAX = 80;

    let stepsData = null;
    let historyChart = null;
    let currentPeriod = '5min';

    // --- ENIGMES (riddles) ---
    // Answers are simple hashes to avoid plain-text in source
    // hash = sum of charCodes * 7 + length
    function hashAnswer(val) {
        const s = String(val).trim();
        let h = 0;
        for (let i = 0; i < s.length; i++) h += s.charCodeAt(i);
        return h * 7 + s.length;
    }

    const ENIGMES = {
        1: {
            title: 'ENIGME // RESERVOIR O2',
            text: 'L\'equipage consomme <span class="enigme-highlight">7 litres d\'O2 par heure</span>. La reserve de secours doit tenir <span class="enigme-highlight">5 heures</span>.<br>Combien de litres dans le reservoir ?',
            hash: hashAnswer('35'),
            hint: 'Entrez un nombre'
        },
        2: {
            title: 'ENIGME // CAISSE DE RATIONS',
            text: 'L\'ordinateur de bord affiche ce code :<br><span class="enigme-code">10100</span><br>Les astronautes pensent en <span class="enigme-highlight">binaire</span>. Convertissez.',
            hash: hashAnswer('20'),
            hint: 'Nombre decimal'
        },
        3: {
            title: 'ENIGME // MODULE COMM',
            text: 'Un message chiffre a ete intercepte :<br><span class="enigme-code">FLQTXDQWH</span><br>Le chiffrement de <span class="enigme-highlight">Cesar</span> avec un decalage de <span class="enigme-highlight">3</span> a ete utilise. Dechiffrez et ecrivez le nombre correspondant.',
            hash: hashAnswer('50'),
            hint: 'Nombre en chiffres'
        }
    };

    // Unlock state from localStorage
    function getUnlocked() {
        try {
            return JSON.parse(localStorage.getItem('g5e_unlocked') || '{}');
        } catch (e) { return {}; }
    }
    function setUnlocked(stepOrder) {
        const u = getUnlocked();
        u[stepOrder] = true;
        localStorage.setItem('g5e_unlocked', JSON.stringify(u));
    }
    function isUnlocked(stepOrder) {
        return !!getUnlocked()[stepOrder];
    }
    function resetUnlocked() {
        localStorage.removeItem('g5e_unlocked');
    }

    // --- Build ruler ticks ---
    function buildRuler() {
        const track = document.querySelector('.ruler-track');
        if (!track) return;

        for (let cm = DIST_MIN; cm <= DIST_MAX; cm += 5) {
            const pct = ((cm - DIST_MIN) / (DIST_MAX - DIST_MIN)) * 100;
            const isMajor = cm % 10 === 0;

            const tick = document.createElement('div');
            tick.className = 'ruler-tick ' + (isMajor ? 'major' : 'minor');
            tick.style.left = pct + '%';
            track.appendChild(tick);

            if (isMajor) {
                const label = document.createElement('div');
                label.className = 'ruler-tick-label';
                label.style.left = pct + '%';
                label.textContent = cm + ' cm';
                track.appendChild(label);
            }
        }
    }

    // --- Build target zones from steps data ---
    function buildTargetZones(steps) {
        const container = document.getElementById('target-zones');
        const cardsContainer = document.getElementById('steps-cards');
        if (!container || !steps) return;

        container.innerHTML = '';
        cardsContainer.innerHTML = '';

        steps.forEach((step, i) => {
            const center = step.target_distance_cm;
            const tol = step.tolerance_cm;
            const left = ((center - tol - DIST_MIN) / (DIST_MAX - DIST_MIN)) * 100;
            const right = ((center + tol - DIST_MIN) / (DIST_MAX - DIST_MIN)) * 100;
            const width = right - left;
            const unlocked = isUnlocked(step.step_order);

            // Zone element — only visible if unlocked
            const zone = document.createElement('div');
            zone.className = 'target-zone pending';
            zone.dataset.step = step.step_order;
            if (unlocked) {
                zone.style.left = Math.max(0, left) + '%';
                zone.style.width = Math.min(width, 100 - left) + '%';
                zone.innerHTML = `
                    <div class="target-zone-label">ETAPE ${step.step_order}</div>
                    <div class="target-zone-check"></div>
                    <div class="target-zone-dist">${center} cm</div>
                `;
            } else {
                zone.classList.add('locked');
                zone.style.display = 'none';
            }
            container.appendChild(zone);

            // Card element
            const card = document.createElement('div');
            card.dataset.step = step.step_order;
            const enigme = ENIGMES[step.step_order];

            if (unlocked) {
                card.className = 'step-card pending';
                card.innerHTML = `
                    <div class="step-card-order">ETAPE ${step.step_order}</div>
                    <div class="step-card-label">${escapeHtml(step.label)}</div>
                    <div class="step-card-target">Cible : ${center} cm (&plusmn; ${tol} cm)</div>
                    <div class="step-card-status">EN ATTENTE</div>
                `;
            } else if (enigme) {
                card.className = 'step-card locked enigme-card';
                card.innerHTML = `
                    <div class="step-card-order">ETAPE ${step.step_order}</div>
                    <div class="enigme-lock-icon">
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                            <rect x="3" y="7" width="10" height="7" rx="1" stroke="currentColor" stroke-width="1.2"/>
                            <path d="M5 7V5a3 3 0 0 1 6 0v2" stroke="currentColor" stroke-width="1.2" fill="none"/>
                        </svg>
                        VERROUILLE
                    </div>
                    <div class="enigme-title">${enigme.title}</div>
                    <div class="enigme-text">${enigme.text}</div>
                    <div class="enigme-input-row">
                        <input type="text" class="enigme-input" data-step="${step.step_order}" placeholder="${enigme.hint}" autocomplete="off" spellcheck="false" />
                        <button class="enigme-submit" data-step="${step.step_order}">VALIDER</button>
                    </div>
                    <div class="enigme-feedback" id="enigme-feedback-${step.step_order}"></div>
                `;
            }
            cardsContainer.appendChild(card);
        });

        // Bind enigme events
        bindEnigmeEvents(steps);
    }

    // --- Enigme validation ---
    function bindEnigmeEvents(steps) {
        document.querySelectorAll('.enigme-submit').forEach(btn => {
            btn.addEventListener('click', function () {
                tryEnigme(parseInt(this.dataset.step), steps);
            });
        });
        document.querySelectorAll('.enigme-input').forEach(input => {
            input.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    tryEnigme(parseInt(this.dataset.step), steps);
                }
            });
        });
    }

    function tryEnigme(stepOrder, steps) {
        const input = document.querySelector(`.enigme-input[data-step="${stepOrder}"]`);
        const feedback = document.getElementById('enigme-feedback-' + stepOrder);
        if (!input) return;

        const val = input.value.trim();
        if (!val) return;

        const enigme = ENIGMES[stepOrder];
        if (!enigme) return;

        if (hashAnswer(val) === enigme.hash) {
            // Correct!
            setUnlocked(stepOrder);
            if (feedback) {
                feedback.textContent = 'ACCES AUTORISE';
                feedback.className = 'enigme-feedback success';
            }

            // Persister le deverrouillage en BDD (pour que read_sensor.php le sache)
            fetch('/php/api/unlock_step.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ step_order: stepOrder })
            }).catch(() => {});

            // Rebuild after short delay for visual feedback
            setTimeout(() => {
                stepsData = null; // force rebuild
                fetchDashboard();
            }, 600);
        } else {
            // Wrong
            if (feedback) {
                feedback.textContent = 'REPONSE INCORRECTE';
                feedback.className = 'enigme-feedback error';
            }
            input.classList.add('shake');
            setTimeout(() => input.classList.remove('shake'), 400);
        }
    }

    // --- Update steps validation state ---
    function updateStepsState(steps, liveDistance) {
        if (!steps) return;

        let firstUnvalidated = -1;
        let validatedCount = 0;

        steps.forEach((step, i) => {
            const zone = document.querySelector(`.target-zone[data-step="${step.step_order}"]`);
            const card = document.querySelector(`.step-card[data-step="${step.step_order}"]`);
            if (!zone || !card) return;

            // Skip locked (enigme not solved) cards
            if (!isUnlocked(step.step_order)) return;

            zone.classList.remove('pending', 'active', 'validated');
            card.classList.remove('pending', 'active', 'validated', 'locked');

            const statusEl = card.querySelector('.step-card-status');

            if (step.validated) {
                zone.classList.add('validated');
                card.classList.add('validated');
                if (statusEl) statusEl.textContent = 'VALIDE';
                validatedCount++;
            } else if (firstUnvalidated < 0) {
                firstUnvalidated = i;
                zone.classList.add('active');
                card.classList.add('active');

                if (statusEl && liveDistance !== null) {
                    const diff = Math.abs(liveDistance - step.target_distance_cm);
                    if (diff <= step.tolerance_cm) {
                        statusEl.textContent = 'EN ZONE';
                        statusEl.style.color = 'var(--green-sys)';
                    } else {
                        statusEl.textContent = diff + ' cm ecart';
                        statusEl.style.color = '';
                    }
                } else if (statusEl) {
                    statusEl.textContent = 'ACTIF';
                    statusEl.style.color = '';
                }
            } else {
                zone.classList.add('pending');
                card.classList.add('pending');
                if (statusEl) {
                    statusEl.textContent = 'EN ATTENTE';
                    statusEl.style.color = '';
                }
            }
        });

        // Update balance meter
        const totalSteps = steps.length;
        const pct = totalSteps > 0 ? Math.round((validatedCount / totalSteps) * 100) : 0;
        const fill = document.getElementById('balance-fill');
        const pctEl = document.getElementById('balance-pct');

        if (fill) {
            fill.style.width = pct + '%';
            if (pct >= 100) fill.classList.add('complete');
            else fill.classList.remove('complete');
        }
        if (pctEl) pctEl.textContent = pct + '%';
    }

    // --- Move sensor line ---
    function updateSensorLine(distance) {
        const line = document.getElementById('sensor-line');
        const label = document.getElementById('sensor-line-label');
        if (!line) return;

        const container = document.querySelector('.schematic-container');
        if (!container) return;

        const rulerLeft = 80; // px offset from CSS
        const rulerRight = 30;
        const containerWidth = container.offsetWidth;
        const trackWidth = containerWidth - rulerLeft - rulerRight;

        const pct = (distance - DIST_MIN) / (DIST_MAX - DIST_MIN);
        const pos = rulerLeft + pct * trackWidth;

        line.style.left = pos + 'px';
        if (label) label.textContent = distance + ' cm';
    }

    // --- Chart.js setup ---
    function initChart() {
        const ctx = document.getElementById('history-chart');
        if (!ctx) return;

        historyChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: [],
                datasets: [{
                    label: 'Distance (cm)',
                    data: [],
                    borderColor: '#00d4ff',
                    backgroundColor: 'rgba(0, 212, 255, 0.05)',
                    borderWidth: 1.5,
                    fill: true,
                    tension: 0.3,
                    pointRadius: 0,
                    pointHitRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: { duration: 300 },
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(10, 15, 26, 0.95)',
                        titleFont: { family: 'Share Tech Mono', size: 11 },
                        bodyFont: { family: 'Share Tech Mono', size: 12 },
                        titleColor: '#8a9bb0',
                        bodyColor: '#00d4ff',
                        borderColor: 'rgba(0, 212, 255, 0.2)',
                        borderWidth: 1,
                        padding: 10,
                        displayColors: false,
                        callbacks: {
                            title: function(items) {
                                if (!items[0]) return '';
                                const d = items[0].label;
                                return d ? d.substring(11, 19) : '';
                            },
                            label: function(item) {
                                return item.parsed.y + ' cm';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            color: 'rgba(0, 212, 255, 0.04)',
                            drawTicks: false
                        },
                        ticks: {
                            font: { family: 'Share Tech Mono', size: 9 },
                            color: 'rgba(138, 155, 176, 0.4)',
                            maxTicksLimit: 8,
                            callback: function(val, i, ticks) {
                                const label = this.getLabelForValue(val);
                                return label ? label.substring(11, 19) : '';
                            }
                        },
                        border: { color: 'rgba(0, 212, 255, 0.1)' }
                    },
                    y: {
                        min: 0,
                        max: 85,
                        grid: {
                            color: 'rgba(0, 212, 255, 0.04)',
                            drawTicks: false
                        },
                        ticks: {
                            font: { family: 'Share Tech Mono', size: 9 },
                            color: 'rgba(138, 155, 176, 0.4)',
                            stepSize: 10,
                            callback: function(val) { return val + ' cm'; }
                        },
                        border: { color: 'rgba(0, 212, 255, 0.1)' }
                    }
                }
            }
        });
    }

    // --- Fetch history + update chart ---
    function fetchHistory() {
        fetch(API_HISTORY + '?period=' + currentPeriod)
            .then(res => res.json())
            .then(data => {
                if (!historyChart || !data) return;

                historyChart.data.labels = data.labels;
                historyChart.data.datasets[0].data = data.distances;
                historyChart.update('none');

                const pointsEl = document.getElementById('chart-points');
                if (pointsEl) pointsEl.textContent = data.count;
            })
            .catch(() => {});
    }

    // --- Fetch dashboard data (steps, alerts, etc.) ---
    function fetchDashboard() {
        fetch(API_DASHBOARD)
            .then(res => res.json())
            .then(data => {
                if (data.steps) {
                    // Build zones only on first load or if steps changed
                    if (!stepsData) {
                        stepsData = data.steps;
                        buildTargetZones(stepsData);
                    }
                    stepsData = data.steps;
                }
            })
            .catch(() => {});
    }

    // --- Hold bar (client-side interpolation for smooth countdown) ---
    const HOLD_REQUIRED = 3.0;
    let holdInZone = false;
    let holdEnteredAt = null;       // performance.now() when entered zone
    let holdLastServerTime = 0;     // last hold_time from server
    let holdAnimFrame = null;
    let holdValidatedStep = null;   // track last validated step to show toast

    function holdTick() {
        const wrapper = document.getElementById('hold-bar-wrapper');
        const fill = document.getElementById('hold-bar-fill');
        const timeEl = document.getElementById('hold-bar-time');
        if (!wrapper || !fill) return;

        if (!holdInZone) {
            wrapper.classList.remove('visible', 'in-zone');
            fill.style.width = '0%';
            fill.classList.remove('complete');
            return;
        }

        // Interpolate: time since we entered zone client-side
        const elapsed = (performance.now() - holdEnteredAt) / 1000;
        const t = Math.min(elapsed, HOLD_REQUIRED);
        const pct = (t / HOLD_REQUIRED) * 100;

        wrapper.classList.add('visible', 'in-zone');
        fill.style.width = pct + '%';

        if (t >= HOLD_REQUIRED) {
            fill.classList.add('complete');
            if (timeEl) timeEl.textContent = 'VALIDATION...';
        } else {
            fill.classList.remove('complete');
            if (timeEl) timeEl.textContent = t.toFixed(1) + ' / ' + HOLD_REQUIRED.toFixed(1) + 's';
        }

        holdAnimFrame = requestAnimationFrame(holdTick);
    }

    function holdStart() {
        if (holdInZone) return;
        holdInZone = true;
        holdEnteredAt = performance.now();
        holdAnimFrame = requestAnimationFrame(holdTick);
    }

    function holdStop() {
        holdInZone = false;
        holdEnteredAt = null;
        if (holdAnimFrame) {
            cancelAnimationFrame(holdAnimFrame);
            holdAnimFrame = null;
        }
        // One last tick to hide
        holdTick();
    }

    // --- Validation toast ---
    function showValidationToast(stepOrder, label) {
        // Remove existing toast
        const old = document.querySelector('.validation-toast');
        if (old) old.remove();

        const toast = document.createElement('div');
        toast.className = 'validation-toast';
        toast.innerHTML = '<div class="toast-icon"></div>' +
            '<div class="toast-text">' +
            '<div class="toast-title">ETAPE ' + stepOrder + ' VALIDEE</div>' +
            '<div class="toast-label">' + escapeHtml(label) + '</div>' +
            '</div>';
        document.body.appendChild(toast);

        // Force reflow then animate in
        toast.offsetHeight;
        toast.classList.add('show');

        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 400);
        }, 3000);
    }

    // --- Fetch live sensor ---
    let liveDistance = null;
    let prevStepOrder = null;
    let liveInitialized = false; // eviter les toasts au chargement de la page

    function fetchSensorLive() {
        fetch(API_LIVE)
            .then(res => res.json())
            .then(data => {
                if (!data) return;
                const dist = parseInt(data.distance_cm);
                if (isNaN(dist)) return;

                liveDistance = dist;
                updateSensorLine(dist);

                // Hold bar: sync with server state
                const serverInZone = data.in_zone || false;
                const serverHoldTime = parseFloat(data.hold_time) || 0;
                const currentStep = data.current_step || null;

                if (serverInZone && !holdInZone) {
                    // Server says in zone, start local timer
                    // Sync: offset our start so local time matches server
                    holdStart();
                    if (serverHoldTime > 0) {
                        holdEnteredAt = performance.now() - (serverHoldTime * 1000);
                    }
                } else if (!serverInZone && holdInZone) {
                    holdStop();
                }

                // Premier poll — initialiser sans toast
                if (!liveInitialized) {
                    liveInitialized = true;
                    prevStepOrder = currentStep;
                } else if (prevStepOrder !== currentStep) {
                    // Step a change — verifier si c'est une vraie validation
                    if (prevStepOrder !== null && currentStep !== null && currentStep !== prevStepOrder) {
                        // Step avance — l'etape precedente a ete validee
                        if (stepsData) {
                            const validated = stepsData.find(s => s.step_order === prevStepOrder);
                            if (validated && isUnlocked(prevStepOrder)) {
                                showValidationToast(prevStepOrder, validated.label);
                            }
                        }
                        holdStop();
                        fetchDashboard();
                    } else if (prevStepOrder !== null && currentStep === null) {
                        // Toutes les etapes terminees
                        if (stepsData) {
                            const last = stepsData.find(s => s.step_order === prevStepOrder);
                            if (last && isUnlocked(prevStepOrder)) {
                                showValidationToast(prevStepOrder, last.label);
                            }
                        }
                        holdStop();
                        fetchDashboard();
                    }
                    prevStepOrder = currentStep;
                }

                if (stepsData) {
                    updateStepsState(stepsData, dist);
                }
            })
            .catch(() => {});
    }

    // --- Period buttons ---
    function initPeriodButtons() {
        const btns = document.querySelectorAll('.period-btn');
        btns.forEach(btn => {
            btn.addEventListener('click', () => {
                btns.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                currentPeriod = btn.dataset.period;

                const periodLabel = document.getElementById('chart-period');
                if (periodLabel) periodLabel.textContent = btn.textContent;

                fetchHistory();
            });
        });
    }

    // --- Utility ---
    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    // --- Reset button ---
    function initResetButton() {
        const btn = document.getElementById('btn-reset');
        if (!btn) return;
        btn.addEventListener('click', () => {
            if (!confirm('Remettre toutes les donnees a zero (enigmes + capteur) ?')) return;
            fetch('/php/api/reset.php', { method: 'POST' })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        resetUnlocked();
                        stepsData = null;
                        fetchDashboard();
                        fetchHistory();
                    }
                })
                .catch(() => {});
        });
    }

    // --- Init ---
    buildRuler();
    initChart();
    initPeriodButtons();
    initResetButton();

    // Initial fetches
    fetchDashboard();
    fetchSensorLive();
    fetchHistory();

    // Loops
    setInterval(fetchSensorLive, 200);      // Live sensor (fast)
    setInterval(fetchDashboard, 5000);       // Steps/alerts (slow)
    setInterval(fetchHistory, 10000);        // Chart refresh

})();
