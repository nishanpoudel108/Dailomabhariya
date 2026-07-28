<?php
// Inside your admin order details loop loader component file:
// Fetching $order details array from database matching current routing selection

?>

<!-- Place this within your Admin Order Card Panel Layout Grid -->

<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';
check_role(['Super Admin']);

$db = Database::getInstance();
$latitude = $order['latitude'] ?? null;
$longitude = $order['longitude'] ?? null;
// Process Status Updates Securely via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['order_id'])) {
    $orderId = (int)$_POST['order_id'];
    $newStatus = trim($_POST['status'] ?? '');
    
    $allowedStatuses = ['Pending', 'Accepted', 'Packing', 'Ready', 'Out for Delivery', 'Delivered', 'Cancelled'];
    
    if (in_array($newStatus, $allowedStatuses)) {
        try {
            $stmt = $db->prepare("UPDATE orders SET status = :status WHERE id = :id");
            $stmt->execute(['status' => $newStatus, 'id' => $orderId]);
            
            // Log this administrative action
            log_system_event($_SESSION['user_id'], "Order #DB-{$orderId} status changed to {$newStatus}");
            
            // Update payment status automatically if order is delivered
            if ($newStatus === 'Delivered') {
                $payStmt = $db->prepare("UPDATE payments SET status = 'Paid' WHERE order_id = :id AND payment_method = 'COD'");
                $payStmt->execute(['id' => $orderId]);
            }
            
            $successMsg = "Order #DB-{$orderId} updated to {$newStatus} successfully.";
        } catch (PDOException $e) {
            $errorMsg = "Database mutation error: " . $e->getMessage();
        }
    }
}

// Fetch all orders with customer details
$query = "SELECT o.*, u.name as customer_name, u.mobile as customer_phone, p.payment_method, p.status as payment_status 
          FROM orders o 
          JOIN users u ON o.user_id = u.id 
          JOIN payments p ON p.order_id = o.id 
          ORDER BY o.id DESC";
