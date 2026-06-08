<?php
require_once 'db.php';
session_start();
ob_clean();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo '{"error":"unauthorized"}';
    exit;
}

$conn = getConnection();
$stmt = $conn->prepare("SELECT id, email, role, username, display_name, address, created_at FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    http_response_code(404);
    echo '{"error":"not found"}';
    exit;
}

echo json_encode($user);
exit;
?>