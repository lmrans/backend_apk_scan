<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PATCH, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, role");
header("Content-Type: application/json");

require_once "../config/database.php";

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$data = json_decode(file_get_contents("php://input"), true);

$role = $_GET['role'] ?? $data['role'] ?? null;

if ($role !== 'admin') {
    echo json_encode([
        "status" => false,
        "message" => "Akses khusus admin"
    ]);
    exit;
} json_decode(file_get_contents("php://input"), true);

switch ($action) {

    // ================= GET =================
    case 'get':

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


    // ================= TAMBAH =================
    case 'add':

        $kode   = trim($data['kode_barang'] ?? '');
        $nama   = trim($data['nama_barang'] ?? '');
        $stok   = (int)($data['stok'] ?? 0);
        $satuan = trim($data['satuan'] ?? '');

        if ($kode === '' || $nama === '' || $satuan === '') {
            echo json_encode(["status"=>false,"message"=>"Kode, Nama, dan Satuan wajib diisi"]);
            exit;
        }

        $stmt = $conn->prepare("INSERT INTO barang (kode_barang, nama_barang, stok, satuan) 
                                VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssis", $kode, $nama, $stok, $satuan);

        if ($stmt->execute()) {
            echo json_encode(["status"=>true,"message"=>"Barang berhasil ditambahkan"]);
        } else {
            echo json_encode(["status"=>false,"message"=>"Gagal menambahkan barang"]);
        }

        break;


    // ================= UPDATE STOK =================
    case 'update':

        $kode = trim($data['kode_barang'] ?? '');
        $tambah = (int)($data['stok_tambah'] ?? 0);

        $stmt = $conn->prepare("UPDATE barang SET stok = stok + ? WHERE TRIM(kode_barang)=TRIM(?)");
        $stmt->bind_param("is", $tambah, $kode);
        $stmt->execute();

        echo json_encode(["status"=>true,"message"=>"Stok berhasil diperbarui"]);
        break;


    // ================= DELETE =================
    case 'delete':

        $id = (int)($data['id'] ?? 0);

        $stmt = $conn->prepare("DELETE FROM barang WHERE id_barang = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        echo json_encode(["status"=>true,"message"=>"Barang berhasil dihapus"]);
        break;


    default:
        echo json_encode(["status"=>false,"message"=>"Action tidak dikenali"]);
        break;
}
?>