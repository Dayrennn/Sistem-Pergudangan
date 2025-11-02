<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("HTTP/1.1 401 Unauthorized");
    exit;
}

require_once '../koneksi.php'; // Sesuaikan path

$data = array();

// Ambil jumlah data barang
$query_barang = mysqli_query($conn, "SELECT COUNT(*) as total FROM barang") or die(mysqli_error($conn));
$data_barang = mysqli_fetch_assoc($query_barang);
$data['barang'] = $data_barang ? $data_barang['total'] : 0;

// Ambil jumlah data penjualan dari pesanan
$query_penjualan = mysqli_query($conn, "SELECT COUNT(*) as total FROM pesanan") or die(mysqli_error($conn));
$data_penjualan = mysqli_fetch_assoc($query_penjualan);
$data['penjualan'] = $data_penjualan ? $data_penjualan['total'] : 0;

// Ambil jumlah data supplier
$query_supplier = mysqli_query($conn, "SELECT COUNT(*) as total FROM supplier") or die(mysqli_error($conn));
$data_supplier = mysqli_fetch_assoc($query_supplier);
$data['supplier'] = $data_supplier ? $data_supplier['total'] : 0;

header('Content-Type: application/json');
echo json_encode($data);
?>