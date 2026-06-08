<?php
require_once 'db.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['msg' => 'Unauthorized']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

if ($method === 'GET' && $order_id > 0) {
    $conn = getConnection();
    $stmt = $conn->prepare("SELECT * FROM payments WHERE order_id = ? ORDER BY payment_date DESC");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $payments = $result->fetch_all(MYSQLI_ASSOC);
    echo json_encode($payments);
    exit;
}

http_response_code(404);
echo json_encode(['msg' => 'Endpoint not found']);
?>