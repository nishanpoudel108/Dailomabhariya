<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/database.php';
check_role(['Customer']);

$db = Database::getInstance();
$config = json_decode(file_get_contents(__DIR__ . '/../config/config.json'), true);

// Fetch user's registered delivery location defaults directly from database profile
$userId = $_SESSION['user_id'] ?? null;
$userLat = 27.700769; // Hardcoded fallback defaults
$userLng = 85.300140;

if ($userId) {
    $userStmt = $db->prepare("SELECT latitude, longitude FROM users WHERE id = :id LIMIT 1");
    $userStmt->execute(['id' => $userId]);
    $userData = $userStmt->fetch(PDO::FETCH_ASSOC);
    if ($userData && !empty($userData['latitude']) && !empty($userData['longitude'])) {
        $userLat = (float)$userData['latitude'];
        $userLng = (float)$userData['longitude'];
    }
}

$cartItems = [];
$subtotal = 0;

if (!empty($_SESSION['cart'])) {
    $placeholders = implode(',', array_fill(0, count($_SESSION['cart']), '?'));
    $stmt = $db->prepare("SELECT * FROM products WHERE id IN ($placeholders) AND status = 'Active'");
    $stmt->execute(array_keys($_SESSION['cart']));
    $products = $stmt->fetchAll();

    foreach ($products as $prod) {
        $qty = $_SESSION['cart'][$prod['id']];
        $itemTotal = $prod['price'] * $qty;
        $subtotal += $itemTotal;
        $cartItems[] = [
            'id' => $prod['id'],
            'name' => $prod['name'],
            'price' => $prod['price'],
            'qty' => $qty,
            'total' => $itemTotal
        ];
    }
}

