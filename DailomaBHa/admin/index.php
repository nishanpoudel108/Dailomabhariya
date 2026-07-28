<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/database.php';
check_role(['Super Admin']);

$db = Database::getInstance();

// Metric Queries
$totalOrders = $db->query("SELECT COUNT(id) FROM orders")->fetchColumn();
$revenue = $db->query("SELECT SUM(grand_total) FROM orders WHERE status = 'Delivered'")->fetchColumn() ?? 0;
$lowStock = $db->query("SELECT COUNT(id) FROM products WHERE stock <= 5")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>दैलोमा भरिया | Admin Center</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Design Tokens Matrix System */
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
            --shadow-md: 0 4px 6px -1px rgba(0,0,0,0.05), 0 2px 4px -1px rgba(0,0,0,0.03);
            --radius-md: 12px;
            --radius-lg: 16px;
        }

        /* Explicit Dark Mode Overrides Engine */
        [data-theme="dark"] {
            --bg-main: #0f172a;
            --surface: #1e293b;
            --text-dark: #f8fafc;
            --text-muted: #94a3b8;
            --border-color: rgba(51, 65, 85, 0.5);
            --primary-light: #064e3b;
            --primary-dark: #34d399;
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
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        /* Master Admin Portal Structure */
        .dashboard-container {
            display: grid;
            grid-template-columns: 280px 1fr;
            min-height: 100vh;
        }

        /* Component Navigation Aside */
        .sidebar {
            background: var(--surface);
            border-right: 1px solid var(--border-color);
            padding: 2.25rem 1.5rem;
            display: flex;
            flex-direction: column;
            transition: background-color 0.3s ease, border-color 0.3s ease;
        }

        .sidebar h2 {
            font-size: 1.35rem;
            font-weight: 800;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.25rem;
        }

        .admin-badge {
            font-size: 0.75rem;
            background: #ef4444;
            color: white;
            padding: 2px 8px;
            border-radius: 4px;
            font-weight: 700;
            display: inline-block;
            margin-bottom: 2.5rem;
            width: max-content;
            text-transform: uppercase;
            letter-spacing: 0.05em;
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

        /* Workspace Main Frame */
        .main-content {
            padding: 3rem 4%;
            width: 100%;
        }

        .control-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2.5rem;
            gap: 1rem;
        }

        .control-header h1 {
            font-size: 1.75rem;
            font-weight: 800;
            letter-spacing: -0.02em;
        }

        /* Action Component Toggles */
        .btn-theme {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.65rem 1.25rem;
            background: var(--surface);
            border: 1px solid var(--border-color);
            color: var(--text-dark);
            border-radius: var(--radius-md);
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            box-shadow: var(--shadow-md);
            transition: all 0.2s ease;
        }

        .btn-theme:hover {
            background: var(--bg-main);
            border-color: var(--text-muted);
        }

        /* High Impact Information Metric Grids */
        .metric-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.75rem;
            margin-bottom: 2.5rem;
        }

        .card {
            background: var(--surface);
            border-radius: var(--radius-lg);
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-md);
            padding: 2rem;
            transition: background-color 0.3s ease, border-color 0.3s ease;
        }

        .metric-card {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .metric-info h3 {
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-muted);
            margin-bottom: 0.5rem;
        }

        .metric-val {
            font-size: 2.25rem;
            font-weight: 800;
            line-height: 1;
        }

        .metric-icon-box {
            width: 56px;
            height: 56px;
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        /* Chart Section Container */
        .chart-section h3 {
            font-size: 1.15rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 1.5rem;
        }

        @media (max-width: 992px) {
            .dashboard-container { grid-template-columns: 1fr; }
            .sidebar { display: none; }
            .control-header { flex-direction: column; align-items: flex-start; }
            .btn-theme { width: 100%; justify-content: center; }
        }
    </style>
</head>
<body>

<div class="dashboard-container">
    <!-- Admin Center Sidebar Component Nav -->
    <aside class="sidebar">
        <h2><i class="fa-solid fa-leaf"></i> दैलोमा भरिया</h2>
        <span class="admin-badge">System Operator</span>
        <nav>
            <a href="index.php" class="active"><i class="fa-solid fa-chart-pie"></i> Dashboard</a>
            <a href="products.php"><i class="fa-solid fa-box"></i> Products</a>
            <a href="orders.php"><i class="fa-solid fa-truck"></i> Orders</a>
            <a href="../logout.php" style="margin-top: 4rem; color: #ef4444;"><i class="fa-solid fa-right-from-bracket" style="color: #ef4444;"></i> Sign Out</a>
        </nav>
    </aside>

    <!-- Admin Operational Screen Workspace View -->
    <main class="main-content">
        <header class="control-header">
            <div>
                <h1>Dashboard Control Panel</h1>
                <p style="color: var(--text-muted); font-size: 0.95rem; margin-top: 0.25rem;">Real-time business analytics and logistical monitoring metrics platform.</p>
            </div>
            <button id="themeToggleBtn" class="btn-theme">
                <i class="fa-solid fa-moon"></i> Toggle Workspace View
            </button>
        </header>

        <!-- KPI Executive Summary Row Module -->
        <section class="metric-grid">
            <div class="card metric-card">
                <div class="metric-info">
                    <h3>Total Shipments</h3>
                    <div class="metric-val" style="color: var(--primary);"><?= htmlspecialchars($totalOrders) ?></div>
                </div>
                <div class="metric-icon-box" style="background: var(--primary-light); color: var(--primary-dark);">
                    <i class="fa-solid fa-truck-ramp-box"></i>
                </div>
            </div>
            <div class="card metric-card">
                <div class="metric-info">
                    <h3>Total Earnings</h3>
                    <div class="metric-val" style="color: #3b82f6;">Rs. <?= htmlspecialchars(number_format($revenue, 0)) ?></div>
                </div>
                <div class="metric-icon-box" style="background: rgba(59, 130, 246, 0.1); color: #3b82f6;">
                    <i class="fa-solid fa-wallet"></i>
                </div>
            </div>
            <div class="card metric-card">
                <div class="metric-info">
                    <h3>Critical Stock Alerts</h3>
                    <div class="metric-val" style="color: #ef4444;"><?= htmlspecialchars($lowStock) ?></div>
                </div>
                <div class="metric-icon-box" style="background: rgba(239, 68, 68, 0.1); color: #ef4444;">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
            </div>
        </section>

        <!-- Strategic Analytics Visualization Workspace -->
        <section class="card chart-section">
            <h3><i class="fa-solid fa-chart-line" style="color: var(--primary); margin-right: 6px;"></i> Strategic Revenue Timeline</h3>
            <div style="position: relative; width: 100%;">
                <canvas id="analyticsChart" style="max-height: 380px; width: 100%;"></canvas>
            </div>
        </section>
    </main>
</div>

<script>
// Theme Switching Core Handler System
const themeToggleBtn = document.getElementById('themeToggleBtn');
themeToggleBtn.addEventListener('click', () => {
    const currentTheme = document.documentElement.getAttribute('data-theme');
    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
    document.documentElement.setAttribute('data-theme', newTheme);
    
    // Switch inline icons inside theme control mechanism
    const icon = themeToggleBtn.querySelector('i');
    if(newTheme === 'dark') {
        icon.className = 'fa-solid fa-sun';
    } else {
        icon.className = 'fa-solid fa-moon';
    }
});

// Analytics Graph Rendering Data Arrays 
const ctx = document.getElementById('analyticsChart').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
        datasets: [{
            label: 'Net Profits (NPR)',
            data: [12000, 19000, 30000, 50000, 45000, 78000],
            borderColor: '#10b981',
            borderWidth: 3,
            tension: 0.38,
            fill: true,
            backgroundColor: 'rgba(16, 185, 129, 0.06)',
            pointBackgroundColor: '#10b981',
            pointRadius: 4,
            pointHoverRadius: 6
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { 
            legend: { display: false } 
        },
        scales: { 
            y: { 
                beginAtZero: true,
                grid: {
                    color: 'rgba(148, 163, 184, 0.1)'
                }
            },
            x: {
                grid: {
                    display: false
                }
            }
        }
    }
});
</script>
</body>
</html>