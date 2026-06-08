<?php
require_once 'db.php';
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['msg' => 'Unauthorized']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$data = json_decode(file_get_contents('php://input'), true);

// POST: Place Order
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

// POST: Mark product as received (buyer)
if ($method === 'POST' && isset($_GET['action']) && $_GET['action'] === 'mark_received') {
    if ($_SESSION['role'] !== 'buyer') {
        http_response_code(403);
        echo json_encode(['msg' => 'Only buyers can mark product as received']);
        exit;
    }
    $order_id = isset($data['order_id']) ? (int)$data['order_id'] : 0;
    $conn = getConnection();
    $stmt = $conn->prepare("UPDATE orders SET product_received = 1, received_at = NOW() WHERE id = ? AND buyer_id = ?");
    $stmt->bind_param("ii", $order_id, $_SESSION['user_id']);
    $stmt->execute();
    echo json_encode(['msg' => 'Product marked as received']);
    exit;
}

// POST: Mark payment as received (farmer)
if ($method === 'POST' && isset($_GET['action']) && $_GET['action'] === 'mark_paid') {
    if ($_SESSION['role'] !== 'farmer') {
        http_response_code(403);
        echo json_encode(['msg' => 'Only farmers can mark payment as received']);
        exit;
    }
    $order_id = isset($data['order_id']) ? (int)$data['order_id'] : 0;
    $conn = getConnection();
    $stmt = $conn->prepare("UPDATE orders SET payment_received = 1, payment_received_at = NOW() WHERE id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    echo json_encode(['msg' => 'Payment marked as received']);
    exit;
}

// POST: Record payment (farmer)
if ($method === 'POST' && isset($_GET['action']) && $_GET['action'] === 'record_payment') {
    if ($_SESSION['role'] !== 'farmer') {
        http_response_code(403);
        echo json_encode(['msg' => 'Only farmers can record payments']);
        exit;
    }
    $order_id = isset($data['order_id']) ? (int)$data['order_id'] : 0;
    $amount = isset($data['amount']) ? (float)$data['amount'] : 0;
    $payment_method = isset($data['payment_method']) ? $data['payment_method'] : 'cash';
    $notes = isset($data['notes']) ? $data['notes'] : '';
    
    if ($order_id <= 0 || $amount <= 0) {
        http_response_code(400);
        echo json_encode(['msg' => 'Order ID and amount required']);
        exit;
    }
    
    $conn = getConnection();
    $stmt = $conn->prepare("INSERT INTO payments (order_id, amount, payment_method, notes) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("idss", $order_id, $amount, $payment_method, $notes);
    $stmt->execute();
    
    $stmt2 = $conn->prepare("SELECT SUM(amount) as total_paid FROM payments WHERE order_id = ?");
    $stmt2->bind_param("i", $order_id);
    $stmt2->execute();
    $result = $stmt2->get_result();
    $total_paid = $result->fetch_assoc()['total_paid'];
    
    $stmt3 = $conn->prepare("SELECT price, quantity FROM orders o JOIN products p ON o.product_id = p.id WHERE o.id = ?");
    $stmt3->bind_param("i", $order_id);
    $stmt3->execute();
    $result3 = $stmt3->get_result();
    $order_info = $result3->fetch_assoc();
    $total_amount = $order_info['price'] * $order_info['quantity'];
    
    if ($total_paid >= $total_amount) {
        $stmt4 = $conn->prepare("UPDATE orders SET payment_status = 'paid', paid_at = NOW() WHERE id = ?");
        $stmt4->bind_param("i", $order_id);
    } else if ($total_paid > 0) {
        $stmt4 = $conn->prepare("UPDATE orders SET payment_status = 'partial' WHERE id = ?");
        $stmt4->bind_param("i", $order_id);
    } else {
        $stmt4 = $conn->prepare("UPDATE orders SET payment_status = 'unpaid' WHERE id = ?");
        $stmt4->bind_param("i", $order_id);
    }
    $stmt4->execute();
    
    echo json_encode(['msg' => 'Payment recorded', 'total_paid' => $total_paid]);
    exit;
}

// POST: Update order status
if ($method === 'POST' && isset($_GET['action']) && $_GET['action'] === 'update_status') {
    $order_id = isset($data['order_id']) ? (int)$data['order_id'] : 0;
    $new_status = isset($data['status']) ? $data['status'] : '';
    $delivery_courier = isset($data['delivery_courier']) ? $data['delivery_courier'] : null;
    $tracking_number = isset($data['tracking_number']) ? $data['tracking_number'] : null;
    
    if ($order_id <= 0 || empty($new_status)) {
        http_response_code(400);
        echo json_encode(['msg' => 'Order ID and status required']);
        exit;
    }
    
    $valid_statuses = ['pending', 'confirmed', 'shipped', 'delivered', 'cancelled'];
    if (!in_array($new_status, $valid_statuses)) {
        http_response_code(400);
        echo json_encode(['msg' => 'Invalid status']);
        exit;
    }
    
    $conn = getConnection();
    $userId = $_SESSION['user_id'];
    $role = $_SESSION['role'];
    
    if ($role === 'farmer') {
        $stmt = $conn->prepare("SELECT o.id FROM orders o JOIN products p ON o.product_id = p.id WHERE o.id = ? AND p.farmer_id = ?");
        $stmt->bind_param("ii", $order_id, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 0) {
            http_response_code(403);
            echo json_encode(['msg' => 'You can only update orders on your own products']);
            exit;
        }
    } elseif ($role !== 'admin') {
        http_response_code(403);
        echo json_encode(['msg' => 'Only farmers or admins can update order status']);
        exit;
    }
    
    $delivered_at = ($new_status === 'delivered') ? date('Y-m-d H:i:s') : null;
    
    $stmt = $conn->prepare("UPDATE orders SET status = ?, delivery_courier = COALESCE(?, delivery_courier), tracking_number = COALESCE(?, tracking_number), delivered_at = COALESCE(?, delivered_at) WHERE id = ?");
    $stmt->bind_param("ssssi", $new_status, $delivery_courier, $tracking_number, $delivered_at, $order_id);
    $stmt->execute();
    
    echo json_encode(['msg' => 'Order status updated']);
    exit;
}

// GET: Retrieve orders
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