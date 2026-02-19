<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

require_once "../config/database.php";

$data = json_decode(file_get_contents("php://input"), true);

$id_barang = isset($data['id_barang']) ? intval($data['id_barang']) : 0;
$nama_pengambil = isset($data['nama_pengambil']) ? trim($data['nama_pengambil']) : '';
$jumlah = isset($data['jumlah']) ? intval($data['jumlah']) : 0;

if ($id_barang <= 0 || empty($nama_pengambil) || $jumlah <= 0) {
    echo json_encode([
        "status" => false,
        "message" => "Data tidak lengkap"
    ]);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}


// Ambil data barang
$stmt = $conn->prepare("SELECT stok, kode_barang FROM barang WHERE id_barang = ?");
$stmt->bind_param("i", $id_barang);
$stmt->execute();
$result = $stmt->get_result();
$barang = $result->fetch_assoc();

if (!$barang) {
    echo json_encode([
        "status" => false,
        "message" => "Barang tidak ditemukan"
    ]);
    exit;
}

if ($barang['stok'] < $jumlah) {
    echo json_encode([
        "status" => false,
        "message" => "Stok tidak cukup"
    ]);
    exit;
}

$kode_barang = $barang['kode_barang'];

$conn->begin_transaction();

try {

    // Kurangi stok
    $update = $conn->prepare("UPDATE barang SET stok = stok - ? WHERE id_barang = ?");
    $update->bind_param("ii", $jumlah, $id_barang);
    $update->execute();

    // Simpan transaksi
    $insert = $conn->prepare("INSERT INTO transaksi 
        (id_barang, kode_barang, nama_pengambil, jumlah, waktu_transaksi) 
        VALUES (?, ?, ?, ?, NOW())");

    $insert->bind_param("issi", $id_barang, $kode_barang, $nama_pengambil, $jumlah);
    $insert->execute();

    $conn->commit();

    echo json_encode([
        "status" => true,
        "message" => "Transaksi berhasil disimpan"
    ]);
} catch (Exception $e) {

    $conn->rollback();

    echo json_encode([
        "status" => false,
        "message" => "Gagal simpan transaksi"
    ]);
}
