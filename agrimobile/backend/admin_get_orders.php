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
$result = $conn->query("
    SELECT o.*, 
           p.name as product_name, 
           buyer.email as buyer_email, 
           farmer.email as farmer_email 
    FROM orders o 
    JOIN products p ON o.product_id = p.id 
    JOIN users buyer ON o.buyer_id = buyer.id 
    JOIN users farmer ON p.farmer_id = farmer.id 
    ORDER BY o.order_date DESC
");
$orders = $result->fetch_all(MYSQLI_ASSOC);
echo json_encode($orders);
?>