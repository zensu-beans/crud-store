<?php

// Allow cross-origin requests from same server
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Preflight
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

// ── Session check helper ──
function is_admin(): bool {
    if (session_status() === PHP_SESSION_NONE) session_start();
    return !empty($_SESSION['admin_id']);
}

$method = $_SERVER['REQUEST_METHOD'];

// ════════════════════════════════════════════
//  GET — public: return all products
// ════════════════════════════════════════════
if ($method === 'GET') {
    $where  = [];
    $params = [];
    $types  = '';

    // Optional search filter (used by products.html)
    if (!empty($_GET['q'])) {
        $where[]  = '(name LIKE ? OR category LIKE ?)';
        $q        = '%' . $_GET['q'] . '%';
        $params[] = $q;
        $params[] = $q;
        $types   .= 'ss';
    }

    // Optional category filter
    if (!empty($_GET['category'])) {
        $where[]  = 'category = ?';
        $params[] = $_GET['category'];
        $types   .= 's';
    }

    $sql = 'SELECT id, name, category, price, old_price AS oldPrice,
                   stock, rating, reviews, badge, img
            FROM products';

    if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
    $sql .= ' ORDER BY id ASC';

    $stmt = $conn->prepare($sql);
    if ($params) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Cast types so JS gets numbers not strings
    $rows = array_map(function($p) {
        $p['id']       = (int)$p['id'];
        $p['price']    = (float)$p['price'];
        $p['oldPrice'] = $p['oldPrice'] !== null ? (float)$p['oldPrice'] : null;
        $p['stock']    = (int)$p['stock'];
        $p['rating']   = (float)$p['rating'];
        $p['reviews']  = (int)$p['reviews'];
        return $p;
    }, $rows);

    echo json_encode($rows);
    exit;
}

// ════════════════════════════════════════════
//  Write operations — admin session required
// ════════════════════════════════════════════
if (!is_admin()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Parse JSON body for POST/PUT
$body = json_decode(file_get_contents('php://input'), true) ?? [];

function field(array $body, string $key, $default = '') {
    return $body[$key] ?? $default;
}

// ════════════════════════════════════════════
//  POST — create product
// ════════════════════════════════════════════
if ($method === 'POST') {
    $name     = trim(field($body, 'name'));
    $category = field($body, 'category');
    $price    = (float)field($body, 'price', 0);
    $oldPrice = field($body, 'oldPrice') !== '' && field($body, 'oldPrice') !== null
                    ? (float)field($body, 'oldPrice') : null;
    $stock    = (int)field($body, 'stock', 0);
    $rating   = (float)field($body, 'rating', 0);
    $reviews  = (int)field($body, 'reviews', 0);
    $badge    = field($body, 'badge', '');
    $img      = trim(field($body, 'img', ''));

    if (!$name || !$category || $price <= 0) {
        http_response_code(422);
        echo json_encode(['error' => 'Name, category, and price are required.']);
        exit;
    }

    $stmt = $conn->prepare('
        INSERT INTO products (name, category, price, old_price, stock, rating, reviews, badge, img)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ');
    $stmt->bind_param('ssddiidss',
        $name, $category, $price, $oldPrice, $stock, $rating, $reviews, $badge, $img
    );
    $stmt->execute();
    $newId = $conn->insert_id;
    $stmt->close();

    echo json_encode(['success' => true, 'id' => $newId]);
    exit;
}

// ════════════════════════════════════════════
//  PUT — update product
// ════════════════════════════════════════════
if ($method === 'PUT') {
    $id       = (int)field($body, 'id', 0);
    $name     = trim(field($body, 'name'));
    $category = field($body, 'category');
    $price    = (float)field($body, 'price', 0);
    $oldPrice = field($body, 'oldPrice') !== '' && field($body, 'oldPrice') !== null
                    ? (float)field($body, 'oldPrice') : null;
    $stock    = (int)field($body, 'stock', 0);
    $rating   = (float)field($body, 'rating', 0);
    $reviews  = (int)field($body, 'reviews', 0);
    $badge    = field($body, 'badge', '');
    $img      = trim(field($body, 'img', ''));

    if (!$id || !$name || !$category || $price <= 0) {
        http_response_code(422);
        echo json_encode(['error' => 'ID, name, category, and price are required.']);
        exit;
    }

    $stmt = $conn->prepare('
        UPDATE products
        SET name=?, category=?, price=?, old_price=?, stock=?, rating=?, reviews=?, badge=?, img=?
        WHERE id=?
    ');
    $stmt->bind_param('ssddiidssi',
        $name, $category, $price, $oldPrice, $stock, $rating, $reviews, $badge, $img, $id
    );
    $stmt->execute();
    $stmt->close();

    echo json_encode(['success' => true]);
    exit;
}

// ════════════════════════════════════════════
//  DELETE — remove product
// ════════════════════════════════════════════
if ($method === 'DELETE') {
    $id = (int)($_GET['id'] ?? 0);

    if (!$id) {
        http_response_code(422);
        echo json_encode(['error' => 'Invalid id.']);
        exit;
    }

    $stmt = $conn->prepare('DELETE FROM products WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();

    echo json_encode(['success' => true]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed.']);
