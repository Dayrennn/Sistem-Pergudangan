<?php
include '../koneksi.php';

if (isset($_POST['simpan_permintaan'])) {
    $supplier_id = $_POST['supplier_id'];
    $nama_barang = $_POST['nama_barang'];
    $jumlah = $_POST['jumlah'];
    $tanggal = date('Y-m-d');

    // Simpan permintaan
    mysqli_query($conn, "INSERT INTO daftar_permintaan (supplier_id, nama_barang, jumlah, tanggal_permintaan) VALUES ('$supplier_id', '$nama_barang', '$jumlah', '$tanggal')");

    // Dapatkan ID permintaan
    $id_permintaan = mysqli_insert_id($conn);

    // Simpan ke daftar pembelian dengan status pending
    mysqli_query($conn, "INSERT INTO daftar_pembelian (id_permintaan, tanggal_pembelian, status) VALUES ('$id_permintaan', '$tanggal', 'Pending')");

    header("Location: data-pembelian.php");
    exit;
}
