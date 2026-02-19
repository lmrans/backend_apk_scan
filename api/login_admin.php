<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('html_errors', 0);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Accept");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

require_once "../config/database.php";

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if (!$conn) {
    echo json_encode([
        "status" => false,
        "message" => "Koneksi database gagal"
    ]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

if (!is_array($data)) {
    echo json_encode([
        "status" => false,
        "message" => "Data JSON tidak valid"
    ]);
    exit;
}

$username = trim($data['username'] ?? '');
$password = trim($data['password'] ?? '');

if ($username === '' || $password === '') {
    echo json_encode([
        "status" => false,
        "message" => "Username dan password wajib diisi"
    ]);
    exit;
}

$username = mysqli_real_escape_string($conn, $username);
$password = mysqli_real_escape_string($conn, $password);

$query = mysqli_query(
    $conn,
    "SELECT id_user FROM users 
     WHERE username='$username' 
     AND password='$password'"
);

if ($query && mysqli_num_rows($query) > 0) {
    echo json_encode([
        "status" => true,
        "message" => "Login admin berhasil"
    ]);
} else {
    echo json_encode([
        "status" => false,
        "message" => "Username atau password salah"
    ]);
}
exit;
