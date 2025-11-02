<?php
include 'koneksi.php';

$id = $_POST['id_pembelian'];
$supplier = $_POST['supplier_id'];
$barang = $_POST['barang_id'];
$jumlah = $_POST['jumlah'];
$tanggal = $_POST['tanggal'];
$status = $_POST['status'];

$query = "INSERT INTO penjadwalan_pembelian (id_pembelian, supplier_id, barang_id, jumlah, tanggal, status) 
          VALUES ('$id', '$supplier', '$barang', '$jumlah', '$tanggal', '$status')";

if (mysqli_query($conn, $query)) {
    header("Location:../pages/penjadwalan-pembelian.php");
} else {
    echo "Gagal menambahkan jadwal: " . mysqli_error($conn);
}
?>