$tax = $subtotal * ($config['app']['tax_rate'] ?? 0.13);
$delivery = $subtotal > 0 ? ($config['app']['delivery_charge'] ?? 100) : 0;
$grandTotal = $subtotal + $tax + $delivery;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Shopping Cart | दैलोमा भरिया</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>
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

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: system-ui, -apple-system, sans-serif; background-color: var(--bg-main); color: var(--text-dark); line-height: 1.5; }

        .dashboard-container { display: grid; grid-template-columns: 280px 1fr; min-height: 100vh; }
        .sidebar { background: var(--surface); border-right: 1px solid var(--border-color); padding: 2.25rem 1.5rem; display: flex; flex-direction: column; }
        .sidebar h3 { font-size: 1.35rem; font-weight: 800; color: var(--primary); display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.25rem; }
        .user-welcome { font-size: 0.85rem; color: var(--text-muted); margin-bottom: 2.5rem; font-weight: 500; }
        .sidebar nav { display: flex; flex-direction: column; gap: 0.5rem; }
        .sidebar nav a { display: flex; align-items: center; gap: 0.75rem; padding: 0.85rem 1rem; color: var(--text-dark); text-decoration: none; font-weight: 600; font-size: 0.95rem; border-radius: var(--radius-md); transition: all 0.2s ease; }
        .sidebar nav a i { width: 20px; font-size: 1.1rem; color: var(--text-muted); }
        .sidebar nav a:hover { background: var(--bg-main); color: var(--primary); }
        .sidebar nav a.active { background: var(--primary-light); color: var(--primary-dark); }

        .main-content { padding: 3rem 4%; width: 100%; }
        .page-title { font-size: 1.6rem; font-weight: 800; color: var(--text-dark); margin-bottom: 2rem; }
        .checkout-grid { display: grid; grid-template-columns: 1.6fr 1fr; gap: 2rem; align-items: start; }
        .card { background: var(--surface); border-radius: var(--radius-lg); border: 1px solid var(--border-color); box-shadow: var(--shadow-md); padding: 2rem; }
        .card h3 { font-size: 1.15rem; font-weight: 700; color: var(--text-dark); margin-bottom: 1.25rem; display: flex; align-items: center; gap: 0.5rem; }

        .cart-table { width: 100%; border-collapse: collapse; text-align: left; }
        .cart-table th { padding: 0.75rem 0.5rem; font-size: 0.82rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); border-bottom: 2px solid var(--bg-main); font-weight: 700; }
        .cart-table td { padding: 1.25rem 0.5rem; border-bottom: 1px solid var(--border-color); font-size: 0.95rem; }
        .product-name { font-weight: 600; color: var(--text-dark); }

        /* Stepper CSS Structure */
        .qty-stepper { display: inline-flex; align-items: center; border: 1px solid var(--border-color); border-radius: 6px; background: var(--bg-main); overflow: hidden; }
        .qty-btn { background: none; border: none; padding: 0.4rem 0.75rem; font-size: 0.9rem; font-weight: bold; color: var(--text-dark); cursor: pointer; transition: background 0.2s; }
        .qty-btn:hover { background: rgba(0,0,0,0.05); }
        .qty-val { padding: 0 0.5rem; min-width: 25px; text-align: center; font-weight: 700; font-size: 0.95rem; }

        .ledger-list { display: flex; flex-direction: column; gap: 0.85rem; }
        .ledger-row { display: flex; justify-content: space-between; font-size: 0.95rem; color: var(--text-muted); }
        .ledger-row strong { color: var(--text-dark); }
        .grand-total-row { display: flex; justify-content: space-between; font-size: 1.25rem; font-weight: 800; color: var(--primary-dark); padding-top: 0.5rem; }

        .form-control-wrapper { margin-top: 1.5rem; }
        .form-label { display: block; margin-bottom: 0.5rem; font-size: 0.85rem; font-weight: 700; text-transform: uppercase; color: var(--text-muted); }
        .custom-select { width: 100%; padding: 0.75rem 1rem; background: var(--bg-main); border: 1px solid var(--border-color); border-radius: var(--radius-md); color: var(--text-dark); font-size: 0.95rem; outline: none; cursor: pointer; }

        .btn-checkout { width: 100%; margin-top: 1.75rem; padding: 1rem; background: var(--primary); border: none; color: white; border-radius: var(--radius-md); font-size: 1rem; font-weight: 700; cursor: pointer; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2); transition: all 0.2s ease; display: flex; align-items: center; justify-content: center; gap: 0.5rem; }
        .btn-checkout:hover { background: var(--primary-hover); transform: translateY(-1px); box-shadow: 0 6px 16px rgba(16, 185, 129, 0.3); }

        .floating-confirmation { position: fixed; top: 24px; right: 24px; background: #ffffff; border-left: 5px solid var(--primary); padding: 1.25rem; border-radius: var(--radius-md); box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1); z-index: 2000; display: flex; align-items: flex-start; gap: 1rem; min-width: 320px; transform: translateY(-20px); opacity: 0; pointer-events: none; transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
        .floating-confirmation.show { transform: translateY(0); opacity: 1; pointer-events: auto; }
        .floating-confirmation i.success-icon { color: var(--primary); font-size: 1.5rem; margin-top: 2px; }
        .floating-info h4 { font-size: 1rem; font-weight: 700; color: var(--text-dark); }
        .floating-info p { font-size: 0.85rem; color: var(--text-muted); margin-top: 2px; }
        .floating-timestamp { font-size: 0.75rem; color: var(--primary-dark); font-weight: 700; display: block; margin-top: 6px; background: var(--primary-light); padding: 2px 8px; border-radius: 4px; inline-size: max-content; }

        @media (max-width: 992px) {
            .dashboard-container { grid-template-columns: 1fr; }
            .sidebar { display: none; }
            .checkout-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<div id="floatingConfBox" class="floating-confirmation">
    <i class="fa-solid fa-circle-check success-icon"></i>
    <div class="floating-info">
        <h4>Order Placed Successfully!</h4>
        <p id="floatingMsg">Your secure transaction entry has been initialized.</p>
        <span id="confTimestamp" class="floating-timestamp"></span>
    </div>
</div>

<div class="dashboard-container">
    <aside class="sidebar">
        <h3><i class="fa-solid fa-leaf"></i> दैलोमा भरिया</h3>
        <p class="user-welcome">Welcome, <?= htmlspecialchars($_SESSION['name'] ?? 'Customer') ?></p>
        <nav>
            <a href="index.php"><i class="fa-solid fa-house"></i> Overview</a>
            <a href="../index.php"><i class="fa-solid fa-basket-shopping"></i> Shop Marketplace</a>
            <a href="cart.php" class="active"><i class="fa-solid fa-cart-shopping"></i> My Cart</a>
            <a href="../logout.php" style="margin-top: 2rem; color: #ef4444;"><i class="fa-solid fa-right-from-bracket" style="color: #ef4444;"></i> Sign Out</a>
        </nav>
    </aside>

    <main class="main-content">
        <h2 class="page-title">Shopping Cart Details</h2>

        <div class="checkout-grid">
            <section class="card">
                <h3><i class="fa-solid fa-basket-shopping" style="color: var(--primary);"></i> Selected Items</h3>
                <?php if (empty($cartItems)): ?>
                    <div style="padding: 3rem 0; text-align: center;">
                        <i class="fa-solid fa-cart-flatbed" style="font-size: 2.5rem; color: #cbd5e1; margin-bottom: 1rem;"></i>
                        <p style="color: var(--text-muted); font-weight: 500;">Your shopping cart is currently empty.</p>
                    </div>
                <?php else: ?>
                    <table class="cart-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Price</th>
                                <th style="text-align: center;">Qty</th>
                                <th style="text-align: right;">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cartItems as $item): ?>
                                <tr id="product-row-<?= $item['id'] ?>">
                                    <td class="product-name"><?= htmlspecialchars($item['name']) ?></td>
                                    <td>Rs. <?= number_format($item['price'], 2) ?></td>
                                    <td style="text-align: center;">
                                        <!-- Functional Counter Engine Buttons Wrapper -->
                                        <div class="qty-stepper">
                                            <button class="qty-btn" onclick="modifyItemQty(<?= $item['id'] ?>, -1)">-</button>
                                            <span class="qty-val" id="qty-display-<?= $item['id'] ?>"><?= $item['qty'] ?></span>
                                            <button class="qty-btn" onclick="modifyItemQty(<?= $item['id'] ?>, 1)">+</button>
                                        </div>
                                    </td>
                                    <td style="text-align: right; font-weight: 700; color: var(--text-dark);" id="total-display-<?= $item['id'] ?>">
                                        Rs. <?= number_format($item['total'], 2) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </section>

            <section class="card">
                <h3><i class="fa-solid fa-receipt" style="color: var(--primary);"></i> Order Summary</h3>
                <div class="ledger-list">
                    <div class="ledger-row"><span>Subtotal:</span><strong id="summary-subtotal">Rs. <?= number_format($subtotal, 2) ?></strong></div>
                    <div class="ledger-row"><span>VAT (13%):</span><strong id="summary-tax">Rs. <?= number_format($tax, 2) ?></strong></div>
                    <div class="ledger-row"><span>Delivery Charge:</span><strong id="summary-delivery">Rs. <?= number_format($delivery, 2) ?></strong></div>
                    <hr style="border: 0; border-top: 1px solid var(--border-color); margin: 0.5rem 0;">
                    <div class="grand-total-row"><span>Grand Total:</span><span id="summary-grandtotal">Rs. <?= number_format($grandTotal, 2) ?></span></div>
                </div>

                <?php if (!empty($cartItems)): ?>
                    <div class="form-control-wrapper">
                        <label for="payment_method" class="form-label">Payment Method</label>
                        <select id="payment_method" class="custom-select">
                            <option value="COD">Cash on Delivery (COD)</option>
                        </select>
                    </div>
                    <div class="form-group" style="margin-top: 1.5rem;">
                        <label style="font-weight: 600; display: block; margin-bottom: 0.5rem; color: var(--text-dark);">
                            <i class="fa-solid fa-map-location-dot" style="color: var(--primary);"></i> Pin Your Delivery Location
                        </label>
                        <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 0.75rem;">
                            Your default registered profile location has been auto-selected. Drag the pin if adjustments are required.
                        </p>
                        
                        <div id="delivery-map" style="width: 100%; height: 320px; border-radius: 8px; border: 1px solid var(--border-color); z-index: 1;"></div>
                        
                        <!-- Seed map with coordinates loaded dynamically from database profile variables -->
                        <input type="hidden" name="latitude" id="delivery_lat" value="<?= $userLat ?>">
                        <input type="hidden" name="longitude" id="delivery_lng" value="<?= $userLng ?>">
                    </div>
                    <button id="checkoutBtn" class="btn-checkout">
                        <i class="fa-solid fa-shield-check"></i> Place Order Securely
                    </button>
                <?php endif; ?>
            </section>
        </div>
    </main>
</div>

<script src="../assets/js/app.js"></script>
<script>
// Capture dynamic profiles parameters directly out of our compiled DOM engine elements
const defaultLat =  parseFloat(document.getElementById('delivery_lat').value);
const defaultLng =  parseFloat(document.getElementById('delivery_lng').value);

document.addEventListener("DOMContentLoaded", () => {
    // Dynamic Profile Mapping Core Bootloader
    const map = L.map('delivery-map').setView([defaultLat, defaultLng], 15);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);

    const marker = L.marker([defaultLat, defaultLng], { draggable: true }).addTo(map);

    function updateCoordinates(lat, lng) {
        document.getElementById('delivery_lat').value = parseFloat(lat).toFixed(6);
        document.getElementById('delivery_lng').value = parseFloat(lng).toFixed(6);
    }

    marker.on('dragend', function (e) {
        const position = marker.getLatLng();
        updateCoordinates(position.lat, position.lng);
    });

    map.on('click', function (e) {
        marker.setLatLng(e.latlng);
        updateCoordinates(e.latlng.lat, e.latlng.lng);
    });
});

