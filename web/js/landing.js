/* ============================================================
   LANDING PAGE — ESCAPE THE ISS
   Stars canvas, typing effect, scroll reveals
   ============================================================ */

(function () {
    'use strict';

    /* ---- STAR CANVAS ---- */
    const canvas = document.getElementById('stars-canvas');
    if (canvas) {
        const ctx = canvas.getContext('2d');
        let stars = [];
        const STAR_COUNT = 200;

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
                    r: Math.random() * 1.3 + 0.2,
                    a: Math.random(),
                    da: (Math.random() - 0.5) * 0.008
                });
            }
        }

        function drawStars() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            stars.forEach(s => {
                s.a += s.da;
                if (s.a > 1 || s.a < 0.1) s.da *= -1;
                ctx.beginPath();
                ctx.arc(s.x, s.y, s.r, 0, Math.PI * 2);
                ctx.fillStyle = `rgba(224, 232, 240, ${s.a})`;
                ctx.fill();
            });
            requestAnimationFrame(drawStars);
        }

        resize();
        initStars();
        drawStars();
        window.addEventListener('resize', () => { resize(); initStars(); });
    }

    /* ---- TYPING EFFECT ---- */
    const terminal = document.getElementById('hero-terminal');
    if (terminal) {
        const lines = [
            '> Breche detectee dans le module de stockage...',
            '> Stock de brioches : DISPARU.',
            '> Equilibre station : CRITIQUE.',
            '> Mission : Reorganiser la salle de stockage.'
        ];

        let lineIdx = 0;
        let charIdx = 0;
        let output = '';

        function type() {
            if (lineIdx >= lines.length) {
                terminal.innerHTML = output + '<span class="terminal-cursor">_</span>';
                return;
            }

            const line = lines[lineIdx];
            if (charIdx < line.length) {
                output += line[charIdx];
                charIdx++;
                terminal.innerHTML = output + '<span class="terminal-cursor">_</span>';
                setTimeout(type, 25 + Math.random() * 20);
            } else {
                output += '<br>';
                lineIdx++;
                charIdx = 0;
                setTimeout(type, 400);
            }
        }

        // Start typing after title animation
        setTimeout(type, 1200);
    }

    /* ---- SCROLL REVEALS ---- */
    const revealEls = document.querySelectorAll('.reveal');

    function checkReveals() {
        const trigger = window.innerHeight * 0.85;
        revealEls.forEach(el => {
            const top = el.getBoundingClientRect().top;
            if (top < trigger) {
                el.classList.add('visible');
            }
        });
    }

    window.addEventListener('scroll', checkReveals, { passive: true });
    // Initial check
    setTimeout(checkReveals, 100);

    /* ---- SCROLL HINT CLICK ---- */
    const scrollHint = document.getElementById('scroll-hint');
    if (scrollHint) {
        scrollHint.addEventListener('click', () => {
            const briefing = document.getElementById('briefing');
            if (briefing) briefing.scrollIntoView({ behavior: 'smooth' });
        });
    }

    /* ---- STAGGER REVEAL DELAYS ---- */
    revealEls.forEach((el, i) => {
        const section = el.closest('section');
        const siblings = section ? section.querySelectorAll('.reveal') : [];
        const idx = Array.from(siblings).indexOf(el);
        el.style.transitionDelay = (idx * 0.1) + 's';
    });

})();
