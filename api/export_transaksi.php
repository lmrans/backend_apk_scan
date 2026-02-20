<?php
require '../vendor/autoload.php';
require_once "../config/database.php";

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// ================= QUERY =================
$query = "SELECT t.id_transaksi,
                 t.kode_barang,
                 b.nama_barang,
                 b.satuan,
                 t.nama_pengambil,
                 t.jumlah,
                 t.waktu_transaksi
          FROM transaksi t
          JOIN barang b ON t.id_barang = b.id_barang
          ORDER BY t.waktu_transaksi DESC";

$result = $conn->query($query);

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// ================= HEADER =================
$sheet->setCellValue('A1', 'ID Transaksi');
$sheet->setCellValue('B1', 'Kode Barang');
$sheet->setCellValue('C1', 'Nama Barang');
$sheet->setCellValue('D1', 'Nama Pengambil');
$sheet->setCellValue('E1', 'Jumlah');
$sheet->setCellValue('F1', 'Waktu Transaksi');

// Optional: Bold header
$sheet->getStyle('A1:F1')->getFont()->setBold(true);

$row = 2;

while ($data = $result->fetch_assoc()) {

    // Gabungkan jumlah + satuan
    $jumlahDenganSatuan = $data['jumlah'] . " " . $data['satuan'];

    $sheet->setCellValue('A'.$row, $data['id_transaksi']);
    $sheet->setCellValue('B'.$row, $data['kode_barang']);
    $sheet->setCellValue('C'.$row, $data['nama_barang']);
    $sheet->setCellValue('D'.$row, $data['nama_pengambil']);
    $sheet->setCellValue('E'.$row, $jumlahDenganSatuan);
    $sheet->setCellValue('F'.$row, $data['waktu_transaksi']);

    $row++;
}

// Auto size kolom
foreach(range('A','F') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

$filename = "Laporan_Transaksi_" . date('Ymd_His') . ".xlsx";

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="'.$filename.'"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;