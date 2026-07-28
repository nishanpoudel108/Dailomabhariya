<?php
// Safeguard loading configuration variables if not already initialized inside parent routes
if (!isset($config)) {
    $configFile = __DIR__ . '/../config/config.json';
    $config = file_exists($configFile) ? json_decode(file_get_contents($configFile), true) : ['app' => ['name' => 'दैलोमा भरिया']];
}
?>
<footer style="background: var(--surface); border-top: 1px solid var(--border-color); padding: 2.5rem 1rem; text-align: center; margin-top: auto; transition: background-color 0.3s ease, border-color 0.3s ease;">
    <p style="font-size: 0.9rem; color: var(--text-muted); font-weight: 500; letter-spacing: -0.01em;">
        &copy; <?= date('Y') ?> **<?= htmlspecialchars($config['app']['name'] ?? 'System Core') ?>**. All Rights Reserved. Built with Native Stack Architecture.
    </p>
</footer>

<!-- App Global Framework Actions Runtime Logic Asset -->
<script src="../assets/js/app.js"></script>
</body>
</html>