<?php
header("Content-Type: application/json");
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/session.php';

$db = Database::getInstance();
$action = $_GET['action'] ?? '';
$input = json_decode(file_get_contents('php://input'), true) ?? [];

/* ==========================================
   Action Endpoint: Add to Cart
   ========================================== */
if ($action === 'add') {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(["success" => false, "message" => "Unauthorized access vector rejected."]);
        exit;
    }

    $pid = (int)($input['product_id'] ?? 0);
    $qty = (int)($input['quantity'] ?? 1);

    if ($pid <= 0 || $qty <= 0) {
        echo json_encode(["success" => false, "message" => "Invalid transactional product quantity metrics requested."]);
        exit;
    }

    try {
        $prodChk = $db->prepare("SELECT stock FROM products WHERE id = :pid AND status = 'Active' LIMIT 1");
        $prodChk->execute(['pid' => $pid]);
        $product = $prodChk->fetch();

        if (!$product || (int)$product['stock'] < $qty) {
            echo json_encode(["success" => false, "message" => "Requested product quantity exceeds warehouse metrics."]);
            exit;
        }

        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }

        if (isset($_SESSION['cart'][$pid])) {
            $_SESSION['cart'][$pid] += $qty;
        } else {
            $_SESSION['cart'][$pid] = $qty;
        }

        echo json_encode(["success" => true, "message" => "Item cataloged into current session cart."]);
    } catch (PDOException $e) {
        echo json_encode(["success" => false, "message" => "Internal processing pipeline breakdown."]);
    }
    exit;
}

/* ==========================================
   Action Endpoint: Update Quantity
   ========================================== */
if ($action === 'update_qty') {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(["success" => false, "message" => "Unauthorized access vector rejected."]);
        exit;
    }

    $pid = (int)($input['product_id'] ?? 0);
    $qty = (int)($input['qty'] ?? 0); 

    if ($pid <= 0 || $qty < 0) {
        echo json_encode(["success" => false, "message" => "Invalid product quantity structural boundaries requested."]);
        exit;
    }

    try {
        if ($qty === 0) {
            unset($_SESSION['cart'][$pid]);
        } else {
            $stockStmt = $db->prepare("SELECT price, stock FROM products WHERE id = :pid AND status = 'Active' LIMIT 1");
            $stockStmt->execute(['pid' => $pid]);
            $product = $stockStmt->fetch(PDO::FETCH_ASSOC);

            if (!$product) {
                echo json_encode(["success" => false, "message" => "Product missing or inactive."]);
                exit;
            }

            if ((int)$product['stock'] < $qty) {
                echo json_encode(["success" => false, "message" => "Requested update changes exceed total local stock limits."]);
                exit;
            }

            $_SESSION['cart'][$pid] = $qty;
        }

        $subtotal = 0;
        $updatedItemTotal = 0;

        if (!empty($_SESSION['cart'])) {
            $placeholders = implode(',', array_fill(0, count($_SESSION['cart']), '?'));
            $stmt = $db->prepare("SELECT id, price FROM products WHERE id IN ($placeholders) AND status = 'Active'");
            $stmt->execute(array_keys($_SESSION['cart']));
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($products as $prod) {
                $itemQty = $_SESSION['cart'][$prod['id']];
                $rowTotal = $prod['price'] * $itemQty;
                $subtotal += $rowTotal;

                if ($prod['id'] == $pid) {
                    $updatedItemTotal = $rowTotal;
                }
            }
        }

        $config = json_decode(file_get_contents(__DIR__ . '/../config/config.json'), true);
        $taxRate = $config['app']['tax_rate'] ?? 0.13;
        $deliveryCharge = $subtotal > 0 ? ($config['app']['delivery_charge'] ?? 100) : 0;

        $tax = $subtotal * $taxRate;
        $grandTotal = $subtotal + $tax + $deliveryCharge;

        echo json_encode([
            "success"     => true,
            "item_total"  => $updatedItemTotal,
            "subtotal"    => $subtotal,
            "tax"         => $tax,
            "delivery"    => $deliveryCharge,
            "grand_total" => $grandTotal,
            "cart_empty"  => empty($_SESSION['cart'])
        ]);

    } catch (PDOException $e) {
        echo json_encode(["success" => false, "message" => "Internal processing failed."]);
    }
    exit;
}

/* ==========================================
   Action Endpoint: Checkout Process Engine
   ========================================== */
if ($action === 'checkout') {
    if (!isset($_SESSION['user_id']) || empty($_SESSION['cart'])) {
        echo json_encode(["success" => false, "message" => "Unauthorized access or empty shopping cart context."]);
        exit;
    }

    $userId = $_SESSION['user_id'];
    $paymentMethod = $input['payment_method'] ?? 'COD';
    
    $latitude = isset($input['latitude']) ? (float)$input['latitude'] : null;
    $longitude = isset($input['longitude']) ? (float)$input['longitude'] : null;

    if (!$latitude || !$longitude) {
        echo json_encode(["success" => false, "message" => "Delivery drop validation parameters are required to process dispatch routing."]);
        exit;
    }

    try {
        $db->beginTransaction();

        $config = json_decode(file_get_contents(__DIR__ . '/../config/config.json'), true);
        $placeholders = implode(',', array_fill(0, count($_SESSION['cart']), '?'));
        
        $stmt = $db->prepare("SELECT * FROM products WHERE id IN ($placeholders) AND status = 'Active'");
        $stmt->execute(array_keys($_SESSION['cart']));
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $subtotal = 0;
        foreach ($products as $prod) {
            $qty = $_SESSION['cart'][$prod['id']];
            $subtotal += $prod['price'] * $qty;
        }

        $tax = $subtotal * ($config['app']['tax_rate'] ?? 0.13);
        $delivery = $subtotal > 0 ? ($config['app']['delivery_charge'] ?? 100) : 0;
        $grandTotal = $subtotal + $tax + $delivery;

        $orderStmt = $db->prepare("INSERT INTO orders (user_id, total_amount, payment_method, status, latitude, longitude, created_at) VALUES (:user_id, :total, :method, 'Pending', :lat, :lng, NOW())");
        $orderStmt->execute([
            'user_id' => $userId,
            'total'   => $grandTotal,
            'method'  => $paymentMethod,
            'lat'     => $latitude,
            'lng'     => $longitude
        ]);
        
        $orderId = $db->lastInsertId();

        $itemStmt = $db->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (:order_id, :product_id, :qty, :price)");
        foreach ($products as $prod) {
            $pid = $prod['id'];
            $qty = $_SESSION['cart'][$pid];
            
            $itemStmt->execute([
                'order_id'   => $orderId,
                'product_id' => $pid,
                'qty'        => $qty,
                'price'      => $prod['price']
            ]);

            $stockUpdate = $db->prepare("UPDATE products SET stock = stock - :qty WHERE id = :pid");
            $stockUpdate->execute(['qty' => $qty, 'pid' => $pid]);
        }

        unset($_SESSION['cart']);
        
        $db->commit();
        echo json_encode([
            "success"  => true, 
            "message"  => "Your order delivery route has been locked in successfully!", 
            "order_id" => $orderId
        ]);

    } catch (Exception $e) {
        $db->rollBack();
        echo json_encode(["success" => false, "message" => "Transactional rollback engine fired: " . $e->getMessage()]);
    }
    exit;
}

echo json_encode(["success" => false, "message" => "Invalid operational gateway endpoint action."]);
exit;