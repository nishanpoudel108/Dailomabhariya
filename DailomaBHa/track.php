<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/database.php';
check_role(['Customer', 'Rider', 'Admin']); // Allow tracking system visualization permissions

$orderId = (int)($_GET['order_id'] ?? 0);
if ($orderId <= 0) {
    die("Invalid transactional entry routing identifier.");
}

$db = Database::getInstance();

// Pull explicit coordinates saved for this order
$stmt = $db->prepare("SELECT o.*, u.name as customer_name FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = :order_id LIMIT 1");
$stmt->execute(['order_id' => $orderId]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    die("Target tracking ledger metrics mismatch error.");
}

// Map database metrics fallback context safety rules
$destLat = !empty($order['latitude']) ? (float)$order['latitude'] : 27.700769;
$destLng = !empty($order['longitude']) ? (float)$order['longitude'] : 85.300140;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Order #<?= $orderId ?> | दैलोमा भरिया</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Leaflet Engine CDN -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <style>
        :root { --primary: #10b981; --text-dark: #1e293b; --surface: #ffffff; }
        body { font-family: system-ui, sans-serif; background: #f8fafc; padding: 2rem; }
        .track-wrap { max-width: 800px; margin: 0 auto; background: var(--surface); padding: 2rem; border-radius: 16px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .status-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 1rem; }
        .status-badge { background: #fef3c7; color: #d97706; padding: 0.35rem 1rem; border-radius: 20px; font-weight: 700; font-size: 0.85rem; }
        #tracking-map { width: 100%; height: 400px; border-radius: 12px; border: 1px solid #e2e8f0; margin-top: 1.5rem; }
    </style>
</head>
<body>

<div class="track-wrap">
    <div class="status-header">
        <div>
            <h2>Order ID: #<?= $orderId ?></h2>
            <p style="color:#64748b; font-size:0.9rem;">Customer: <?= htmlspecialchars($order['customer_name']) ?></p>
        </div>
        <span class="status-badge"><?= htmlspecialchars($order['status']) ?></span>
    </div>

    <div class="order-details">
        <h3><i class="fa-solid fa-map-pin" style="color:var(--primary)"></i> Delivery Point Details</h3>
        <p style="margin-top:0.5rem; color:#475569; font-size:0.95rem;">
            Riders are utilizing the precise satellite coordinate system mapping data loaded below to locate your exact drop zone location structure.
        </p>
        
        <!-- Interactive Tracking Map Display Viewport canvas -->
        <div id="tracking-map"></div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
    // Capture dynamic system variables safely into execution threads
    const destLat = <?= $destLat ?>;
    const destLng = <?= $destLng ?>;

    // Load static destination layout map frame
    const trackMap = L.map('tracking-map').setView([destLat, destLng], 16);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '© OpenStreetMap contributors'
    }).addTo(trackMap);

    // Lock fixed marker pinning customer target entry position (non-draggable to avoid rider confusion)
    const landingMarker = L.marker([destLat, destLng]).addTo(trackMap);
    landingMarker.bindPopup("<b>Deliver Order Here</b><br>Target doorstep drop-off coordinate lock.").openPopup();
});
</script>
</body>
</html>