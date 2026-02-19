<?php
header("Content-Type: application/json");
require_once "../config/database.php";

$response = [
    "status" => false,
    "data" => []
];

$query = "
    SELECT 
        t.id_transaksi,
        t.id_barang,
        t.kode_barang,
        b.nama_barang,
        t.nama_pengambil,
        t.jumlah,
        t.waktu_transaksi
    FROM transaksi t
    JOIN barang b ON t.id_barang = b.id_barang
    ORDER BY t.waktu_transaksi DESC
";

$result = $conn->query($query);

if ($result) {
    $data = [];

    while ($row = $result->fetch_assoc()) {
        $data[] = [
            "id_transaksi"   => $row['id_transaksi'],
            "id_barang"      => $row['id_barang'],
            "kode_barang"    => $row['kode_barang'],
            "nama_barang"    => $row['nama_barang'],
            "nama_pengambil" => $row['nama_pengambil'],
            "jumlah"         => $row['jumlah'],
            "tanggal"        => date("d-m-Y H:i", strtotime($row['waktu_transaksi']))
        ];
    }

    $response["status"] = true;
    $response["data"] = $data;
}

echo json_encode($response);
