<?php
header("Content-Type: application/json");
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/session.php';

$db = Database::getInstance();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'read';

/* ==========================================
   Action Endpoint: Read Catalog Entities (Public/Dashboard)
   ========================================== */
if ($method === 'GET' && $action === 'read') {
    $categorySlug = $_GET['category'] ?? null;
    
    try {
        if ($categorySlug) {
            $stmt = $db->prepare("SELECT p.*, c.name as category_name 
                                  FROM products p 
                                  JOIN categories c ON p.category_id = c.id 
                                  WHERE c.slug = :slug AND p.status = 'Active' 
                                  ORDER BY p.id DESC");
            $stmt->execute(['slug' => $categorySlug]);
            echo json_encode($stmt->fetchAll());
        } else {
            // Filtered feed parameters to enforce customer-facing item isolation
            $stmt = $db->query("SELECT p.*, c.name as category_name 
                                FROM products p 
                                JOIN categories c ON p.category_id = c.id 
                                WHERE p.status = 'Active' 
                                ORDER BY p.id DESC");
            echo json_encode($stmt->fetchAll());
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Failed to retrieve catalog inventory parameters."]);
    }
    exit;
}

/* ==========================================
   Authentication Guard: Mutation Operations (Super Admin Only)
   ========================================== */
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Super Admin') {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "Access denied. Unauthorized admin token context rejected."]);
    exit;
}

/* ==========================================
   Action Endpoint: Add Product Entity
   ========================================== */
if ($method === 'POST' && $action === 'create') {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    
    // Store data values unescaped—sanitize on presentation layer instead
    $name       = trim($input['name'] ?? '');
    $categoryId = (int)($input['category_id'] ?? 0);
    $brand      = trim($input['brand'] ?? '');
    $sku        = trim($input['sku'] ?? '');
    $price      = (float)($input['price'] ?? 0.00);
    $stock      = (int)($input['stock'] ?? 0);
    $weight     = trim($input['weight'] ?? '');

    // Validation engine checkpoint
    if (empty($name) || $categoryId <= 0 || empty($sku) || $price <= 0 || $stock < 0) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Missing or invalid tracking fields."]);
        exit;
    }

    try {
        $stmt = $db->prepare("INSERT INTO products (category_id, name, brand, sku, price, stock, weight, status) 
                              VALUES (:cid, :name, :brand, :sku, :price, :stock, :weight, 'Active')");
        $success = $stmt->execute([
            'cid'    => $categoryId,
            'name'   => $name,
            'brand'  => $brand,
            'sku'    => $sku,
            'price'  => $price,
            'stock'  => $stock,
            'weight' => $weight
        ]);

        if ($success) {
            echo json_encode(["success" => true, "message" => "Product cataloged into storage metrics successfully."]);
        } else {
            echo json_encode(["success" => false, "message" => "Storage architecture failed to write entry."]);
        }
    } catch (PDOException $e) {
        // Handle database constraint exceptions (e.g., duplicate SKU matches)
        echo json_encode(["success" => false, "message" => "SKU key conflict or storage structural breakdown."]);
    }
    exit;
}

// Fallback routing endpoint catchall
http_response_code(400);
echo json_encode(["success" => false, "message" => "Unsupported routing mechanism request mapping."]);
exit;