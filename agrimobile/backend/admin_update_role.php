<?php
require_once 'db.php';
session_start();
ob_clean();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$userId = isset($input['id']) ? (int)$input['id'] : 0;
$role = isset($input['role']) ? $input['role'] : '';

if ($userId && in_array($role, ['farmer', 'buyer', 'admin'])) {
    $conn = getConnection();
    $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
    $stmt->bind_param("si", $role, $userId);
    $stmt->execute();
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['error' => 'Invalid data']);
}
?>