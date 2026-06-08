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

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

if ($order_id > 0) {
    $conn = getConnection();
    $stmt = $conn->prepare("SELECT * FROM payments WHERE order_id = ? ORDER BY payment_date DESC");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $payments = $result->fetch_all(MYSQLI_ASSOC);
    echo json_encode($payments);
    exit;
}

echo json_encode([]);
?>