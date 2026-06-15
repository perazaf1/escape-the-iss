/* ============================================================
   ISS G5E — Admin / Game Master Panel JS
   ============================================================ */

(function () {
    'use strict';

    const API_DASHBOARD = '/php/api/dashboard.php';
    const API_FORCE = '/php/api/force_step.php';
    const API_RESET = '/php/api/reset.php';

    // Fetch dashboard data (rooms progress & steps status)
    function fetchAdminData() {
        fetch(API_DASHBOARD)
            .then(res => res.json())
            .then(data => {
                if (data.rooms) renderRooms(data.rooms);
                if (data.steps) renderSteps(data.steps);
            })
            .catch(() => {});
    }

    // Render left column (all rooms progress)
    function renderRooms(rooms) {
        const container = document.getElementById('admin-rooms-list');
        if (!container) return;

        let html = '';
        rooms.forEach(room => {
            const isOurs = room.salle === 'G5E';
            html += `
                <div class="admin-room-card ${isOurs ? 'is-ours' : ''}">
                    <div class="admin-room-id">${room.salle}</div>
                    <div class="admin-room-name">${escapeHtml(room.nom_usage)}</div>
                    <div class="admin-room-progress">
                        <div class="admin-room-bar">
                            <div class="admin-room-fill" style="width: ${room.progress}%"></div>
                        </div>
                        <div class="admin-room-pct">${room.progress}%</div>
                    </div>
                </div>
            `;
        });
        container.innerHTML = html;
    }

    // Render right column (bypass steps)
    function renderSteps(steps) {
        const container = document.getElementById('admin-steps-list');
        if (!container) return;

        let html = '';
        steps.forEach(step => {
            const isUnlocked = parseInt(step.unlocked) === 1;
            const isValidated = step.validated;
            
            let statusClass = 'locked';
            let statusText = 'VERROUILLE';
            if (isValidated) {
                statusClass = 'validated';
                statusText = 'VALIDE';
            } else if (isUnlocked) {
                statusClass = 'pending';
                statusText = 'EN ATTENTE CAPTEUR';
            }

            html += `
                <div class="admin-step-card">
                    <div class="admin-step-header">
                        <span class="admin-step-order">ETAPE ${step.step_order}</span>
                        <span class="admin-step-status ${statusClass}">${statusText}</span>
                    </div>
                    <div class="admin-step-title">${escapeHtml(step.label)}</div>
                    <div class="admin-step-target">Cible : ${step.target_distance_cm} cm (&plusmn; ${step.tolerance_cm})</div>
                    
                    <div class="admin-step-actions">
                        <button class="admin-btn admin-btn-unlock" data-step="${step.step_order}" 
                            ${isUnlocked || isValidated ? 'disabled' : ''}>
                            Bypass Devinette
                        </button>
                        <button class="admin-btn admin-btn-validate" data-step="${step.step_order}" 
                            ${isValidated || !isUnlocked ? 'disabled' : ''}>
                            Forcer Validation
                        </button>
                    </div>
                </div>
            `;
        });
        container.innerHTML = html;
        bindBypassButtons();
    }

    // Bind bypass buttons to API
    function bindBypassButtons() {
        document.querySelectorAll('.admin-btn-unlock').forEach(btn => {
            btn.addEventListener('click', function() {
                const step = this.dataset.step;
                if (!confirm(`Forcer le déverrouillage de l'étape ${step} (bypasser l'énigme logicielle) ?`)) return;
                forceAction(step, 'unlock');
            });
        });

        document.querySelectorAll('.admin-btn-validate').forEach(btn => {
            btn.addEventListener('click', function() {
                const step = this.dataset.step;
                if (!confirm(`Forcer la VALIDATION de l'étape ${step} ? Cela validera l'étape comme si le capteur l'avait détectée.`)) return;
                forceAction(step, 'validate');
            });
        });
    }

    function forceAction(stepOrder, action) {
        fetch(API_FORCE, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ step_order: stepOrder, action: action })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                fetchAdminData(); // Refresh immediately
            } else {
                alert('Erreur: ' + (data.error || 'inconnue'));
            }
        })
        .catch(err => alert('Erreur réseau.'));
    }

    // Reset button
    function initResetButton() {
        const btn = document.getElementById('btn-admin-reset');
        if (!btn) return;
        btn.addEventListener('click', () => {
            if (!confirm('ATTENTION ! Remettre toutes les donnees a zero pour TOUTE l\'équipe G5E (enigmes + capteur + progression) ?')) return;
            fetch(API_RESET, { method: 'POST' })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        // Supprimer aussi le cache local des énigmes si le GM l'avait sur son navigateur
                        localStorage.removeItem('g5e_unlocked');
                        alert('Reset effectué.');
                        fetchAdminData();
                    } else {
                        alert('Erreur: ' + (data.error || 'inconnue'));
                    }
                })
                .catch(() => alert('Erreur réseau.'));
        });
    }

    // Utility
    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    // Init
    initResetButton();
    fetchAdminData();
    setInterval(fetchAdminData, 3000); // Poll every 3s

})();
