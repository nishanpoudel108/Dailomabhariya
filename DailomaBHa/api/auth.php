<?php
header("Content-Type: application/json");
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php'; // Ensures access to log_system_event()

$db = Database::getInstance();
$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];

    if ($action === 'login') {
        $email = filter_var($input['email'] ?? '', FILTER_VALIDATE_EMAIL);
        $password = $input['password'] ?? '';

        if (!$email || empty($password)) {
            echo json_encode(["success" => false, "message" => "Please enter structural parameters correctly."]);
            exit;
        }

        try {
            // Fetch account structure matching target address metrics
            $stmt = $db->prepare("SELECT u.*, r.name as role_name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.email = :email LIMIT 1");
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch();

            // Mitigation against user enumeration timing attacks:
            // Use a dummy hash calculation if the entry record array returns completely empty
            $dummyHash = '$2y$10$Uo7zN4L9fXyvK2N59R1Mee7uG2w6B/T6R1d5k7uO8mB2K9a4C8u3i'; 
            $targetHash = $user ? $user['password'] : $dummyHash;

            if (password_verify($password, $targetHash) && $user) {
                // Verify administrative account constraint states
                if (($user['status'] ?? '') === 'Blocked') {
                    echo json_encode(["success" => false, "message" => "Account suspended."]);
                    exit;
                }

                // Protect against session fixation vectors securely
                session_regenerate_id(true);

                $_SESSION['user_id'] = (int)$user['id'];
                $_SESSION['role']    = $user['role_name'];
                $_SESSION['name']    = $user['name'];

                // Track authentication clearance using global logger
                log_system_event($user['id'], "User Login Approved");

                echo json_encode([
                    "success" => true,
                    "message" => "Authentication clear.",
                    "role"    => $user['role_name']
                ]);
            } else {
                // Return generic mismatch notice regardless of execution vector
                echo json_encode(["success" => false, "message" => "Invalid credentials submitted."]);
            }
        } catch (PDOException $e) {
            echo json_encode(["success" => false, "message" => "Internal processing pipeline breakdown."]);
        }
        exit;
    }
}

// Fallback response context catchall routing 
http_response_code(400);
echo json_encode(["success" => false, "message" => "Unsupported routing mechanism request mapping."]);
exit;