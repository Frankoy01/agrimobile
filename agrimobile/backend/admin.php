<?php
require_once 'db.php';
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['msg' => 'Admin access required']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$path = explode('/', trim($_SERVER['PATH_INFO'] ?? '', '/'));

$conn = getConnection();

if ($method === 'GET' && $path[0] === 'stats') {
    $totalUsers = $conn->query("SELECT COUNT(*) FROM users")->fetch_row()[0];
    $totalProducts = $conn->query("SELECT COUNT(*) FROM products")->fetch_row()[0];
    $totalOrders = $conn->query("SELECT COUNT(*) FROM orders")->fetch_row()[0];
    $totalSold = $conn->query("SELECT COALESCE(SUM(quantity),0) FROM orders")->fetch_row()[0];
    echo json_encode(['total_users' => $totalUsers, 'total_products' => $totalProducts, 'total_orders' => $totalOrders, 'total_sold' => $totalSold]);
    exit;
}

if ($method === 'GET' && $path[0] === 'users') {
    $result = $conn->query("SELECT id, email, role, username, display_name, created_at FROM users ORDER BY created_at DESC");
    $users = $result->fetch_all(MYSQLI_ASSOC);
    echo json_encode($users);
    exit;
}

if ($method === 'DELETE' && $path[0] === 'users' && isset($path[1])) {
    $id = intval($path[1]);
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    echo json_encode(['msg' => 'User deleted']);
    exit;
}

if ($method === 'PUT' && $path[0] === 'users' && isset($path[1]) && $path[2] === 'role') {
    $id = intval($path[1]);
    $data = json_decode(file_get_contents('php://input'), true);
    $role = $data['role'];
    $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
    $stmt->bind_param("si", $role, $id);
    $stmt->execute();
    echo json_encode(['msg' => 'Role updated']);
    exit;
}

if ($method === 'GET' && $path[0] === 'products') {
    $result = $conn->query("SELECT p.*, u.email as farmer_email, u.display_name as farmer_name FROM products p LEFT JOIN users u ON p.farmer_id = u.id ORDER BY p.created_at DESC");
    $products = $result->fetch_all(MYSQLI_ASSOC);
    echo json_encode($products);
    exit;
}

if ($method === 'DELETE' && $path[0] === 'products' && isset($path[1])) {
    $id = intval($path[1]);
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    echo json_encode(['msg' => 'Product deleted']);
    exit;
}

if ($method === 'GET' && $path[0] === 'orders') {
    $result = $conn->query("SELECT o.*, p.name as product_name, buyer.email as buyer_email, buyer.display_name as buyer_name, farmer.email as farmer_email FROM orders o JOIN products p ON o.product_id = p.id JOIN users buyer ON o.buyer_id = buyer.id JOIN users farmer ON p.farmer_id = farmer.id ORDER BY o.order_date DESC");
    $orders = $result->fetch_all(MYSQLI_ASSOC);
    echo json_encode($orders);
    exit;
}

http_response_code(404);
echo json_encode(['msg' => 'Endpoint not found']);
?>