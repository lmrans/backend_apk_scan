<?php
header("Content-Type: application/json");
require_once "../config/database.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => false]);
    exit;
}

$id_user = $_POST['id'] ?? null;

if (!$id_user) {
    echo json_encode(["status" => false, "message" => "ID wajib"]);
    exit;
}

$stmt = $conn->prepare("DELETE FROM transaksi WHERE id=?");
$stmt->bind_param("i", $id_user);
$stmt->execute();

echo json_encode(["status" => true]);
