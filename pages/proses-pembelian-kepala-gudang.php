<?php
include '../koneksi.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajukan_pembelian'])) {
    $supplier_id = $_POST['supplier_id'];
    $nama = $_POST['nama_supplier'];
    $barang = $_POST['barang'];
    $harga = $_POST['harga'];
    $jumlah = $_POST['jumlah'];
    $total_harga = $_POST['total_harga'];
    $tanggal = date('Y-m-d');

    mysqli_query($conn, "INSERT INTO permintaan_pembelian 
        (supplier_id, nama_supplier, barang, harga, jumlah, total_harga, status, tanggal_permintaan)
        VALUES ('$supplier_id', '$nama', '$barang', '$harga', '$jumlah', '$total_harga', 'Pending', '$tanggal')");
    
    header("Location: data-pembelian-kepala-gudang.php");
    exit;
}

?>
