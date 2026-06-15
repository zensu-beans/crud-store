<?php
// Enable error reporting for debugging (Remove this line once fixed)
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle CORS Preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Start Session BEFORE checking admin status
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config.php';

// Connect to DB
$conn = db();

// Check if connection failed
if (!$conn) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

// Single, correct definition of is_admin()
function is_admin(): bool {
    // Session is already started at the top of the file
    return !empty($_SESSION['admin_id']);
}

$method = $_SERVER['REQUEST_METHOD'];

// GET — Public access
if ($method === 'GET') {
    $res = $conn->query("SELECT eyebrow, title, description, price, img_path FROM store_banner WHERE id = 1 LIMIT 1");
    if ($res) {
        echo json_encode($res->fetch_assoc() ?: []);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Query failed: ' . $conn->error]);
    }
    exit;
}

// PUT — Secure access
if ($method === 'PUT') {
    if (!is_admin()) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized. Please log in as admin.']);
        exit;
    }

    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    
    // Validate input
    if (empty($body)) {
        http_response_code(400);
        echo json_encode(['error' => 'No data provided']);
        exit;
    }

    $eyebrow    = $body['eyebrow'] ?? '';
    $title      = $body['title'] ?? '';
    $description = $body['description'] ?? '';
    $price      = $body['price'] ?? '';
    $img_path   = $body['img_path'] ?? '';

    $stmt = $conn->prepare("UPDATE store_banner SET eyebrow=?, title=?, description=?, price=?, img_path=? WHERE id=1");
    
    if ($stmt) {
        $stmt->bind_param('sssss', $eyebrow, $title, $description, $price, $img_path);
        $stmt->execute();
        $stmt->close();
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Prepare failed: ' . $conn->error]);
    }
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed.']);   
