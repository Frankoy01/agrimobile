<?php
require_once 'db.php';
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['msg' => 'Unauthorized']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

// Add product (only farmers)
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

// Get all products (anyone)
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

// Delete product (only owner)
if ($method === 'DELETE') {
    parse_str(file_get_contents('php://input'), $data);
    $product_id = $data['product_id'] ?? null;
    if (!$product_id) {
        http_response_code(400);
        echo json_encode(['msg' => 'Product ID required']);
        exit;
    }
    $stmt = $conn->prepare("SELECT farmer_id FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
    if (!$product || $product['farmer_id'] != $_SESSION['user_id']) {
        http_response_code(403);
        echo json_encode(['msg' => 'You can only delete your own products']);
        exit;
    }
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    echo json_encode(['msg' => 'Product deleted']);
    exit;
}

// Update product (only owner)
if ($method === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);
    $product_id = $data['id'];
    $name = $data['name'];
    $price = $data['price'];
    $quantity = $data['quantity'];
    $description = $data['description'];
    $origin = $data['origin'];
    $category = $data['category'];
    
    $stmt = $conn->prepare("SELECT farmer_id FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
    if (!$product || $product['farmer_id'] != $_SESSION['user_id']) {
        http_response_code(403);
        echo json_encode(['msg' => 'You can only edit your own products']);
        exit;
    }
    
    $stmt = $conn->prepare("UPDATE products SET name=?, price=?, quantity=?, description=?, origin=?, category=? WHERE id=?");
    $stmt->bind_param("sdisssi", $name, $price, $quantity, $description, $origin, $category, $product_id);
    if ($stmt->execute()) {
        echo json_encode(['msg' => 'Product updated']);
    } else {
        http_response_code(500);
        echo json_encode(['msg' => 'Update failed']);
    }
    exit;
}

http_response_code(404);
?>