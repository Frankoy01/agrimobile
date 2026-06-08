<?php
require_once 'db.php';
session_start();
ob_clean();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$data = json_decode(file_get_contents('php://input'), true);

// Place order (buyer only)
if ($method === 'POST' && !isset($_GET['action'])) {
    if ($_SESSION['role'] !== 'buyer') {
        http_response_code(403);
        echo json_encode(['msg' => 'Only buyers can place orders']);
        exit;
    }
    
    $product_id = isset($data['product_id']) ? (int)$data['product_id'] : 0;
    $quantity = isset($data['quantity']) ? (int)$data['quantity'] : 0;
    
    if ($product_id <= 0 || $quantity <= 0) {
        http_response_code(400);
        echo json_encode(['msg' => 'Invalid product ID or quantity']);
        exit;
    }
    
    $conn = getConnection();
    $stmt = $conn->prepare("SELECT id, name, farmer_id, quantity, price FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
    
    if (!$product) {
        http_response_code(404);
        echo json_encode(['msg' => 'Product not found']);
        exit;
    }
    
    if ($product['quantity'] < $quantity) {
        http_response_code(400);
        echo json_encode(['msg' => 'Insufficient stock. Available: ' . $product['quantity'] . ' kg']);
        exit;
    }
    
    $buyer_id = $_SESSION['user_id'];
    
    $conn->begin_transaction();
    try {
        $stmt2 = $conn->prepare("INSERT INTO orders (buyer_id, product_id, quantity, status, payment_status) VALUES (?, ?, ?, 'pending', 'unpaid')");
        $stmt2->bind_param("iii", $buyer_id, $product_id, $quantity);
        $stmt2->execute();
        $order_id = $stmt2->insert_id;
        
        $newQty = $product['quantity'] - $quantity;
        $stmt3 = $conn->prepare("UPDATE products SET quantity = ? WHERE id = ?");
        $stmt3->bind_param("ii", $newQty, $product_id);
        $stmt3->execute();
        
        $conn->commit();
        echo json_encode(['msg' => 'Order placed successfully', 'order_id' => $order_id]);
    } catch (Exception $e) {
        $conn->rollback();
        http_response_code(500);
        echo json_encode(['msg' => 'Order failed: ' . $e->getMessage()]);
    }
    exit;
}

// Get orders
if ($method === 'GET') {
    $userId = $_SESSION['user_id'];
    $role = $_SESSION['role'];
    $conn = getConnection();
    
    if ($role === 'buyer') {
        $stmt = $conn->prepare("
            SELECT o.*, p.name as product_name, p.price, p.image_url,
                   (SELECT SUM(amount) FROM payments WHERE order_id = o.id) as total_paid
            FROM orders o 
            JOIN products p ON o.product_id = p.id 
            WHERE o.buyer_id = ? 
            ORDER BY o.order_date DESC
        ");
        $stmt->bind_param("i", $userId);
    } else {
        $stmt = $conn->prepare("
            SELECT o.*, p.name as product_name, p.price, p.image_url, u.email as buyer_email, u.display_name as buyer_name,
                   (SELECT SUM(amount) FROM payments WHERE order_id = o.id) as total_paid
            FROM orders o 
            JOIN products p ON o.product_id = p.id 
            JOIN users u ON o.buyer_id = u.id 
            WHERE p.farmer_id = ? 
            ORDER BY o.order_date DESC
        ");
        $stmt->bind_param("i", $userId);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $orders = $result->fetch_all(MYSQLI_ASSOC);
    echo json_encode($orders);
    exit;
}

http_response_code(404);
echo json_encode(['msg' => 'Endpoint not found']);
?>