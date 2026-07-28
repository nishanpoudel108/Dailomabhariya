<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/database.php';
check_role(['Customer']);

$db = Database::getInstance();
$orderId = (int)($_GET['order_id'] ?? 0);

// Fallback: If no order ID is provided in the URL, fetch their most recent active order
if ($orderId === 0) {
    $fallbackStmt = $db->prepare("SELECT id FROM orders WHERE user_id = :uid ORDER BY id DESC LIMIT 1");
    $fallbackStmt->execute(['uid' => $_SESSION['user_id']]);
    $latestOrder = $fallbackStmt->fetch();
    
    if ($latestOrder) {
        $orderId = (int)$latestOrder['id'];
    } else {
        echo "<script>alert('You do not have any orders to track yet!'); window.location.href='index.php';</script>";
        exit;
    }
}

$stmt = $db->prepare("SELECT * FROM orders WHERE id = :oid AND user_id = :uid");
$stmt->execute(['oid' => $orderId, 'uid' => $_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order) {
    die("Order reference index tracking metric not found.");
}

// Map each state to a user-friendly tracking title and descriptive font-awesome icon
$statuses = [
    ['title' => 'Pending', 'icon' => 'fa-clock'],
    ['title' => 'Accepted', 'icon' => 'fa-circle-check'],
    ['title' => 'Packing', 'icon' => 'fa-box-open'],
    ['title' => 'Ready', 'icon' => 'fa-clipboard-check'],
    ['title' => 'Out for Delivery', 'icon' => 'fa-truck-fast'],
    ['title' => 'Delivered', 'icon' => 'fa-house-chimney-user']
];

$orderStatus = $order['status'];
$currentIdx = array_search(strtolower($orderStatus), array_map(function($s) { return strtolower($s['title']); }, $statuses));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Order #DB-<?= $orderId ?> | दैलोमा भरिया</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Design Tokens System */
        :root {
            --primary: #10b981;
            --primary-hover: #059669;
            --primary-light: #ecfdf5;
            --primary-dark: #064e3b;
            --text-dark: #1e293b;
            --text-muted: #64748b;
            --bg-main: #f8fafc;
            --surface: #ffffff;
            --border-color: rgba(226, 232, 240, 0.8);
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.05);
            --shadow-md: 0 4px 6px -1px rgba(0,0,0,0.05), 0 2px 4px -1px rgba(0,0,0,0.03);
            --radius-md: 12px;
            --radius-lg: 16px;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background-color: var(--bg-main);
            color: var(--text-dark);
            line-height: 1.5;
        }

        /* Dashboard View Layout Splitter */
        .dashboard-container {
            display: grid;
            grid-template-columns: 280px 1fr;
            min-height: 100vh;
        }

        /* Sidebar Component */
        .sidebar {
            background: var(--surface);
            border-right: 1px solid var(--border-color);
            padding: 2.25rem 1.5rem;
            display: flex;
            flex-direction: column;
        }

        .sidebar h3 {
            font-size: 1.35rem;
            font-weight: 800;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.25rem;
        }

        .user-welcome {
            font-size: 0.85rem;
            color: var(--text-muted);
            margin-bottom: 2.5rem;
            font-weight: 500;
        }

        .sidebar nav {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .sidebar nav a {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.85rem 1rem;
            color: var(--text-dark);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95rem;
            border-radius: var(--radius-md);
            transition: all 0.2s ease;
        }

        .sidebar nav a i {
            width: 20px;
            font-size: 1.1rem;
            color: var(--text-muted);
        }

        .sidebar nav a:hover {
            background: var(--bg-main);
            color: var(--primary);
        }

        .sidebar nav a.active {
            background: var(--primary-light);
            color: var(--primary-dark);
        }

        /* Content Container Workspace */
        .main-content {
            padding: 3rem 4%;
            width: 100%;
        }

        .card {
            background: var(--surface);
            border-radius: var(--radius-lg);
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-md);
            padding: 2.5rem;
        }

        .header-title {
            font-size: 1.6rem;
            font-weight: 800;
            color: var(--text-dark);
        }

        /* Enhanced Interactive Stepper Timeline Graphic */
        .timeline-container {
            display: flex;
            justify-content: space-between;
            position: relative;
            margin: 4rem 0;
            padding: 0 0.5rem;
        }

        .timeline-container::before {
            content: '';
            position: absolute;
            top: 22px;
            left: 0;
            width: 100%;
            height: 4px;
            background: #e2e8f0;
            z-index: 1;
        }

        .timeline-progress {
            position: absolute;
            top: 22px;
            left: 0;
            height: 4px;
            background: var(--primary);
            z-index: 2;
            transition: width 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .timeline-step {
            position: relative;
            z-index: 3;
            text-align: center;
            flex: 1;
        }

        .timeline-dot {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: #f1f5f9;
            border: 3px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 0.75rem auto;
            font-size: 1.1rem;
            color: #94a3b8;
            transition: all 0.3s ease;
        }

        .timeline-step-title {
            display: block;
            font-size: 0.85rem;
            font-weight: 700;
            color: var(--text-muted);
            transition: color 0.3s ease;
        }

        /* State Modifiers selectors handles */
        .timeline-step.active .timeline-dot {
            background: var(--surface);
            color: var(--primary);
            border-color: var(--primary);
            box-shadow: 0 0 0 5px var(--primary-light);
            transform: scale(1.1);
        }

        .timeline-step.active .timeline-step-title {
            color: var(--text-dark);
        }

        .timeline-step.completed .timeline-dot {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .timeline-step.completed .timeline-step-title {
            color: var(--primary-dark);
        }

        /* Info Metabar Grid Box */
        .summary-box {
            background: var(--bg-main);
            border: 1px solid var(--border-color);
            padding: 1.5rem;
            border-radius: var(--radius-md);
            margin-top: 2.5rem;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
        }

        .summary-item h5 {
            font-size: 0.82rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-muted);
            margin-bottom: 0.25rem;
        }

        .summary-item p {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--text-dark);
        }

        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
            padding: 0.65rem 1.25rem;
            background: var(--surface);
            border: 1px solid var(--border-color);
            color: var(--text-dark);
            border-radius: var(--radius-md);
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.2s ease;
            margin-top: 2rem;
        }

        .btn-back:hover {
            background: var(--bg-main);
            border-color: var(--text-muted);
            transform: translateX(-2px);
        }

        @media (max-width: 992px) {
            .dashboard-container { grid-template-columns: 1fr; }
            .sidebar { display: none; }
            .timeline-container { flex-direction: column; gap: 2rem; padding-left: 2rem; }
            .timeline-container::before { left: 22px; top: 0; width: 4px; height: 100%; }
            .timeline-progress { left: 22px; top: 0; width: 4px; height: 0%; /* Fallback vertical tracking behavior overrides */ }
            .timeline-step { display: flex; align-items: center; text-align: left; gap: 1rem; }
            .timeline-dot { margin: 0; }
        }
    </style>