// Async Client Quantity Modifier Engine (AJAX Router)
async function modifyItemQty(productId, changeDelta) {
    const qtyDisplay = document.getElementById(`qty-display-${productId}`);
    let currentQty = parseInt(qtyDisplay.innerText);
    let targetQty = currentQty + changeDelta;

    if (targetQty < 0) return; // Stop reduction processing beneath clear absolute bound rule

    try {
        // Targets your process action handler matching the cart system routing
        const response = await fetch('../api/cart.php?action=update_qty', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ product_id: productId, qty: targetQty })
        });
        const res = await response.json();

        if (res.success) {
            if (targetQty === 0) {
                // If quantity reaches zero, wipe row block item out of visibility
                document.getElementById(`product-row-${productId}`).remove();
                if (res.cart_empty) {
                    window.location.reload(); // Refresh canvas context layout completely if completely empty
                    return;
                }
            } else {
                qtyDisplay.innerText = targetQty;
                document.getElementById(`total-display-${productId}`).innerText = "Rs. " + parseFloat(res.item_total).toFixed(2);
            }
            
            // Re-render financial layout boxes instantly
            document.getElementById('summary-subtotal').innerText = "Rs. " + parseFloat(res.subtotal).toFixed(2);
            document.getElementById('summary-tax').innerText = "Rs. " + parseFloat(res.tax).toFixed(2);
            document.getElementById('summary-delivery').innerText = "Rs. " + parseFloat(res.delivery).toFixed(2);
            document.getElementById('summary-grandtotal').innerText = "Rs. " + parseFloat(res.grand_total).toFixed(2);
        } else {
            alert(res.message || "Failed to update item count metrics.");
        }
    } catch (err) {
        console.error("AJAX Error updating item matrix state: ", err);
    }
}

