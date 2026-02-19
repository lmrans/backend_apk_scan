<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Role");
header("Content-Type: application/json");

require_once "../config/database.php";

// Menangani Preflight Request CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$role = $_SERVER['HTTP_ROLE'] ?? null;
if ($role !== 'admin') {
    echo json_encode(["status" => false, "message" => "Akses khusus admin"]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$data = json_decode(file_get_contents("php://input"), true);

switch ($method) {
    // 1. GET: Ambil Semua Data Barang
    case 'GET':
        $query = "SELECT id_barang, kode_barang, nama_barang, stok, harga FROM barang ORDER BY id_barang DESC";
        $result = $conn->query($query);
        $barang = [];
        while ($row = $result->fetch_assoc()) {
            $barang[] = $row;
        }
        echo json_encode(["status" => true, "data" => $barang]);
        break;

    // 2. POST: Tambah Barang Baru
    case 'POST':
        $kode   = $data['kode_barang'] ?? null;
        $nama   = $data['nama_barang'] ?? null;
        $stok   = $data['stok'] ?? 0;
        $harga  = $data['harga'] ?? 0;

        if (!$kode || !$nama) {
            echo json_encode(["status" => false, "message" => "Kode dan Nama wajib diisi"]);
            exit;
        }

        $stmt = $conn->prepare("INSERT INTO barang (kode_barang, nama_barang, stok, harga) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssii", $kode, $nama, $stok, $harga);

        if ($stmt->execute()) {
            echo json_encode(["status" => true, "message" => "Barang berhasil ditambahkan"]);
        } else {
            echo json_encode(["status" => false, "message" => "Gagal: " . $conn->error]);
        }
        break;

    // 3. PATCH: Update Stok Saja (Berdasarkan kode_barang)

   // 3. PATCH: Update Stok Saja
    case 'PATCH':
        $kode = $data['kode_barang'] ?? null;
        $tambah = (int)($data['stok_tambah'] ?? 0); // Paksa jadi integer

        if (!$kode) {
            echo json_encode(["status" => false, "message" => "Kode barang tidak diterima oleh server"]);
            exit;
        }

        // Kita gunakan TRIM pada kolom database untuk membuang spasi hantu
        $query = "UPDATE barang SET stok = stok + ? WHERE TRIM(kode_barang) = TRIM(?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("is", $tambah, $kode);
        
        $stmt->execute();

        // CEK APAKAH ADA BARIS YANG BERHASIL DIUPDATE
        if ($stmt->affected_rows > 0) {
            echo json_encode(["status" => true, "message" => "Stok berhasil diperbarui"]);
        } else {
            // Jika gagal, kita beri info kode apa yang tadi dicari
            echo json_encode([
                "status" => false, 
                "message" => "Kode [$kode] tidak ditemukan. Pastikan kode di DB tidak mengandung spasi atau karakter aneh."
            ]);
        }
        break;

    // 4. DELETE: Hapus Barang (Berdasarkan id_barang)
    case 'DELETE':
        $id = $data['id'] ?? null; // Dikirim dari Flutter: body: jsonEncode({"id": id})
        if (!$id) {
            echo json_encode(["status" => false, "message" => "ID wajib diisi untuk menghapus"]);
            exit;
        }
        $stmt = $conn->prepare("DELETE FROM barang WHERE id_barang = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            echo json_encode(["status" => true, "message" => "Barang berhasil dihapus"]);
        } else {
            echo json_encode(["status" => false, "message" => "Gagal menghapus barang"]);
        }
        break;

    default:
        echo json_encode(["status" => false, "message" => "Method tidak dikenali"]);
        break;
}
