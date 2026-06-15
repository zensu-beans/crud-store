<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/config.php';
$conn = db();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Read the cart payload sent from the frontend
$input = file_get_contents('php://input');
$cart = json_decode($input, true);

if (empty($cart) || !is_array($cart)) {
    http_response_code(400);
    echo json_encode(['error' => 'Your cart is empty or invalid.']);
    exit;
}

// Start a transaction to ensure all item stocks update successfully together
$conn->begin_transaction();

try {
    foreach ($cart as $item) {
        $id = (int)($item['id'] ?? 0);
        $qty = (int)($item['quantity'] ?? 0);

        if ($id <= 0 || $qty <= 0) {
            throw new Exception('Invalid product reference data.');
        }

        // Fetch current stock while locking the row for data consistency
        $stmt = $conn->prepare("SELECT stock, name FROM products WHERE id = ? FOR UPDATE");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $product = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$product) {
            throw new Exception("Product ID {$id} could not be found.");
        }

        if ($product['stock'] < $qty) {
            throw new Exception("Insufficient stock for '{$product['name']}'. Only {$product['stock']} units left.");
        }

        // Deduct the checked-out quantity from database stock
        $updateStmt = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
        $updateStmt->bind_param("ii", $qty, $id);
        $updateStmt->execute();
        $updateStmt->close();
    }

    // All balances verified and updated; commit the transaction
    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Checkout processed successfully.']);

} catch (Exception $e) {
    // Rollback changes if any item fails stock requirements
    $conn->rollback();
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
exit;
