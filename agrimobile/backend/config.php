<?php
// ============================================
// AgriMobile - Database Configuration
// ============================================

// CORS Headers - Allow cross-origin requests
// For localhost only (secure):
header("Access-Control-Allow-Origin: http://localhost");

// For mobile/network testing, uncomment the line below and comment the one above:
// header("Access-Control-Allow-Origin: *");

header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Credentials: true");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ============================================
// Database Configuration
// ============================================

$host = 'localhost';
$user = 'root';
$pass = '';          // XAMPP default: empty password
$dbname = 'agrimobile';

// Create connection
$conn = new mysqli($host, $user, $pass, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to UTF-8
$conn->set_charset("utf8mb4");

// ============================================
// Upload Directory Configuration
// ============================================

define('UPLOAD_DIR', __DIR__ . '/uploads/');

// Create uploads directory if it doesn't exist
if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0777, true);
}

// Optional: Set maximum file size (16MB)
ini_set('upload_max_filesize', '16M');
ini_set('post_max_size', '16M');
?>