if (document.getElementById('checkoutBtn')) {
    document.getElementById('checkoutBtn').addEventListener('click', async () => {
        const method = document.getElementById('payment_method').value;
        const lat = document.getElementById('delivery_lat').value;
        const lng = document.getElementById('delivery_lng').value;
        const btn = document.getElementById('checkoutBtn');
        
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Processing Checkout...';
        
        try {
            const response = await fetch('../api/orders.php?action=checkout', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    payment_method: method,
                    latitude: lat,
                    longitude: lng
                })
            });
            const res = await response.json();
            if (res.success) {
                const now = new Date();
                const timestampStr = now.toLocaleDateString('en-US', { 
                    month: 'short', day: 'numeric', year: 'numeric' 
                }) + ' at ' + now.toLocaleTimeString('en-US', { 
                    hour: '2-digit', minute: '2-digit', second: '2-digit' 
                });

                document.getElementById('floatingMsg').innerText = res.message;
                document.getElementById('confTimestamp').innerHTML = '<i class="fa-regular fa-clock"></i> Confirmed: ' + timestampStr;
                
                const floatBox = document.getElementById('floatingConfBox');
                floatBox.classList.add('show');

                setTimeout(() => {
                    window.location.href = 'track.php?order_id=' + res.order_id;
                }, 3000);

            } else {
                alert(res.message);
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-shield-check"></i> Place Order Securely';
            }
        } catch (err) {
            alert("Error sending checkout request.");
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-shield-check"></i> Place Order Securely';
        }
    });
}
</script>
</body>
</html>