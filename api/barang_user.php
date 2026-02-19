<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

require_once "../config/database.php";

$query = "SELECT id_barang, kode_barang, nama_barang, stok, harga FROM barang";
$result = $conn->query($query);

$barang = [];

while ($row = $result->fetch_assoc()) {
    $barang[] = $row;
}

echo json_encode([
    "status" => true,
    "data" => $barang
]);