$orders = $db->query($query)->fetchAll();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders | Admin Center</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>
        /* Design Tokens Matrix System Alignment */
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

        /* Portal Master Grid Splitter Framework */
        .dashboard-container {
            display: grid;
            grid-template-columns: 280px 1fr;
            min-height: 100vh;
        }

        /* Shared Dashboard Sidebar Component */
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

        /* Workspace Main Frame Container */
        .main-content {
            padding: 3rem 4%;
            width: 100%;
        }

        .page-header {
            margin-bottom: 2.5rem;
        }

        .page-header h1 {
            font-size: 1.75rem;
            font-weight: 800;
            letter-spacing: -0.02em;
        }

        .card {
            background: var(--surface);
            border-radius: var(--radius-lg);
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-md);
            padding: 2.25rem;
            margin-bottom: 2rem;
            transition: background-color 0.3s ease, border-color 0.3s ease;
        }

        .card h3 {
            font-size: 1.15rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* Responsive Datatable Scaffolding Framework */
        .table-responsive { 
            width: 100%; 
            overflow-x: auto; 
        }

        .order-table { 
            width: 100%; 
            border-collapse: collapse; 
            text-align: left; 
        }

        .order-table th {
            padding: 1rem 0.85rem;
            font-size: 0.82rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-muted);
            border-bottom: 2px solid var(--bg-main);
            font-weight: 700;
        }

        .order-table td { 
            padding: 1.15rem 0.85rem; 
            border-bottom: 1px solid var(--border-color); 
            font-size: 0.95rem;
            vertical-align: middle;
        }

        /* Metric Pill Badges */
        .badge { 
            display: inline-block;
            padding: 0.3rem 0.75rem; 
            border-radius: 50px; 
            font-size: 0.78rem; 
            font-weight: 700; 
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        /* Administrative Operation Inputs */
        .select-status { 
            padding: 0.55rem 0.75rem; 
            border-radius: var(--radius-md); 
            border: 1px solid var(--border-color); 
            outline: none; 
            background: var(--bg-main); 
            color: var(--text-dark);
            font-weight: 600;
            font-size: 0.88rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .select-status:focus {
            border-color: var(--primary);
            background: var(--surface);
        }

        .btn-update { 
            background: var(--primary); 
            color: white; 
            border: none; 
            padding: 0.55rem 1rem; 
            border-radius: var(--radius-md); 
            font-weight: 700; 
            font-size: 0.88rem;
            cursor: pointer; 
            box-shadow: 0 2px 6px rgba(16, 185, 129, 0.15);
            transition: all 0.2s ease;
        }

        .btn-update:hover { 
            background: var(--primary-hover); 
            transform: translateY(-1px);
        }

        /* Alert Notification Banners */
        .alert { 
            padding: 1rem 1.25rem; 
            border-radius: var(--radius-md); 
            margin-bottom: 1.75rem; 
            font-weight: 600; 
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .alert-success { background: rgba(16, 185, 129, 0.15); color: #059669; border: 1px solid rgba(16, 185, 129, 0.2); }
        .alert-danger { background: rgba(239, 68, 68, 0.15); color: #dc2626; border: 1px solid rgba(239, 68, 68, 0.2); }

        @media (max-width: 992px) {
            .dashboard-container { grid-template-columns: 1fr; }
            .sidebar { display: none; }
        }
    </style>
</head>
<body>

<div class="dashboard-container">
    <!-- Super Admin Sidebar Workspace Nav Components -->
    <aside class="sidebar">
        <h2><i class="fa-solid fa-leaf"></i>  दैलोमा भरिया</h2>
        <span class="admin-badge">System Operator</span>
        <nav>
            <a href="index.php"><i class="fa-solid fa-chart-pie"></i> Dashboard</a>
            <a href="products.php"><i class="fa-solid fa-box"></i> Products</a>
            <a href="orders.php" class="active"><i class="fa-solid fa-truck"></i> Orders</a>
            <a href="../logout.php" style="margin-top: 4rem; color: #ef4444;"><i class="fa-solid fa-right-from-bracket" style="color: #ef4444;"></i> Sign Out</a>
        </nav>
    </aside>

    <!-- Operational Ledger Main Panel Content Workspace -->
    <main class="main-content">
        <header class="page-header">
            <h1>Order Fulfilment Pipeline</h1>
            <p style="color: var(--text-muted); font-size: 0.95rem; margin-top: 0.25rem;">Verify payments, assign logistics progress, and manage live delivery steps.</p>
        </header>

        <!-- Dynamic Feedback Alert Pipeline Components -->
        <?php if (isset($successMsg)): ?>
            <div class="alert alert-success">
                <i class="fa-solid fa-circle-check"></i> <?= sanitize_output($successMsg) ?>
            </div>
        <?php endif; ?>
        <?php if (isset($errorMsg)): ?>
            <div class="alert alert-danger">
                <i class="fa-solid fa-triangle-exclamation"></i> <?= sanitize_output($errorMsg) ?>
            </div>
        <?php endif; ?>

        <!-- Active Fulfilment Pipeline Log Sheet Card Container -->
        <section class="card">
            <h3><i class="fa-solid fa-list-check" style="color: var(--primary);"></i> Incoming Orders Ledger</h3>
            
            <div class="table-responsive">
                <table class="order-table">
                    <thead>
                        <tr>
                            <th>ID Code</th>
                            <th>Customer</th>
                            <th>Contact Info</th>
                            <th>Grand Total</th>
                            <th>Payment State</th>
                            <th>Tracking Status</th>
                            <th> LOCATION </th>
                            <th>Change Operations</th>
                     
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($orders)): ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 3rem 1rem; color: var(--text-muted); font-weight: 500;">
                                    <i class="fa-solid fa-cubes" style="font-size: 2rem; display: block; margin-bottom: 0.5rem; color: var(--text-muted); opacity: 0.6;"></i>
                                    No delivery shipments recorded yet.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($orders as $row): ?>
                                <tr>
                                    <td style="font-family: monospace; font-weight: 700; color: var(--text-muted);">#DB-<?= $row['id'] ?></td>
                                    <td style="font-weight: 600; color: var(--text-dark);"><?= sanitize_output($row['customer_name']) ?></td>
                                    <td style="font-weight: 500; color: var(--text-muted);"><?= sanitize_output($row['customer_phone']) ?></td>
                                    <td style="font-weight: 700; color: var(--text-dark);"><?= format_nepali_currency($row['grand_total']) ?></td>
                                    <td>
                                        <?php 
                                            $isPaid = $row['payment_status'] === 'Paid';
                                            $payBg = $isPaid ? 'rgba(16, 185, 129, 0.15)' : 'rgba(245, 158, 11, 0.15)';
                                            $payColor = $isPaid ? '#059669' : '#d97706';
                                        ?>
                                        <span class="badge" style="background: <?= $payBg ?>; color: <?= $payColor ?>;">
                                            <?= htmlspecialchars($row['payment_method']) ?> (<?= htmlspecialchars($row['payment_status']) ?>)
                                        </span>
                                    </td>
                                    
                                    <td>
                                        <?php 
                                            $trackingColor = get_tracking_status_color($row['status']);
                                        ?>
                                        <span class="badge" style="background: <?= $trackingColor ?>18; color: <?= $trackingColor ?>; border: 1px solid <?= $trackingColor ?>30;">
                                            <i class="fa-solid fa-circle-dot" style="font-size: 0.65rem; margin-right: 4px; vertical-align: middle;"></i><?= htmlspecialchars($row['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($latitude && $longitude): ?>
    <div class="admin-card" style="background: var(--surface); border: 1px solid var(--border-color); padding: 1.5rem; border-radius: 12px; margin-top: 1.5rem;">
        <h3 style="font-size: 1.1rem; font-weight: 700; color: var(--text-dark); margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
            <i class="fa-solid fa-truck-ramp-box" style="color: #3b82f6;"></i> Exact Doorstep Routing Coordinates
        </h3>
        
        <!-- Interactive View Map Canvas Box -->
        <div id="admin-view-map" style="width: 100%; height: 280px; border-radius: 8px; margin-bottom: 1rem; border: 1px solid var(--border-color);"></div>
        
        <!-- Operational Direct Links (Google Maps Routing API Integration Shortcuts) -->
        <div style="display: flex; gap: 1rem;">
            <a href="https://www.google.com/maps/search/?api=1&query=<?= $latitude ?>,<?= $longitude ?>" 
               target="_blank" 
               class="btn-primary" 
               style="background: #10b981; color: #fff; text-decoration: none; padding: 0.6rem 1rem; border-radius: 6px; font-weight: 600; font-size: 0.85rem; display: inline-flex; align-items: center; gap: 0.4rem;">
                <i class="fa-solid fa-location-arrow"></i> Open Google Maps Navigation
            </a>
        </div>
    </div>
                                     <?php else: ?>
    <div style="padding: 1.5rem; background: rgba(0,0,0,0.02); border-radius: 8px; border: 1px dashed var(--border-color); text-align: center; color: var(--text-muted);">
         <i class="fa-solid fa-map-location" style="font-size: 1.5rem; margin-bottom: 0.5rem; opacity: 0.5;"></i>
         <p>No explicit map pin coordinate parameters provided during this order process.</p>
    </div>
<?php endif; ?>
                                    </td>
                                    <td>
                                        
                                              <form method="POST" action="orders.php" style="display: flex; gap: 0.5rem; align-items: center;">
                                            <input type="hidden" name="order_id" value="<?= $row['id'] ?>">
                                            <input type="hidden" name="action" value="update_status">
                                            
                                            <select name="status" class="select-status">
                                                <?php
                                                $statuses = ['Pending', 'Accepted', 'Packing', 'Ready', 'Out for Delivery', 'Delivered', 'Cancelled'];
                                                foreach ($statuses as $st):
                                                ?>
                                                    <option value="<?= $st ?>" <?= $row['status'] === $st ? 'selected' : '' ?>><?= $st ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="submit" class="btn-update">Update</button>
                                        </form>
                                        
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
<script>
    document.addEventListener("DOMContentLoaded", () => {
        const orderLat = <?= json_encode((float)$latitude) ?>;
        const orderLng = <?= json_encode((float)$longitude) ?>;

        // Initialize Admin Map view locked onto coordinates
        const adminMap = L.map('admin-view-map').setView([orderLat, orderLng], 16);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap'
        }).addTo(adminMap);

        // Render fixed destination point pin marker (Draggable false securely isolates data input state changes)
        L.marker([orderLat, orderLng])
            .addTo(adminMap)
            .bindPopup("<b>Target Delivery Doorstep Point</b><br>Lat: " + orderLat + "<br>Lng: " + orderLng)
            .openPopup();
    });
    </script>
</body>
</html>