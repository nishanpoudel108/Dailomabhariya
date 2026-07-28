<?php
// item-details.php — Save this in your project's main root folder
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/database.php';

$db = Database::getInstance();

// 1. Extract the Item ID safely from the URL query string (?id=X)
$item_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($item_id > 0) {
    // 2. Fetch the product matching this unique ID
    // NOTE: Change 'items' to your actual consumer products table name if it differs
    $stmt = $db->prepare("SELECT * FROM products WHERE id = :id AND status = 'Active'");
    $stmt->execute(['id' => $item_id]);
    $item = $stmt->fetch();

    if (!$item) {
        die("Item not found or has been unlisted.");
    }
} else {
    // Redirect to home if no ID parameters are provided
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($item['name']) ?> | दैलोमा भरिया</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body style="background-color: #f8fafc; font-family: system-ui, sans-serif; color: #1e293b; margin: 0; padding: 0;">

    <!-- Top Navigation Row -->
    <div style="background: #fff; border-bottom: 1px solid #e2e8f0; padding: 1.5rem 2rem;">
        <div style="max-width: 1200px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center;">
            <a href="index.php" style="text-decoration: none; color: #10b981; font-weight: 700; font-size: 1.25rem;">
                <i class="fa-solid fa-leaf"></i> दैलोमा भरिया
            </a>
            <a href="javascript:history.back()" style="text-decoration: none; color: #64748b; font-weight: 600; font-size: 0.95rem;">
                <i class="fa-solid fa-arrow-left"></i> Go Back
            </a>
        </div>
    </div>

    <!-- Product Layout Container -->
    <main style="max-width: 1000px; margin: 4rem auto; padding: 0 1rem;">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 3rem; background: #fff; padding: 2.5rem; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
            
            <!-- Product Information Side -->
            <div style="display: flex; flex-direction: column; justify-content: center;">
                <span style="text-transform: uppercase; color: #10b981; font-weight: 700; font-size: 0.85rem; letter-spacing: 0.05em; margin-bottom: 0.5rem;">
                    <?= htmlspecialchars($item['brand'] ?? 'Local Brand') ?>
                </span>
                
                <h1 style="font-size: 2.2rem; font-weight: 800; color: #1e293b; margin: 0 0 1rem 0; line-height: 1.2;">
                    <?= htmlspecialchars($item['name']) ?>
                </h1>

                <?php if (isset($item['weight'])): ?>
                    <p style="color: #64748b; font-weight: 500; font-size: 1rem; margin-bottom: 1.5rem;">
                        <i class="fa-solid fa-weight-hanging"></i> Net Weight: <?= htmlspecialchars($item['weight']) ?>
                    </p>
                <?php endif; ?>

                <hr style="border: 0; border-top: 1px solid #e2e8f0; margin-bottom: 1.5rem;">

                <!-- Product Pricing Display -->
                <div style="margin-bottom: 2rem;">
                    <p style="color: #64748b; font-size: 0.9rem; margin: 0;">Price inclusive of all taxes</p>
                    <span style="font-size: 2rem; font-weight: 800; color: #1e293b;">Rs. <?= number_format($item['price'], 2) ?></span>
                </div>

                <!-- Add to Cart Interaction Form -->
                <!-- Make sure 'customer/cart.php' or your target cart adding endpoint matches this action -->
                <form action="customer/cart.php?action=add" method="POST" style="display: flex; gap: 1rem; align-items: center;">
                    <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                    
                    <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                        <label for="quantity" style="font-size: 0.85rem; font-weight: 700; color: #64748b;">QTY</label>
                        <input type="number" id="quantity" name="quantity" value="1" min="1" max="20" style="width: 70px; padding: 0.65rem; border: 1px solid #cbd5e1; border-radius: 8px; font-weight: 600; text-align: center;">
                    </div>

                    <button type="submit" style="flex-grow: 1; margin-top: 1.5rem; background: #10b981; color: #fff; border: none; padding: 0.85rem 1.5rem; border-radius: 8px; font-weight: 700; font-size: 1rem; cursor: pointer; display: inline-flex; justify-content: center; align-items: center; gap: 0.5rem; transition: background 0.2s;">
                        <i class="fa-solid fa-cart-plus"></i> Add to Basket
                    </button>
                </form>
            </div>

            <!-- Optional Product Meta / Description Side -->
            <div style="background: #f8fafc; border-radius: 12px; padding: 1.5rem; border: 1px solid #e2e8f0; display: flex; flex-direction: column; justify-content: center;">
                <h3 style="margin-top: 0; color: #1e293b; font-size: 1.1rem; font-weight: 700;"><i class="fa-solid fa-circle-info" style="color: #10b981;"></i> Delivery Details</h3>
                <p style="color: #64748b; font-size: 0.92rem; line-height: 1.6; margin-bottom: 0;">
                    Your item will be handpicked fresh from the marketplace inventory and delivered straight to your doorstep via our local logistics courier system.
                </p>
            </div>

        </div>
    </main>

</body>
</html>