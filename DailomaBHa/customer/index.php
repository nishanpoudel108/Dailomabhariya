<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/database.php';
check_role(['Customer']);

$db = Database::getInstance();
$uid = $_SESSION['user_id'];

// Grab transactional order rows mapped onto the authenticated user
$orders = $db->prepare("SELECT * FROM orders WHERE user_id = :uid ORDER BY id DESC");
$orders->execute(['uid' => $uid]);
$history = $orders->fetchAll();

// Dynamic Category Fetching to eliminate 404 navigation links
$categoryQuery = $db->query("SELECT id, name FROM categories ORDER BY name ASC");
$categories = $categoryQuery->fetchAll();

// Inline calculations for dashboard overview stats
$totalSpent = 0;
$activeCount = 0;
foreach ($history as $row) {
    $totalSpent += $row['grand_total'];
    if (in_array(strtolower($row['status']), ['pending', 'processing', 'shipped'])) {
        $activeCount++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard | दैलोमा भरिया</title>
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
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.05), 0 1px 2px rgba(0,0,0,0.02);
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

        /* Dashboard Structural Grid */
        .dashboard-container {
            display: grid;
            grid-template-columns: 280px 1fr;
            min-height: 100vh;
        }

        /* Sidebar View */
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

        .sidebar nav a, .category-dropdown-btn {
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
            background: transparent;
            border: none;
            width: 100%;
            cursor: pointer;
            text-align: left;
        }

        .sidebar nav a i, .category-dropdown-btn i {
            width: 20px;
            font-size: 1.1rem;
            color: var(--text-muted);
            transition: color 0.2s ease;
        }

        .sidebar nav a:hover, .category-dropdown-btn:hover {
            background: var(--bg-main);
            color: var(--primary);
        }

        .sidebar nav a:hover i, .category-dropdown-btn:hover i {
            color: var(--primary);
        }

        .sidebar nav a.active {
            background: var(--primary-light);
            color: var(--primary-dark);
        }

        .sidebar nav a.active i {
            color: var(--primary);
        }

        /* Category Dropdown Container */
        .category-submenu {
            padding-left: 1.5rem;
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
            margin-top: -0.25rem;
            margin-bottom: 0.5rem;
        }
        
        .category-submenu a {
            padding: 0.5rem 0.75rem !important;
            font-size: 0.88rem !important;
            font-weight: 500 !important;
            color: var(--text-muted) !important;
        }

        .category-submenu a:hover {
            color: var(--primary) !important;
        }

        /* Main Workspace Content Area */
        .main-content {
            padding: 3rem 4%;
            max-width: 1200px;
            width: 100%;
        }

        .main-content h2 {
            font-size: 1.75rem;
            font-weight: 800;
            letter-spacing: -0.02em;
            margin-bottom: 2rem;
        }

        /* Aggregated Overview Statistic Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2.5rem;
        }

        .stat-card {
            background: var(--surface);
            border-radius: var(--radius-md);
            padding: 1.5rem;
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-sm);
            display: flex;
            align-items: center;
            gap: 1.25rem;
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: var(--radius-md);
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 1.25rem;
        }

        .stat-icon.blue { background: #e0f2fe; color: #0284c7; }
        .stat-icon.green { background: #dcfce7; color: #16a34a; }
        .stat-icon.orange { background: #ffedd5; color: #ea580c; }

        .stat-info p {
            font-size: 0.88rem;
            color: var(--text-muted);
            font-weight: 500;
        }

        .stat-info h4 {
            font-size: 1.4rem;
            font-weight: 800;
            color: var(--text-dark);
            margin-top: 2px;
        }

        /* Dashboard Sectional Panels */
        .card {
            background: var(--surface);
            border-radius: var(--radius-lg);
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-md);
            padding: 2rem;
            overflow: hidden;
        }

        .card h3 {
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 1.25rem;
            color: var(--text-dark);
        }

        /* Responsive UI Table Element styling */
        .table-responsive {
            width: 100%;
            overflow-x: auto;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        .data-table th {
            padding: 1rem 0.75rem;
            font-weight: 700;
            color: var(--text-muted);
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom: 2px solid var(--bg-main);
        }

        .data-table td {
            padding: 1.1rem 0.75rem;
            font-size: 0.95rem;
            border-bottom: 1px solid var(--bg-main);
            color: var(--text-dark);
        }

        .data-table tr:last-child td {
            border-bottom: none;
        }

        .data-table tbody tr:hover td {
            background-color: rgba(248, 250, 252, 0.6);
        }

        /* Context Sensitive Status Flags Badges */
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.35rem 0.75rem;
            border-radius: 50px;
            font-size: 0.78rem;
            font-weight: 700;
            text-transform: capitalize;
        }
        
        .status-badge.delivered { background-color: #dcfce7; color: #15803d; }
        .status-badge.pending { background-color: #fef9c3; color: #a16207; }
        .status-badge.processing, .status-badge.shipped { background-color: #e0f2fe; color: #0369a1; }
        .status-badge.cancelled { background-color: #fee2e2; color: #b91c1c; }

        @media (max-width: 992px) {
            .dashboard-container {
                grid-template-columns: 1fr;
            }
            .sidebar {
                display: none;
            }
        }
    </style>
</head>
<body>

<div class="dashboard-container">
    <aside class="sidebar">
        <h3><i class="fa-solid fa-leaf"></i> दैलोमा भरिया</h3>
        <p class="user-welcome">Welcome, <?= htmlspecialchars($_SESSION['name'] ?? 'Customer') ?></p>
        <nav>
            <a href="index.php" class="active"><i class="fa-solid fa-house"></i> Overview</a>
            
            <!-- Category Browsing Module -->
            <div class="category-dropdown">
                <div class="category-dropdown-btn"><i class="fa-solid fa-layer-group"></i> Browse Categories</div>
                <div class="category-submenu">
                    <?php foreach($categories as $cat): ?>
                        <!-- Ensure your marketplace layout handling target script matches this path -->
                        <a href="../category.php?id=<?= $cat['id'] ?>">
                            <i class="fa-solid fa-chevron-right" style="font-size: 0.7rem;"></i> <?= htmlspecialchars($cat['name']) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <a href="../index.php"><i class="fa-solid fa-basket-shopping"></i> Shop Marketplace</a>
            <a href="track.php"><i class="fa-solid fa-map-location-dot"></i> Track Order</a>
            <a href="cart.php"><i class="fa-solid fa-cart-shopping"></i> View Cart</a>
            <a href="../logout.php" style="margin-top: 2rem; color: #ef4444;"><i class="fa-solid fa-right-from-bracket" style="color: #ef4444;"></i> Sign Out</a>
        </nav>
    </aside>

    <main class="main-content">
        <h2>Your Account Overview</h2>
        
        <!-- Live Computed Summary Metrics Metrics Row -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon blue"><i class="fa-solid fa-box"></i></div>
                <div class="stat-info">
                    <p>Total Orders Placed</p>
                    <h4><?= count($history) ?></h4>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon orange"><i class="fa-solid fa-truck-ramp-box"></i></div>
                <div class="stat-info">
                    <p>Active Shipments</p>
                    <h4><?= $activeCount ?></h4>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green"><i class="fa-solid fa-wallet"></i></div>
                <div class="stat-info">
                    <p>Lifetime Spending</p>
                    <h4>Rs. <?= number_format($totalSpent, 2) ?></h4>
                </div>
            </div>
        </div>
        
        <section class="card">
            <h3>Active Purchase Tracking History</h3>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Order Identifier</th>
                            <th>Date Registered</th>
                            <th>Grand Total</th>
                            <th>Tracking State</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($history)): ?>
                            <tr>
                                <td colspan="4" style="padding: 3rem; text-align: center; color: var(--text-muted);">
                                    <i class="fa-regular fa-folder-open" style="font-size: 2.5rem; margin-bottom: 1rem; display: block; color: #cbd5e1;"></i>
                                    No recent purchase operations found.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach($history as $row): 
                                $statusClass = strtolower(htmlspecialchars($row['status']));
                            ?>
                                <tr>
                                    <td style="font-weight: 700; color: var(--primary-dark);">#DB-<?= $row['id'] ?></td>
                                    <td style="color: var(--text-muted);"><?= date('M d, Y', strtotime($row['created_at'])) ?></td>
                                    <td style="font-weight: 600;">Rs. <?= number_format($row['grand_total'], 2) ?></td>
                                    <td>
                                        <span class="status-badge <?= $statusClass ?>">
                                            <?= htmlspecialchars($row['status']) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</div>

</body>
</html>