<?php
require_once 'db.php';
session_start();
ob_clean();
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$action = $_GET['action'] ?? '';

if ($action === 'register') {
    $email = $data['email'] ?? '';
    $password = $data['password'] ?? '';
    $role = $data['role'] ?? '';
    $username = $data['username'] ?? '';
    $address = $data['address'] ?? '';

    if (empty($email) || empty($password) || empty($role)) {
        http_response_code(400);
        echo json_encode(['msg' => 'All fields required']);
        exit;
    }

    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $conn = getConnection();
    $stmt = $conn->prepare("INSERT INTO users (email, password, role, username, display_name, address) VALUES (?, ?, ?, ?, ?, ?)");
    $display_name = $username;
    $stmt->bind_param("ssssss", $email, $hashed, $role, $username, $display_name, $address);
    
    if ($stmt->execute()) {
        $userId = $stmt->insert_id;
        $_SESSION['user_id'] = $userId;
        $_SESSION['role'] = $role;
        echo json_encode(['user' => [
            'id' => $userId,
            'email' => $email,
            'role' => $role,
            'username' => $username,
            'display_name' => $display_name,
            'address' => $address,
            'created_at' => date('Y-m-d H:i:s')
        ]]);
    } else {
        http_response_code(400);
        echo json_encode(['msg' => 'Email already exists']);
    }
    exit;
}

if ($action === 'login') {
    $email = $data['email'] ?? '';
    $password = $data['password'] ?? '';

    if (empty($email) || empty($password)) {
        http_response_code(400);
        echo json_encode(['msg' => 'Email and password required']);
        exit;
    }

    $conn = getConnection();
    $stmt = $conn->prepare("SELECT id, email, password, role, username, display_name, address, created_at FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (!$user || !password_verify($password, $user['password'])) {
        http_response_code(401);
        echo json_encode(['msg' => 'Invalid credentials']);
        exit;
    }

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['role'] = $user['role'];
    echo json_encode(['user' => $user]);
    exit;
}

if ($action === 'update_profile') {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['msg' => 'Unauthorized']);
        exit;
    }
    $display_name = $data['display_name'] ?? '';
    $username = $data['username'] ?? '';
    $address = $data['address'] ?? '';
    
    $conn = getConnection();
    if (!empty($display_name)) {
        $stmt = $conn->prepare("UPDATE users SET display_name = ? WHERE id = ?");
        $stmt->bind_param("si", $display_name, $_SESSION['user_id']);
        $stmt->execute();
    }
    if (!empty($username)) {
        $stmt = $conn->prepare("UPDATE users SET username = ? WHERE id = ?");
        $stmt->bind_param("si", $username, $_SESSION['user_id']);
        $stmt->execute();
    }
    if ($address !== null) {
        $stmt = $conn->prepare("UPDATE users SET address = ? WHERE id = ?");
        $stmt->bind_param("si", $address, $_SESSION['user_id']);
        $stmt->execute();
    }
    echo json_encode(['msg' => 'Profile updated']);
    exit;
}

http_response_code(404);
echo json_encode(['msg' => 'Endpoint not found']);
?>