<?php
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/database.php';

$db = Database::getInstance();
$config = json_decode(file_get_contents(__DIR__ . '/config/config.json'), true);

// Fetch categories for display
$categories = $db->query("SELECT * FROM categories WHERE status = 'Active' LIMIT 6")->fetchAll();
// Fetch latest popular products
$products = $db->query("SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id WHERE p.status = 'Active' ORDER BY p.id DESC LIMIT 8")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ne">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($config['app']['name'] ?? 'Premium Grocery') ?> | Premium Grocery Delivery</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Global Reset & Theme Tokens */
        :root {
            --primary: #10b981;
            --primary-hover: #059669;
            --primary-light: #ecfdf5;
            --primary-dark: #064e3b;
            --danger: #ef4444;
            --warning: #f59e0b;
            --text-dark: #1e293b;
            --text-muted: #64748b;
            --bg-main: #f8fafc;
            --surface: #ffffff;
            --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.04);
            --shadow-md: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.05);
            --shadow-lg: 0 20px 25px -5px rgba(16, 185, 129, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            --radius-sm: 8px;
            --radius-md: 14px;
            --radius-lg: 24px;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background-color: var(--bg-main);
            color: var(--text-dark);
            line-height: 1.5;
        }

        /* Navigation */
        .nav-bar {
            position: sticky;
            top: 0;
            z-index: 100;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.25rem 5%;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(226, 232, 240, 0.8);
            box-shadow: var(--shadow-sm);
        }

        .nav-logo {
            color: var(--primary);
            font-weight: 800;
            font-size: 1.6rem;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 2rem;
        }

        .nav-links a {
            text-decoration: none;
            color: var(--text-dark);
            font-weight: 600;
            font-size: 0.95rem;
            transition: color 0.2s ease;
        }

        .nav-links a:hover {
            color: var(--primary);
        }

        .btn-main {
            background: var(--primary);
            color: white !important;
            padding: 0.65rem 1.5rem;
            border-radius: var(--radius-sm);
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);
            transition: all 0.2s ease;
        }

        .btn-main:hover {
            background: var(--primary-hover);
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(16, 185, 129, 0.3);
        }

        .logout-icon {
            font-size: 1.2rem;
            color: var(--text-muted);
        }
        .logout-icon:hover {
            color: var(--danger) !important;
        }

        /* Hero Banner */
        .hero {
            background: linear-gradient(135deg, #10b981 0%, #059669 50%, #047857 100%);
            color: white;
            padding: 6rem 5% 7rem 5%;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: -20%;
            right: -10%;
            width: 400px;
            height: 400px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 50%;
        }

        .hero-title {
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 1.25rem;
            letter-spacing: -0.02em;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .hero-subtitle {
            font-size: 1.25rem;
            margin-bottom: 2.5rem;
            opacity: 0.95;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
            font-weight: 400;
        }

        .btn-hero {
            background: #ffffff;
            color: var(--primary-dark) !important;
            font-weight: 700;
            padding: 0.9rem 2.5rem;
            border-radius: var(--radius-md);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .btn-hero:hover {
            background: var(--primary-light);
            transform: translateY(-2px);
            box-shadow: 0 12px 24px rgba(0,0,0,0.15);
        }

        /* Section Global Layout */
        .main-container {
            max-width: 1240px;
            margin: 0 auto;
            padding: 4rem 2rem;
        }

        .section-title {
            font-size: 1.8rem;
            font-weight: 800;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: var(--text-dark);
        }

        /* Categories Section Setup */
        .categories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 1.25rem;
            margin-bottom: 4.5rem;
        }

        .cat-card {
            background: var(--surface);
            text-align: center;
            padding: 1.75rem 1rem;
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-sm);
            text-decoration: none;
            color: inherit;
            border: 1px solid rgba(226, 232, 240, 0.6);
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.75rem;
        }

        .cat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-md);
            border-color: var(--primary);
        }

        .cat-icon-box {
            width: 55px;
            height: 55px;
            background: var(--primary-light);
            color: var(--primary);
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 1.4rem;
            transition: all 0.25s ease;
        }

        .cat-card:hover .cat-icon-box {
            background: var(--primary);
            color: white;
        }

        .cat-card h4 {
            font-size: 1rem;
            font-weight: 700;
            color: var(--text-dark);
        }

        /* Products Grid Grid */
        .grid-layout {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 2rem;
        }

        /* Product Cards */
        .product-card {
            position: relative;
            background: var(--surface);
            border-radius: var(--radius-md);
            padding: 1.25rem;
            border: 1px solid rgba(226, 232, 240, 0.7);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: var(--shadow-sm);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .product-card:hover {
            transform: translateY(-6px);
            box-shadow: var(--shadow-md);
            border-color: rgba(16, 185, 129, 0.3);
        }

        .product-image {
            height: 180px;
            background: linear-gradient(135deg, #f8fafc, #f1f5f9);
            border-radius: var(--radius-sm);
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 1.25rem;
            position: relative;
        }

        .product-image i {
            font-size: 4rem;
            color: #cbd5e1;
            transition: all 0.3s ease;
        }

        .product-card:hover .product-image i {
            color: var(--primary);
            transform: scale(1.1) rotate(-5deg);
        }

        .product-card h4 {
            font-size: 1.15rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
            color: var(--text-dark);
        }

        .product-weight {
            color: var(--text-muted);
            font-size: 0.88rem;
            margin-bottom: 0.75rem;
            font-weight: 500;
        }

        .rating {
            color: var(--warning);
            font-size: 0.85rem;
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
            gap: 2px;
        }

        .rating span {
            color: var(--text-muted);
            margin-left: 6px;
            font-weight: 600;
        }

        .price-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: auto;
        }

        .price {
            font-size: 1.35rem;
            font-weight: 800;
            color: var(--primary-dark);
        }

        .btn-cart {
            border: none;
            background: var(--primary);
            color: #fff;
            padding: 0.6rem 1.2rem;
            border-radius: var(--radius-sm);
            cursor:pointer;
            transition: all 0.2s ease;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
        }

        .btn-cart:hover {
            background: var(--primary-hover);
            box-shadow: 0 4px 12px rgba(5, 150, 105, 0.25);
        }

        .product-badge {
            position: absolute;
            top: 12px;
            left: 12px;
            background: var(--danger);
            color: #fff;
            padding: 4px 10px;
            border-radius: 30px;
            font-size: 0.72rem;
            font-weight: 700;
            z-index: 2;
            letter-spacing: 0.03em;
            text-transform: uppercase;
            box-shadow: 0 2px 6px rgba(239, 68, 68, 0.2);
        }

        /* Empty states */
        .empty-products {
            grid-column: 1/-1;
            text-align: center;
            padding: 5rem 2rem;
            background: var(--surface);
            border-radius: var(--radius-md);
            border: 2px dashed rgba(226, 232, 240, 1);
        }

        .empty-products i {
            font-size: 4.5rem;
            margin-bottom: 1.5rem;
            color: #cbd5e1;
        }

        .empty-products h3 {
            font-size: 1.4rem;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }

        /* Responsive Optimization */
        @media(max-width: 768px) {
            .hero-title { font-size: 2.2rem; }
            .hero-subtitle { font-size: 1.1rem; }
            .nav-bar { padding: 1rem 4%; }
            .nav-links { gap: 1.25rem; }
            .main-container { padding: 2.5rem 1.25rem; }
        }
    </style>
</head>
<body>

<nav class="nav-bar">
    <a href="index.php" class="nav-logo">
        <i class="fa-solid fa-leaf"></i>
        <span><?= htmlspecialchars($config['app']['name'] ?? 'Premium Grocery') ?></span>
    </a>
    <div class="nav-links">
        <a href="index.php">Home</a>
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="<?= $_SESSION['role'] === 'Super Admin' ? 'admin/index.php' : 'customer/index.php' ?>" class="btn-main">Dashboard</a>
            <a href="logout.php" class="logout-icon" title="Logout"><i class="fa-solid fa-right-from-bracket"></i></a>
        <?php else: ?>
            <a href="login.php">Login</a>
            <a href="register.php" class="btn-main">Register</a>
        <?php endif; ?>
    </div>
</nav>

<header class="hero">
    <h2 class="hero-title">ताजा तरकारी र खाद्यान्न सिधै तपाईंको दैलोमा!</h2>
    <p class="hero-subtitle">Fast, reliable, and premium quality grocery delivery across your city.</p>
    <a href="#shop" class="btn-main btn-hero">Shop Fresh Now</a>
</header>

<main class="main-container" id="shop">
    
    <!-- Dynamic Categories Grid (Now Displayed Correctly) -->
    <?php if (!empty($categories)): ?>
    <section style="margin-bottom: 4rem;">
        <h3 class="section-title">
            <i class="fa-solid fa-layer-group" style="color: var(--primary);"></i>
            Browse Categories
        </h3>
        <div class="categories-grid">
            <?php foreach($categories as $cat): ?>
                <a href="category.php?id=<?= $cat['id'] ?>" class="cat-card">
                    <div class="cat-icon-box">
                        <i class="fa-solid fa-basket-shopping"></i>
                    </div>
                    <h4><?= htmlspecialchars($cat['name']) ?></h4>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- Products Section -->
    <section>
        <h3 class="section-title">
            <i class="fa-solid fa-fire" style="color: var(--warning);"></i>
            Popular Daily Essentials
        </h3>

        <div class="grid-layout" id="product-container">
            <?php if (empty($products)): ?>
                <div class="empty-products">
                    <i class="fa-solid fa-box-open"></i>
                    <h3>No Products Available</h3>
                    <p>Products will appear here once added by the admin.</p>
                </div>
            <?php else: ?>
                <?php foreach($products as $prod): ?>
                    <div class="product-card">
                        <div>
                            <!-- Badge -->
                            <span class="product-badge">Bestseller</span>

                            <!-- Image Placeholder -->
                            <div class="product-image">
                                <i class="fa-solid fa-apple-whole"></i>
                            </div>

                            <!-- Name -->
                            <h4><?= htmlspecialchars($prod['name']) ?></h4>

                            <!-- Weight -->
                            <p class="product-weight">
                                <i class="fa-solid fa-scale-balanced" style="font-size: 0.8rem; margin-right: 4px;"></i> 
                                <?= htmlspecialchars($prod['weight'] ?? '1 Kg') ?>
                            </p>

                            <!-- Static Premium Rating Structure -->
                            <div class="rating">
                                <i class="fa-solid fa-star"></i>
                                <i class="fa-solid fa-star"></i>
                                <i class="fa-solid fa-star"></i>
                                <i class="fa-solid fa-star"></i>
                                <i class="fa-solid fa-star-half-stroke"></i>
                                <span>(4.5)</span>
                            </div>
                        </div>

                        <!-- Price Section / CTA Row -->
                        <div class="price-row">
                            <div>
                                <span class="price">Rs. <?= htmlspecialchars($prod['price']) ?></span>
                            </div>
                            <button onclick="addToCart(<?= $prod['id'] ?>)" class="btn-cart">
                                <i class="fa-solid fa-cart-plus"></i> Add
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>
</main>

<script src="assets/js/app.js"></script>
<script>
async function addToCart(productId) {
    <?php if (!isset($_SESSION['user_id'])): ?>
        window.location.href = 'login.php';
        return;
    <?php endif; ?>
    
    try {
        const response = await fetch('api/cart.php?action=add', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ product_id: productId, quantity: 1 })
        });
        const res = await response.json();
        if(res.success) {
            showToast("Item cataloged to cart successfully!");
        } else {
            showToast(res.message, "error");
        }
    } catch(err) {
        showToast("Network pipe breakdown.", "error");
    }
}
</script>
</body>
</html>