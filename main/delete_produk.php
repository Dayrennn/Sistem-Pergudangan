<?php
include 'koneksi.php';

if (isset($_GET['id_produk_jadi'])) {
    $id_produk_jadi = intval($_GET['id_produk_jadi']);

    // Query untuk menghapus produk
    $query = "DELETE FROM produk_jadi WHERE id_produk_jadi = $id_produk_jadi";
    $result = mysqli_query($conn, $query);

    if ($result) {
        // Perbaiki path redirect dari 'pages/barang.php.php' menjadi 'pages/barang.php'
        header("Location: pages/barang.php"); // Kembali ke halaman data produk
        exit;
    } else {
        echo "Error deleting record: " . mysqli_error($conn);
    }
} else {
    echo "Invalid request.";
}
?>