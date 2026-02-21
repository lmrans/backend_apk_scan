<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Role");
header("Content-Type: application/json");

require_once "../config/database.php";

/* ================= HANDLE PREFLIGHT ================= */
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

/* ================= AMBIL ROLE DARI HEADER ================= */
$role = $_SERVER['HTTP_ROLE'] ?? null;

if ($role !== 'admin') {
    echo json_encode([
        "status" => false,
        "message" => "Akses khusus admin"
    ]);
    exit();
}

/* ================= AMBIL METHOD & DATA ================= */
$method = $_SERVER['REQUEST_METHOD'];
$rawData = file_get_contents("php://input");
$data = json_decode($rawData, true);

/* ================= SWITCH METHOD ================= */
switch ($method) {

    /* ================= GET ================= */
    case 'GET':

        $result = $conn->query("SELECT id_barang, kode_barang, nama_barang, stok, satuan FROM barang ORDER BY id_barang DESC");

        $barang = [];
        while ($row = $result->fetch_assoc()) {
            $barang[] = $row;
        }

        echo json_encode([
            "status" => true,
            "data" => $barang
        ]);
        break;


    /* ================= POST (TAMBAH) ================= */
    case 'POST':

        $kode   = trim($data['kode_barang'] ?? '');
        $nama   = trim($data['nama_barang'] ?? '');
        $stok   = (int)($data['stok'] ?? 0);
        $harga = $input['harga'] ?? 0;
        $satuan = trim($data['satuan'] ?? '');

        if ($kode == '' || $nama == '' || $satuan == '') {
            echo json_encode([
                "status" => false,
                "message" => "Data tidak lengkap"
            ]);
            exit();
        }

        $stmt = $conn->prepare("INSERT INTO barang (kode_barang, nama_barang, stok, satuan, harga) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssis", $kode, $nama, $stok, $satuan, $harga);

        if ($stmt->execute()) {
            echo json_encode([
                "status" => true,
                "message" => "Barang berhasil ditambahkan"
            ]);
        } else {
            echo json_encode([
                "status" => false,
                "message" => "Gagal menambahkan barang"
            ]);
        }

        break;


    /* ================= PUT (UPDATE STOK) ================= */
    case 'PUT':

        $kode = trim($data['kode_barang'] ?? '');
        $tambah = (int)($data['stok'] ?? 0);

        if ($kode == '' || $tambah <= 0) {
            echo json_encode([
                "status" => false,
                "message" => "Data tidak valid"
            ]);
            exit();
        }

        $stmt = $conn->prepare("UPDATE barang SET stok = stok + ? WHERE kode_barang = ?");
        $stmt->bind_param("is", $tambah, $kode);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            echo json_encode([
                "status" => true,
                "message" => "Stok berhasil diperbarui"
            ]);
        } else {
            echo json_encode([
                "status" => false,
                "message" => "Barang tidak ditemukan"
            ]);
        }

        break;


    /* ================= DELETE ================= */
    case 'DELETE':

        $id = (int)($data['id_barang'] ?? 0);

        if ($id <= 0) {
            echo json_encode([
                "status" => false,
                "message" => "ID tidak valid"
            ]);
            exit();
        }

        $stmt = $conn->prepare("DELETE FROM barang WHERE id_barang = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            echo json_encode([
                "status" => true,
                "message" => "Barang berhasil dihapus"
            ]);
        } else {
            echo json_encode([
                "status" => false,
                "message" => "Barang tidak ditemukan"
            ]);
        }

        break;


    default:
        echo json_encode([
            "status" => false,
            "message" => "Method tidak dikenali"
        ]);
        break;
}
