<?php
// category.php — Place this file in your root folder
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/database.php';

$db = Database::getInstance();

// 1. Extract the category identifier from the URL safely (?id=1)
$category_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($category_id > 0) {
    // 2. Look up the category info to display as a page heading
    $catStmt = $db->prepare("SELECT name FROM categories WHERE id = :id");
    $catStmt->execute(['id' => $category_id]);
    $category = $catStmt->fetch();

    // Prevent issues if someone modifies the URL to an ID that doesn't exist
    if (!$category) {
        die("Category not found.");
    }

    // 3. Fetch all active items linked to this specific category ID
    // Note: Adjust the column name 'category_id' if your items table uses a different foreign key (e.g., cat_id)
    $prodStmt = $db->prepare("SELECT * FROM products WHERE category_id = :cat_id AND status = 'Active' ORDER BY id DESC");
    $prodStmt->execute(['cat_id' => $category_id]);
    $items = $prodStmt->fetchAll();
} else {
    // Fallback redirect to the main consumer marketplace if no ID parameter is provided
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buy <?= htmlspecialchars($category['name']) ?> | दैलोमा भरिया</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body style="background-color: #f8fafc; font-family: system-ui, sans-serif; color: #1e293b; margin: 0; padding: 0;">

    <!-- Marketplace Header Area -->
    <div style="background: #fff; border-bottom: 1px solid #e2e8f0; padding: 1.5rem 2rem;">
        <div style="max-width: 1200px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center;">
            <a href="index.php" style="text-decoration: none; color: #10b981; font-weight: 700; font-size: 1.25rem;">
                <i class="fa-solid fa-leaf"></i> दैलोमा भरिया
            </a>
            <a href="index.php" style="text-decoration: none; color: #64748b; font-weight: 600; font-size: 0.95rem;">
                <i class="fa-solid fa-arrow-left"></i> Back to Shop
            </a>
        </div>
    </div>

    <!-- Product Grid Workspace Area -->
    <main style="max-width: 1200px; margin: 3rem auto; padding: 0 1rem;">
        <h1 style="font-size: 1.75rem; font-weight: 800; margin-bottom: 2rem; letter-spacing: -0.02em;">
            Browsing: <span style="color: #10b981;"><?= htmlspecialchars($category['name']) ?></span>
        </h1>

        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 1.5rem;">
            <?php if (empty($items)): ?>
                <div style="grid-column: 1/-1; background: #fff; padding: 4rem 2rem; text-align: center; border-radius: 16px; border: 1px solid #e2e8f0;">
                    <i class="fa-solid fa-basket-shopping" style="font-size: 3rem; color: #cbd5e1; margin-bottom: 1rem;"></i>
                    <p style="color: #64748b; font-weight: 500;">No items are available in this category at the moment.</p>
                </div>
            <?php else: ?>
                <?php foreach ($items as $item): ?>
                    <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1.5rem; display: flex; flex-direction: column; justify-content: space-between; box-shadow: 0 1px 3px rgba(0,0,0,0.05); transition: transform 0.2s ease;">
                        <div>
                            <h3 style="font-size: 1.15rem; font-weight: 700; margin-bottom: 0.5rem; color: #1e293b;"><?= htmlspecialchars($item['name']) ?></h3>
                            <p style="color: #64748b; font-size: 0.88rem; margin-bottom: 1.5rem;">
                                <?= htmlspecialchars($item['brand'] ?? 'Local') ?> <?= isset($item['weight']) ? '• ' . htmlspecialchars($item['weight']) : '' ?>
                            </p>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: auto;">
                            <span style="font-weight: 800; font-size: 1.2rem; color: #1e293b;">Rs. <?= number_format($item['price'], 2) ?></span>
                            <!-- Replace item-details.php with your actual individual product view script -->
                            <a href="item-details.php?id=<?= $item['id'] ?>" style="background: #10b981; color: #fff; text-decoration: none; padding: 0.5rem 1rem; border-radius: 8px; font-weight: 600; font-size: 0.85rem; transition: background 0.2s;">
                                View Item
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

</body>
</html>