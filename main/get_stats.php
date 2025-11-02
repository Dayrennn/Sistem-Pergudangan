<?php
include 'koneksi.php';

// Stok tertinggi
$queryTertinggi = "SELECT MAX(stok) AS stok_tertinggi FROM produk_jadi";
$resultTertinggi = mysqli_query($conn, $queryTertinggi);
$rowTertinggi = mysqli_fetch_assoc($resultTertinggi);

// Stok terendah (bukan nol)
$queryTerendah = "SELECT MIN(stok) AS stok_terendah FROM produk_jadi WHERE stok > 0";
$resultTerendah = mysqli_query($conn, $queryTerendah);
$rowTerendah = mysqli_fetch_assoc($resultTerendah);

$data = [
    'stok_tertinggi' => $rowTertinggi['stok_tertinggi'] ?? 0,
    'stok_terendah' => $rowTerendah['stok_terendah'] ?? 0,
];

header('Content-Type: application/json');
echo json_encode($data);
?>
