    </main>

    <footer class="iss-footer">
        <div class="footer-left">
            <span class="footer-label">SYS STATUS</span>
            <span class="footer-status online">NOMINAL</span>
        </div>
        <div class="footer-center">
            <span class="footer-mission">MISSION ISS-G5E // SALLE DE STOCKAGE</span>
        </div>
        <div class="footer-right">
            <span class="footer-clock" id="footer-clock">--:--:--</span>
            <span class="footer-label">UTC</span>
        </div>
    </footer>

    <script src="/js/main.js"></script>
    <?php if (isset($extraJs)): ?>
        <script src="/js/<?= $extraJs ?>"></script>
    <?php endif; ?>
</body>
</html>
