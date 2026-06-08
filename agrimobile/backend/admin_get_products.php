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
    SELECT p.*, u.email as farmer_email 
    FROM products p 
    LEFT JOIN users u ON p.farmer_id = u.id 
    ORDER BY p.created_at DESC
");
$products = $result->fetch_all(MYSQLI_ASSOC);
echo json_encode($products);
?>