<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
header("Content-Type: application/json");

require_once "../config/database.php";

/*
|--------------------------------------------------------------------------
| AMBIL DATA REQUEST (STABIL UNTUK HOSTING)
|--------------------------------------------------------------------------
*/

$rawData = file_get_contents("php://input");
$jsonData = json_decode($rawData, true);

// Gabungkan semua kemungkinan input
$data = array_merge($_GET, $_POST, $jsonData ?? []);

/*
|--------------------------------------------------------------------------
| VALIDASI ROLE (TIDAK BERGANTUNG HEADER)
|--------------------------------------------------------------------------
*/

$role = $data['role'] ?? null;

if ($role !== 'admin') {
    echo json_encode([
        "status" => false,
        "message" => "Akses khusus admin"
    ]);
    exit;
}

/*
|--------------------------------------------------------------------------
| ACTION HANDLER
|--------------------------------------------------------------------------
*/

$action = $data['action'] ?? 'get';

switch ($action) {

    /*
    |--------------------------------------------------------------------------
    | GET DATA
    |--------------------------------------------------------------------------
    */
    case 'get':

        $query = "SELECT id_barang, kode_barang, nama_barang, stok, satuan 
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


    /*
    |--------------------------------------------------------------------------
    | TAMBAH BARANG
    |--------------------------------------------------------------------------
    */
    case 'add':

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


    /*
    |--------------------------------------------------------------------------
    | UPDATE STOK (TAMBAH STOK)
    |--------------------------------------------------------------------------
    */
    case 'update':

        $kode = trim($data['kode_barang'] ?? '');
        $tambah = (int)($data['stok'] ?? 0);

        if ($kode === '' || $tambah <= 0) {
            echo json_encode([
                "status" => false,
                "message" => "Data tidak valid"
            ]);
            exit;
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


    /*
    |--------------------------------------------------------------------------
    | DELETE BARANG
    |--------------------------------------------------------------------------
    */
    case 'delete':

        $id = (int)($data['id_barang'] ?? 0);

        if ($id <= 0) {
            echo json_encode([
                "status" => false,
                "message" => "ID tidak valid"
            ]);
            exit;
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
            "message" => "Action tidak dikenali"
        ]);
        break;
}
?>