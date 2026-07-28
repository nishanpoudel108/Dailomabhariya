<?php
require_once __DIR__ . '/session.php';

// Safe extraction layer for core config parameters
$configFile = __DIR__ . '/../config/config.json';
$config = file_exists($configFile) ? json_decode(file_get_contents($configFile), true) : [];
$appName = $config['app']['name'] ?? 'दैलोमा भरिया';
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($appName) ?> | Marketplace Platform</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Document level baseline font override */
        body {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
        }
    </style>
</head>
<body>

<!-- Global Navigation Utility Shell Component -->
<header style="background: var(--surface); border-bottom: 1px solid var(--border-color); padding: 1.25rem 2rem; position: sticky; top: 0; z-index: 100; transition: background-color 0.3s ease, border-color 0.3s ease;">
    <div style="max-width: 1400px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center;">
        
        <!-- Branding Logomark Anchor -->
        <a href="index.php" style="text-decoration: none; font-size: 1.45rem; font-weight: 800; color: var(--primary); letter-spacing: -0.03em; display: flex; align-items: center; gap: 0.5rem;">
            <i class="fa-solid fa-leaf"></i> <?= htmlspecialchars($appName) ?>
        </a>
        
        <!-- User Context State Handlers -->
        <div style="display: flex; align-items: center; gap: 1.75rem;">
            <?php if (isset($_SESSION['user_id'])): ?>
                <span style="font-size: 0.92rem; font-weight: 600; color: var(--text-dark); display: flex; align-items: center; gap: 0.4rem;">
                    <i class="fa-regular fa-circle-user" style="color: var(--text-muted); font-size: 1.1rem;"></i> 
                    नमस्ते, <?= htmlspecialchars($_SESSION['name'] ?? 'User') ?>
                </span>
                
                <?php if (($_SESSION['role'] ?? '') === 'Super Admin'): ?>
                    <a href="admin/index.php" style="color: var(--primary-dark); background: var(--primary-light); text-decoration: none; font-size: 0.85rem; font-weight: 700; padding: 0.4rem 0.8rem; border-radius: 6px; text-transform: uppercase; letter-spacing: 0.02em;">Dashboard</a>
                <?php endif; ?>

                <a href="logout.php" style="color: #ef4444; text-decoration: none; font-weight: 700; font-size: 0.92rem; display: flex; align-items: center; gap: 0.35rem; transition: color 0.2s;">
                    <i class="fa-solid fa-power-off" style="font-size: 0.85rem;"></i> Logout
                </a>
            <?php else: ?>
                <a href="login.php" style="text-decoration: none; color: var(--text-dark); font-weight: 600; font-size: 0.92rem; transition: color 0.2s;">Sign In</a>
                <a href="register.php" style="background: var(--primary); color: white; padding: 0.6rem 1.2rem; border-radius: 8px; text-decoration: none; font-weight: 700; font-size: 0.92rem; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.15); transition: all 0.2s ease;">Register</a>
            <?php endif; ?>
        </div>
    </div>
</header>