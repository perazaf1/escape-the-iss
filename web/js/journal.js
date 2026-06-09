/* ============================================================
   JOURNAL DE BORD — ISS G5E
   Polling, filtres severity, auto-scroll
   ============================================================ */

(function () {
    'use strict';

    const API_URL = '/php/api/journal.php';
    const POLL_INTERVAL = 3000;

    const terminalBody = document.getElementById('terminal-body');
    const terminalLoading = document.getElementById('terminal-loading');
    const terminalStatus = document.getElementById('terminal-status');
    const eventCount = document.getElementById('event-count');
    const autoScrollBtn = document.getElementById('auto-scroll-btn');

    const countAll = document.getElementById('count-all');
    const countInfo = document.getElementById('count-info');
    const countWarning = document.getElementById('count-warning');
    const countCritical = document.getElementById('count-critical');

    let currentSeverity = 'all';
    let autoScroll = true;
    let knownIds = new Set();
    let firstLoad = true;
    let pollTimer = null;

    // --- Filter buttons ---
    document.querySelectorAll('.filter-btn[data-severity]').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.filter-btn[data-severity]').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            currentSeverity = btn.dataset.severity;
            firstLoad = true;
            knownIds.clear();
            fetchJournal();
        });
    });

    // --- Auto-scroll toggle ---
    autoScrollBtn.classList.add('active');
    autoScrollBtn.addEventListener('click', () => {
        autoScroll = !autoScroll;
        autoScrollBtn.classList.toggle('active', autoScroll);
    });

    // --- Format timestamp ---
    function formatTime(dateStr) {
        const d = new Date(dateStr);
        const pad = n => String(n).padStart(2, '0');
        return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`;
    }

    // --- Format type label ---
    function formatType(type) {
        return type.replace(/_/g, ' ');
    }

    // --- Create log entry element ---
    function createLogEntry(alert, isNew) {
        const div = document.createElement('div');
        div.className = `log-entry severity-${alert.severity}${isNew ? ' new-entry' : ''}`;
        div.dataset.id = alert.id;

        div.innerHTML =
            `<span class="log-timestamp">${formatTime(alert.created_at)}</span>` +
            `<span class="log-tag tag-${alert.severity}">${alert.severity.toUpperCase()}</span>` +
            `<span class="log-message">${escapeHtml(alert.message)}<span class="log-type">[${formatType(alert.type)}]</span></span>`;

        return div;
    }

    // --- Escape HTML ---
    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    // --- Render empty state ---
    function renderEmpty() {
        terminalBody.innerHTML =
            '<div class="terminal-empty">' +
            '<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">' +
            '<path d="M4 19.5A2.5 2.5 0 016.5 17H20"/>' +
            '<path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"/>' +
            '</svg>' +
            '<span class="terminal-empty-text">Aucun evenement enregistre</span>' +
            '</div>';
    }

    // --- Fetch journal data ---
    async function fetchJournal() {
        try {
            const url = `${API_URL}?severity=${currentSeverity}&limit=200`;
            const resp = await fetch(url);
            if (!resp.ok) throw new Error('HTTP ' + resp.status);

            const data = await resp.json();
            terminalStatus.textContent = 'CONNECTED';

            // Update counts
            countAll.textContent = data.counts.total;
            countInfo.textContent = data.counts.info;
            countWarning.textContent = data.counts.warning;
            countCritical.textContent = data.counts.critical;
            eventCount.textContent = data.counts.total;

            if (data.alerts.length === 0) {
                renderEmpty();
                return;
            }

            if (firstLoad) {
                // Full render
                terminalBody.innerHTML = '';
                data.alerts.forEach(alert => {
                    knownIds.add(String(alert.id));
                    terminalBody.appendChild(createLogEntry(alert, false));
                });
                firstLoad = false;

                if (autoScroll) {
                    terminalBody.scrollTop = terminalBody.scrollHeight;
                }
            } else {
                // Incremental — find new entries
                const newAlerts = data.alerts.filter(a => !knownIds.has(String(a.id)));
                if (newAlerts.length > 0) {
                    // Insert at the top (newest first in API, but we show newest at top)
                    newAlerts.reverse().forEach(alert => {
                        knownIds.add(String(alert.id));
                        const el = createLogEntry(alert, true);
                        terminalBody.insertBefore(el, terminalBody.firstChild);
                    });
                }
            }
        } catch (err) {
            terminalStatus.textContent = 'ERROR';
            terminalStatus.style.color = 'var(--red-critical)';
            console.error('[JOURNAL]', err);
        }
    }

    // --- Remove loading, start polling ---
    setTimeout(() => {
        if (terminalLoading) terminalLoading.remove();
        fetchJournal();
        pollTimer = setInterval(fetchJournal, POLL_INTERVAL);
    }, 800);

})();
