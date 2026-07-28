<?php
header("Content-Type: application/json");
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/session.php';

$db = Database::getInstance();
$action = $_GET['action'] ?? '';

// Parse inbound JSON payloads cleanly
$input = json_decode(file_get_contents('php://input'), true) ?? [];

/* ==========================================
   Action Endpoint: Account Registration Matrix
   ========================================== */
if ($action === 'register') {
    $name = trim($input['name'] ?? '');
    $email = filter_var($input['email'] ?? '', FILTER_VALIDATE_EMAIL);
    $mobile = trim($input['mobile'] ?? '');
    $password = $input['password'] ?? '';

    if (empty($name) || !$email || empty($mobile) || strlen($password) < 6) {
        echo json_encode(["success" => false, "message" => "Invalid parameters. Password requires 6+ characters."]);
        exit;
    }

    try {
        // Prevent duplicate user registrations
        $chk = $db->prepare("SELECT id FROM users WHERE email = :email OR mobile = :mobile LIMIT 1");
        $chk->execute(['email' => $email, 'mobile' => $mobile]);
        if ($chk->fetch()) {
            echo json_encode(["success" => false, "message" => "Email or phone number already registered inside system metrics."]);
            exit;
        }

        // Generate cryptographically secure hash mapping
        $hashed = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
        
        // Persist structured user state to core ledger
        $stmt = $db->prepare("INSERT INTO users (role_id, name, email, mobile, password, status) VALUES (2, :name, :email, :mobile, :password, 'Active')");
        $success = $stmt->execute([
            'name'     => $name,
            'email'    => $email,
            'mobile'   => $mobile,
            'password' => $hashed
        ]);

        if ($success) {
            echo json_encode(["success" => true, "message" => "Profile successfully injected."]);
        } else {
            echo json_encode(["success" => false, "message" => "Database level constraint breakdown encountered."]);
        }
    } catch (PDOException $e) {
        echo json_encode(["success" => false, "message" => "Internal database processing failure."]);
    }
    exit;
}

/* ==========================================
   Action Endpoint: Session Cart Management (Add)
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
        // Verify warehouse stock limits securely
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
   ADDED: Action Endpoint: Update Quantity
   ========================================== */
if ($action === 'update_qty') {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(["success" => false, "message" => "Unauthorized access vector rejected."]);
        exit;
    }

    $pid = (int)($input['product_id'] ?? 0);
    $qty = (int)($input['qty'] ?? 0); // Named 'qty' to precisely match frontend JS payload

    if ($pid <= 0 || $qty < 0) {
        echo json_encode(["success" => false, "message" => "Invalid product quantity structural boundaries requested."]);
        exit;
    }

    try {
        // 1. If quantity is explicit 0, remove item from session array completely
        if ($qty === 0) {
            unset($_SESSION['cart'][$pid]);
        } else {
            // Check inventory stock safety limits
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

            // Assign updated count inside Session State array
            $_SESSION['cart'][$pid] = $qty;
        }

        // 2. Compute live structural pricing to return to the interactive frontend interface
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

        // Load pricing structures from application JSON configuration manifest
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
        echo json_encode(["success" => false, "message" => "Internal math recalculation matrix processing failed."]);
    }
    exit;
}

// Fallback response context catchall routing 
echo json_encode(["success" => false, "message" => "Invalid operational gateway endpoint action."]);
exit;