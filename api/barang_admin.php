<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PATCH, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Role");
header("Content-Type: application/json");

require_once "../config/database.php";

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$role = $_SERVER['HTTP_ROLE'] ?? null;
if ($role !== 'admin') {
    echo json_encode([
        "status" => false,
        "message" => "Akses khusus admin"
    ]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$data = json_decode(file_get_contents("php://input"), true);

switch ($method) {

    // ================= GET =================
    case 'GET':

        $query = "SELECT id_barang, kode_barang, nama_barang, stok, satuan, created_at 
                  FROM barang 
                  ORDER BY id_barang DESC";

        $result = $conn->query($query);
        $barang = [];

        while ($row = $result->fetch_assoc()) {
            $barang[] = $row;
        }

        echo json_encode([
            "status" => true,
            "data" => $barang
        ]);
        break;


    // ================= POST =================
    case 'POST':

        $kode   = trim($data['kode_barang'] ?? '');
        $nama   = trim($data['nama_barang'] ?? '');
        $stok   = (int)($data['stok'] ?? 0);
        $satuan = trim($data['satuan'] ?? '');

        if ($kode === '' || $nama === '' || $satuan === '') {
            echo json_encode([
                "status" => false,
                "message" => "Kode, Nama, dan Satuan wajib diisi"
            ]);
            exit;
        }

        if ($stok < 0) {
            echo json_encode([
                "status" => false,
                "message" => "Stok tidak boleh negatif"
            ]);
            exit;
        }

        // CEK DUPLIKAT
        $cek = $conn->prepare("SELECT id_barang FROM barang WHERE TRIM(kode_barang)=TRIM(?)");
        $cek->bind_param("s", $kode);
        $cek->execute();
        $cek->store_result();

        if ($cek->num_rows > 0) {
            echo json_encode([
                "status" => false,
                "message" => "Kode barang sudah digunakan"
            ]);
            exit;
        }

        $stmt = $conn->prepare("INSERT INTO barang (kode_barang, nama_barang, stok, satuan) 
                                VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssis", $kode, $nama, $stok, $satuan);

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


    // ================= PATCH (Tambah Stok) =================
    case 'PATCH':

        $kode = trim($data['kode_barang'] ?? '');
        $tambah = (int)($data['stok_tambah'] ?? 0);

        if ($kode === '' || $tambah <= 0) {
            echo json_encode([
                "status" => false,
                "message" => "Data tidak valid"
            ]);
            exit;
        }

        $stmt = $conn->prepare("UPDATE barang 
                                SET stok = stok + ? 
                                WHERE TRIM(kode_barang)=TRIM(?)");

        $stmt->bind_param("is", $tambah, $kode);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            echo json_encode([
                "status" => true,
                "message" => "Stok berhasil ditambahkan"
            ]);
        } else {
            echo json_encode([
                "status" => false,
                "message" => "Kode barang tidak ditemukan"
            ]);
        }

        break;


    // ================= DELETE =================
    case 'DELETE':

        $id = (int)($data['id'] ?? 0);

        if ($id <= 0) {
            echo json_encode([
                "status" => false,
                "message" => "ID wajib diisi"
            ]);
            exit;
        }

        $stmt = $conn->prepare("DELETE FROM barang WHERE id_barang = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            echo json_encode([
                "status" => true,
                "message" => "Barang berhasil dihapus"
            ]);
        } else {
            echo json_encode([
                "status" => false,
                "message" => "Gagal menghapus barang"
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
?>