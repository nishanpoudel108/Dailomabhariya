<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/database.php';
check_role(['Super Admin']);

$db = Database::getInstance();
$categories = $db->query("SELECT * FROM categories WHERE status = 'Active'")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Product Metrics | System Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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

        /* Refined Administrative Form Layout Engines */
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.25rem;
            margin-bottom: 1.25rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
        }

        .form-label {
            font-size: 0.85rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            color: var(--text-muted);
        }

        .input-box {
            padding: 0.75rem 1rem;
            background: var(--bg-main);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            color: var(--text-dark);
            font-size: 0.95rem;
            font-weight: 500;
            outline: none;
            transition: all 0.2s ease;
            width: 100%;
        }

        .input-box:focus {
            border-color: var(--primary);
            background: var(--surface);
            box-shadow: 0 0 0 3px var(--primary-light);
        }

        .btn-commit {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: var(--primary);
            border: none;
            padding: 0.85rem 1.75rem;
            color: white;
            border-radius: var(--radius-md);
            font-size: 0.95rem;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);
            transition: all 0.2s ease;
            margin-top: 0.5rem;
        }

        .btn-commit:hover {
            background: var(--primary-hover);
            transform: translateY(-1px);
        }

        /* Structured Admin Master Datatable */
        .admin-table-wrapper {
            overflow-x: auto;
        }

        .admin-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        .admin-table th {
            padding: 1rem 0.85rem;
            font-size: 0.82rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-muted);
            border-bottom: 2px solid var(--bg-main);
            font-weight: 700;
        }

        .admin-table td {
            padding: 1.15rem 0.85rem;
            border-bottom: 1px solid var(--border-color);
            font-size: 0.95rem;
        }

        @media (max-width: 992px) {
            .dashboard-container { grid-template-columns: 1fr; }
            .sidebar { display: none; }
        }
    </style>
</head>
<body>

