<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid structural request method.']);
    exit;
}

require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

$inputData = file_get_contents('php://input');
$data = json_decode($inputData, true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Malformed dataset transmission.']);
    exit;
}

$name = trim($data['name'] ?? '');
$email = trim($data['email'] ?? '');
$mobile = trim($data['mobile'] ?? '');
$password = $data['password'] ?? '';
$latitude = isset($data['latitude']) ? (float)$data['latitude'] : null;
$longitude = isset($data['longitude']) ? (float)$data['longitude'] : null;

// Basic Field Validation
if (empty($name) || empty($email) || empty($mobile) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'All core profile data fields are required.']);
    exit;
}

// Map Coordinate System Validation Rules
if (empty($latitude) || empty($longitude)) {
    echo json_encode(['success' => false, 'message' => 'Delivery drop verification coordinates are missing.']);
    exit;
}

if (!preg_match('/^[9][6-8][0-9]{8}$/', $mobile)) {
    echo json_encode(['success' => false, 'message' => 'Please provide a valid 10-digit mobile number.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email address syntax structure.']);
    exit;
}

if (strlen($password) < 6) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters long.']);
    exit;
}

try {
    $db = Database::getInstance();

    // 1. Dynamic Column Discovery Loop
    $columnsDesc = $db->query("DESCRIBE roles")->fetchAll(PDO::FETCH_COLUMN);
    $targetColumn = null;

    // Check what column name your schema actually uses for labels
    if (in_array('name', $columnsDesc)) {
        $targetColumn = 'name';
    } elseif (in_array('title', $columnsDesc)) {
        $targetColumn = 'title';
    } elseif (in_array('role', $columnsDesc)) {
        $targetColumn = 'role';
    } else {
        // Fallback fallback selector if matching strings aren't explicitly hit
        $targetColumn = $columnsDesc[1] ?? 'id'; 
    }

    // 2. Fetch the Customer Role using the column we just found
    $roleStmt = $db->prepare("SELECT id FROM roles WHERE {$targetColumn} LIKE :roleName LIMIT 1");
    $roleStmt->execute(['roleName' => '%Customer%']);
    $roleResult = $roleStmt->fetch(PDO::FETCH_ASSOC);

    if (!$roleResult) {
        echo json_encode(['success' => false, 'message' => "System configuration error: Please ensure a 'Customer' entry exists in your roles table."]);
        exit;
    }
    $customerRoleId = $roleResult['id'];

    // 3. Prevent duplicate user registrations across unique indexes
    $checkStmt = $db->prepare("SELECT id FROM users WHERE email = :email OR mobile = :mobile LIMIT 1");
    $checkStmt->execute(['email' => $email, 'mobile' => $mobile]);
    
    if ($checkStmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Email address or mobile number already registered.']);
        exit;
    }

    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    // 4. Save profile dataset record containing geographical location and mapped role_id
    $insertStmt = $db->prepare("INSERT INTO users (name, email, mobile, password, role_id, latitude, longitude, created_at) VALUES (:name, :email, :mobile, :password, :role_id, :latitude, :longitude, NOW())");
    
    $executionSuccess = $insertStmt->execute([
        'name'      => $name,
        'email'     => $email,
        'mobile'    => $mobile,
        'password'  => $hashedPassword,
        'role_id'   => $customerRoleId,
        'latitude'  => $latitude,
        'longitude' => $longitude
    ]);

    if ($executionSuccess) {
        echo json_encode(['success' => true, 'message' => 'Profile setup successfully completed with coordinates.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to build user profile metadata row.']);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database exception error: ' . $e->getMessage()]);
}
exit;