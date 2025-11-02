<?php
// Pastikan TIDAK ADA SPASI, BARIS KOSONG, atau KARAKTER APAPUN sebelum tag pembuka <?php

// Sertakan file koneksi database Anda
include 'koneksi.php'; // Sesuaikan path jika berbeda (misal '../koneksi.php' jika di pages/)

// Pastikan ini adalah GET request dan parameter 'id' ada
if (isset($_GET['id'])) {
    // Ambil ID barang dari GET data
    $barang_id = mysqli_real_escape_string($conn, $_GET['id']); // Sesuaikan tipe data, jika barang_id INTEGER gunakan intval()

    // Validasi ID (opsional tapi disarankan)
    if (empty($barang_id)) {
        echo "<script>alert('ID Barang tidak valid.'); window.location.href='pages/barang.php';</script>";
        exit;
    }

    // Gunakan prepared statement untuk menghapus data barang
    $stmt = $conn->prepare("DELETE FROM barang WHERE barang_id = ?");
    if ($stmt) {
        // 's' jika barang_id adalah string (VARCHAR), 'i' jika integer
        $stmt->bind_param("s", $barang_id); 

        if ($stmt->execute()) {
            // Periksa apakah ada baris yang terpengaruh
            if ($stmt->affected_rows > 0) {
                echo "<script>alert('Barang berhasil dihapus.'); window.location.href='pages/barang.php';</script>";
            } else {
                echo "<script>alert('Barang tidak ditemukan atau sudah dihapus.'); window.location.href='pages/barang.php';</script>";
            }
        } else {
            echo "<script>alert('Gagal menghapus barang: " . $stmt->error . "'); window.location.href='pages/barang.php';</script>";
        }
        $stmt->close();
    } else {
        echo "<script>alert('Terjadi kesalahan internal saat menyiapkan query: " . $conn->error . "'); window.location.href='pages/barang.php';</script>";
    }

    $conn->close();
    exit;
} else {
    echo "<script>alert('Permintaan tidak valid untuk menghapus barang.'); window.location.href='pages/barang.php';</script>";
    exit;
}
?>