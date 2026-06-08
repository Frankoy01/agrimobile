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

$method = $_SERVER['REQUEST_METHOD'];

// Add product (farmer only)
if ($method === 'POST') {
    if ($_SESSION['role'] !== 'farmer') {
        http_response_code(403);
        echo json_encode(['msg' => 'Only farmers can add products']);
        exit;
    }
    $name = $_POST['name'];
    $price = $_POST['price'];
    $quantity = $_POST['quantity'];
    $description = $_POST['description'] ?? '';
    $origin = $_POST['origin'] ?? '';
    $category = $_POST['category'] ?? '';
    $farmer_id = $_SESSION['user_id'];
    
    $image_url = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array($ext, $allowed)) {
            $filename = time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
            $destination = UPLOAD_DIR . $filename;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $destination)) {
                $image_url = '/agrimobile/backend/uploads/' . $filename;
            }
        }
    }
    
    $stmt = $conn->prepare("INSERT INTO products (name, price, quantity, description, origin, category, image_url, farmer_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sdissssi", $name, $price, $quantity, $description, $origin, $category, $image_url, $farmer_id);
    if ($stmt->execute()) {
        echo json_encode(['id' => $stmt->insert_id, 'msg' => 'Product added']);
    } else {
        http_response_code(500);
        echo json_encode(['msg' => 'Database error']);
    }
    exit;
}

// Get all products
if ($method === 'GET' && !isset($_GET['my'])) {
    $result = $conn->query("SELECT * FROM products ORDER BY created_at DESC");
    $products = $result->fetch_all(MYSQLI_ASSOC);
    echo json_encode($products);
    exit;
}

// Get farmer's own products
if ($method === 'GET' && isset($_GET['my'])) {
    $farmer_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT * FROM products WHERE farmer_id = ? ORDER BY created_at DESC");
    $stmt->bind_param("i", $farmer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $products = $result->fetch_all(MYSQLI_ASSOC);
    echo json_encode($products);
    exit;
}

http_response_code(404);
echo json_encode(['msg' => 'Endpoint not found']);
?>