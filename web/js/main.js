/* ============================================================
   ISS G5E — Main JS (Stars, Clock, Effects)
   ============================================================ */

(function () {
    'use strict';

    /* --- Star field canvas --- */
    const canvas = document.getElementById('stars-canvas');
    if (canvas) {
        const ctx = canvas.getContext('2d');
        let stars = [];
        const STAR_COUNT = 120;

        function resize() {
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
        }

        function initStars() {
            stars = [];
            for (let i = 0; i < STAR_COUNT; i++) {
                stars.push({
                    x: Math.random() * canvas.width,
                    y: Math.random() * canvas.height,
                    r: Math.random() * 1.2 + 0.3,
                    baseAlpha: Math.random() * 0.6 + 0.2,
                    phase: Math.random() * Math.PI * 2,
                    speed: Math.random() * 0.003 + 0.001
                });
            }
        }

        let starsRunning = true;

        function drawStars(time) {
            if (document.hidden) { starsRunning = false; return; }
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            for (const s of stars) {
                const alpha = s.baseAlpha + Math.sin(time * s.speed + s.phase) * 0.2;
                ctx.beginPath();
                ctx.arc(s.x, s.y, s.r, 0, Math.PI * 2);
                ctx.fillStyle = `rgba(200, 220, 255, ${Math.max(0.05, alpha)})`;
                ctx.fill();
            }
            requestAnimationFrame(drawStars);
        }

        resize();
        initStars();
        requestAnimationFrame(drawStars);
        window.addEventListener('resize', () => { resize(); initStars(); });

        document.addEventListener('visibilitychange', () => {
            if (!document.hidden && !starsRunning) {
                starsRunning = true;
                requestAnimationFrame(drawStars);
            }
        });
    }

    /* --- UTC Clock --- */
    const clockEl = document.getElementById('footer-clock');
    if (clockEl) {
        function updateClock() {
            const now = new Date();
            const h = String(now.getUTCHours()).padStart(2, '0');
            const m = String(now.getUTCMinutes()).padStart(2, '0');
            const s = String(now.getUTCSeconds()).padStart(2, '0');
            clockEl.textContent = `${h}:${m}:${s}`;
        }
        updateClock();
        setInterval(updateClock, 1000);
    }

    /* --- Glitch effect on logo (subtle, rare) --- */
    const logoText = document.querySelector('.logo-text');
    if (logoText) {
        setInterval(() => {
            if (Math.random() > 0.92) {
                logoText.style.animation = 'glitchFlicker 0.3s ease';
                setTimeout(() => { logoText.style.animation = ''; }, 300);
            }
        }, 4000);
    }

})();