<div class="dashboard-container">
    <!-- Super Admin View Side Navigation Layer -->
    <aside class="sidebar">
        <h2><i class="fa-solid fa-leaf"></i> दैलोमा भरिया</h2>
        <span class="admin-badge">System Operator</span>
        <nav>
            <a href="index.php"><i class="fa-solid fa-chart-pie"></i> Dashboard</a>
            <a href="products.php" class="active"><i class="fa-solid fa-box"></i> Products</a>
            <a href="orders.php"><i class="fa-solid fa-truck"></i> Orders</a>
            <a href="../logout.php" style="margin-top: 4rem; color: #ef4444;"><i class="fa-solid fa-right-from-bracket" style="color: #ef4444;"></i> Sign Out</a>
        </nav>
    </aside>

    <!-- Workspace Main Module Panel -->
    <main class="main-content">
        <header class="page-header">
            <h1>Product Catalog Center</h1>
            <p style="color: var(--text-muted); font-size: 0.95rem; margin-top: 0.25rem;">Create, configure, and maintain operational product SKUs in the local warehouse matrix.</p>
        </header>

        <!-- Product Schema Injection Entry form card -->
        <section class="card">
            <h3><i class="fa-solid fa-square-plus" style="color: var(--primary);"></i> Inject New Product Item Structure</h3>
            <form id="createProductForm" style="margin-top: 0.5rem;">
                <div class="form-row">
                    <div class="form-group">
                        <label for="p_name" class="form-label">Product Name</label>
                        <input type="text" id="p_name" class="input-box" placeholder="e.g. Premium Basmati Rice" required>
                    </div>
                    <div class="form-group">
                        <label for="p_category" class="form-label">Category Mapping</label>
                        <select id="p_category" class="input-box" style="cursor: pointer;" required>
                            <option value="">Select Target Metric Category</option>
                            <?php foreach($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="p_sku" class="form-label">Unique SKU Code / Barcode</label>
                        <input type="text" id="p_sku" class="input-box" placeholder="e.g. GRO-RIC-BRN-01" required>
                    </div>
                    <div class="form-group">
                        <label for="p_brand" class="form-label">Brand Indicator</label>
                        <input type="text" id="p_brand" class="input-box" placeholder="e.g. DDC, Patanjali">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="p_price" class="form-label">Base Price (NPR)</label>
                        <input type="number" step="0.01" id="p_price" class="input-box" placeholder="0.00" required>
                    </div>
                    <div class="form-group">
                        <label for="p_stock" class="form-label">Available Stock Volume</label>
                        <input type="number" id="p_stock" class="input-box" placeholder="0" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="p_weight" class="form-label">Weight / Volume metrics</label>
                        <input type="text" id="p_weight" class="input-box" placeholder="e.g. 1 kg, 500 ml, 1 ltr">
                    </div>
                </div>
                <button type="submit" class="btn-commit">
                    <i class="fa-solid fa-layer-group"></i> Commit Item to Warehouse
                </button>
            </form>
        </section>

        <!-- Live Inventory Matrix Status Sheet Grid Block -->
        <section class="card">
            <h3><i class="fa-solid fa-boxes-stacked" style="color: var(--primary);"></i> Live Product Inventory Data Metrics</h3>
            <div class="admin-table-wrapper">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>SKU Code</th>
                            <th>Item Name</th>
                            <th>Category</th>
                            <th>Unit Price</th>
                            <th>Stock Count</th>
                            <th>Status Badge</th>
                        </tr>
                    </thead>
                    <tbody id="inventoryTableBody">
                        <!-- Loaded dynamically via async client configuration pipeline -->
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</div>

<script src="../assets/js/app.js"></script>
<script>
document.addEventListener("DOMContentLoaded", () => {
    loadInventoryTable();

    document.getElementById('createProductForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = e.target.querySelector('.btn-commit');
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Registering SKU...';

        const payload = {
            name: document.getElementById('p_name').value,
            category_id: document.getElementById('p_category').value,
            sku: document.getElementById('p_sku').value,
            brand: document.getElementById('p_brand').value,
            price: document.getElementById('p_price').value,
            stock: document.getElementById('p_stock').value,
            weight: document.getElementById('p_weight').value
        };

        try {
            const response = await fetch('../api/products.php?action=create', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const res = await response.json();
            if(res.success) {
                alert(res.message);
                document.getElementById('createProductForm').reset();
                loadInventoryTable();
            } else {
                alert(res.message);
            }
        } catch(err) {
            alert("Network processing engine block communication error.");
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-layer-group"></i> Commit Item to Warehouse';
        }
    });
});

async function loadInventoryTable() {
    try {
        const response = await fetch('../api/products.php?action=read');
        const data = await response.json();
        const tbody = document.getElementById('inventoryTableBody');
        
        tbody.innerHTML = data.map(item => {
            const isLowStock = parseInt(item.stock) <= 5;
            const stockColor = isLowStock ? '#ef4444' : 'var(--text-dark)';
            const stockWeight = isLowStock ? '700' : '500';
            const warningIcon = isLowStock ? '<i class="fa-solid fa-circle-exclamation" style="color:#ef4444; margin-right:4px;"></i>' : '';

            const badgeBg = item.status === 'Active' ? 'rgba(16, 185, 129, 0.15)' : 'rgba(239, 68, 68, 0.15)';
            const badgeColor = item.status === 'Active' ? '#059669' : '#dc2626';

            return `
                <tr>
                    <td style="font-family: monospace; font-weight: 700; color: var(--text-muted);">${item.sku}</td>
                    <td style="font-weight: 600; color: var(--text-dark);">${item.name}</td>
                    <td><span style="font-weight: 500; color: var(--text-muted);">${item.category_name}</span></td>
                    <td style="font-weight: 600;">Rs. ${parseFloat(item.price).toFixed(2)}</td>
                    <td style="color: ${stockColor}; font-weight: ${stockWeight};">
                        ${warningIcon}${item.stock} units
                    </td>
                    <td>
                        <span style="display: inline-block; padding: 0.25rem 0.75rem; font-size: 0.75rem; border-radius: 50px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; background: ${badgeBg}; color: ${badgeColor};">
                            ${item.status}
                        </span>
                    </td>
                </tr>
            `;
        }).join('');
    } catch(err) {
        console.error("Failure pulling global ledger records:", err);
    }
}
</script>
</body>
</html>