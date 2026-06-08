<?php
require_once 'db.php';
session_start();
ob_clean();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$conn = getConnection();
$totalUsers = $conn->query("SELECT COUNT(*) FROM users")->fetch_row()[0];
$totalProducts = $conn->query("SELECT COUNT(*) FROM products")->fetch_row()[0];
$totalOrders = $conn->query("SELECT COUNT(*) FROM orders")->fetch_row()[0];
$totalSold = $conn->query("SELECT COALESCE(SUM(quantity), 0) FROM orders")->fetch_row()[0];

echo json_encode([
    'total_users' => (int)$totalUsers,
    'total_products' => (int)$totalProducts,
    'total_orders' => (int)$totalOrders,
    'total_sold' => (int)$totalSold
]);
?>