</head>
<body>

<div class="dashboard-container">
    <!-- Component Sidebar Injection -->
    <aside class="sidebar">
        <h3><i class="fa-solid fa-leaf"></i> दैलोमा भरिया</h3>
        <p class="user-welcome">Welcome, <?= htmlspecialchars($_SESSION['name']) ?></p>
        <nav>
            <a href="index.php"><i class="fa-solid fa-house"></i> Overview</a>
            <a href="../index.php"><i class="fa-solid fa-basket-shopping"></i> Shop Marketplace</a>
            <a href="track.php" class="active"><i class="fa-solid fa-map-location-dot"></i> Track Order</a>
            <a href="cart.php"><i class="fa-solid fa-cart-shopping"></i> View Cart</a>
            <a href="../logout.php" style="margin-top: 2rem; color: #ef4444;"><i class="fa-solid fa-right-from-bracket" style="color: #ef4444;"></i> Sign Out</a>
        </nav>
    </aside>

    <!-- Main Dynamic Interface -->
    <main class="main-content">
        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem;">
                <div>
                    <h2 class="header-title">Order Tracking Status Summary</h2>
                    <p style="color: var(--text-muted); font-weight: 500; margin-top: 0.25rem;">
                        Order Identification Key: <strong style="color: var(--text-dark);">#DB-<?= $orderId ?></strong>
                    </p>
                </div>
            </div>
            
            <!-- Graphic Step Line Progress Bar Tracker -->
            <div class="timeline-container">
                <div class="timeline-progress" style="width: <?= $currentIdx !== false ? ($currentIdx / (count($statuses) - 1)) * 100 : 0 ?>%;"></div>
                
                <?php foreach ($statuses as $idx => $step): ?>
                    <?php 
                        $class = '';
                        if ($idx === $currentIdx) {
                            $class = 'active';
                        } elseif ($currentIdx !== false && $idx < $currentIdx) {
                            $class = 'completed';
                        }
                    ?>
                    <div class="timeline-step <?= $class ?>">
                        <div class="timeline-dot">
                            <i class="fa-solid <?= $step['icon'] ?>"></i>
                        </div>
                        <span class="timeline-step-title"><?= $step['title'] ?></span>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Ledger Values Panel Metadata Block -->
            <div class="summary-box">
                <div class="summary-item">
                    <h5>Total Bill Amount</h5>
                    <p style="color: var(--primary-dark);">Rs. <?= number_format($order['grand_total'], 2) ?></p>
                </div>
                <div class="summary-item">
                    <h5>Current Status State</h5>
                    <p><i class="fa-solid fa-circle" style="font-size: 0.65rem; color: var(--primary); margin-right: 6px; vertical-align: middle;"></i><?= htmlspecialchars($orderStatus) ?></p>
                </div>
            </div>
            
            <div style="text-align: left;">
                <a href="index.php" class="btn-back">
                    <i class="fa-solid fa-arrow-left-long"></i> Return to Account Overview
                </a>
            </div>
        </div>
    </main>
</div>

</body>
</html>