<?php

// ── Database credentials (Dynamic for Railway) ──────
// Railway injects these automatically via environment variables
$host = getenv('MYSQLHOST') ?: 'mysql.railway.internal';
$user = getenv('MYSQLUSER') ?: 'root';
$pass = getenv('MYSQLPASSWORD') ?: '';
$dbname = getenv('MYSQLDATABASE') ?: 'crud_store';
$port = getenv('MYSQLPORT') ?: '3306';

// ── Session ─────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── Connect ─────────────────────────────────────
function db(): mysqli {
    global $host, $user, $pass, $dbname, $port;
    static $conn = null;
    
    if ($conn === null) {
        // Notice we added the $port variable here for Railway
        $conn = new mysqli($host, $user, $pass, $dbname, $port);
        if ($conn->connect_error) {
            http_response_code(500);
            die(json_encode(['error' => 'Database connection failed: ' . $conn->connect_error]));
        }
        $conn->set_charset('utf8mb4');
    }
    return $conn;
}

// ── Auth guard: redirect if not logged in ───────
function require_login(): void {
    if (empty($_SESSION['admin_id'])) {
        header('Location: index.php');
        exit;
    }
}

// ── JSON response helper ─────────────────────────
function json_out(array $data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}
?>
