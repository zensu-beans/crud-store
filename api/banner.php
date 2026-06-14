<?php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

// ── DB connection (Dynamic for Railway) ──
$host = getenv('MYSQLHOST') ?: 'localhost';
$user = getenv('MYSQLUSER') ?: 'root';
$pass = getenv('MYSQLPASSWORD') ?: '';
$db   = getenv('MYSQLDATABASE') ?: 'crud_store';
$port = getenv('MYSQLPORT') ?: '3306';

$conn = new mysqli($host, $user, $pass, $db, $port);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'DB connection failed: ' . $conn->connect_error]);
    exit;
}
$conn->set_charset('utf8mb4');

function is_admin(): bool {
    if (session_status() === PHP_SESSION_NONE) session_start();
    return !empty($_SESSION['admin_id']);
}

$method = $_SERVER['REQUEST_METHOD'];

// GET — Public access to view banner content on index.html
if ($method === 'GET') {
    $res = $conn->query("SELECT eyebrow, title, description, price, img_path FROM store_banner WHERE id = 1 LIMIT 1");
    echo json_encode($res->fetch_assoc() ?: []);
    exit;
}

// PUT — Secure access for admin updates from admin.html
if ($method === 'PUT') {
    if (!is_admin()) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }

    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    $eyebrow    = $body['eyebrow'] ?? '';
    $title      = $body['title'] ?? '';
    $description = $body['description'] ?? '';
    $price      = $body['price'] ?? '';
    $img_path   = $body['img_path'] ?? '';

    $stmt = $conn->prepare("UPDATE store_banner SET eyebrow=?, title=?, description=?, price=?, img_path=? WHERE id=1");
    $stmt->bind_param('sssss', $eyebrow, $title, $description, $price, $img_path);
    $stmt->execute();
    $stmt->close();

    echo json_encode(['success' => true]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed.']);
