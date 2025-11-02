<?php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "nama_database"; // Ganti dengan nama database kamu

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Inisialisasi variabel
$total_barang = 0;
$stok_tertinggi = 0;
$stok_terendah = 0;

// Ambil total barang (jumlah baris produk_jadi)
$sql_total = "SELECT COUNT(*) as total FROM produk_jadi";
$result_total = $conn->query($sql_total);
if ($result_total && $result_total->num_rows > 0) {
    $row = $result_total->fetch_assoc();
    $total_barang = (int)$row['total'];
}

// Ambil stok tertinggi
$sql_tertinggi = "SELECT stok FROM produk_jadi ORDER BY stok+0 DESC LIMIT 1";
$result_tertinggi = $conn->query($sql_tertinggi);
if ($result_tertinggi && $result_tertinggi->num_rows > 0) {
    $row = $result_tertinggi->fetch_assoc();
    $stok_tertinggi = (int)$row['stok'];
}

// Ambil stok terendah
$sql_terendah = "SELECT stok FROM produk_jadi ORDER BY stok+0 ASC LIMIT 1";
$result_terendah = $conn->query($sql_terendah);
if ($result_terendah && $result_terendah->num_rows > 0) {
    $row = $result_terendah->fetch_assoc();
    $stok_terendah = (int)$row['stok'];
}

$conn->close();
?>
