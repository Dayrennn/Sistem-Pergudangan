<?php
// Pastikan tidak ada spasi atau karakter lain sebelum tag pembuka <?php

// Sertakan file koneksi database Anda
include 'koneksi.php'; // Sesuaikan path jika file ini berada di direktori yang sama dengan koneksi.php
                       // Jika koneksi.php ada di direktori induk, gunakan '../koneksi.php'

// Set header Content-Type agar browser tahu ini adalah JSON (opsional, jika Anda ingin merespons dengan JSON)
// header('Content-Type: application/json'); // Hapus baris ini jika Anda langsung redirect

// Pastikan hanya metode POST yang diterima
if ($_SERVER['REQUEST_METHOD'] === 'POST') {   
         error_log("--- update_permintaan.php Debug ---");
    error_log("Raw POST data: " . print_r($_POST, true));
    // Ambil ID dan status dari POST data
    $id_permintaan = intval($_POST['id'] ?? 0);
    error_log("ID setelah intval: " . $id_permintaan);
    $status_baru = $_POST['status'] ?? '';
    $redirect_to = $_POST['redirect_to'] ?? 'index.php'; // Ambil halaman redirect dari form

    // Validasi input
    if ($id_permintaan <= 0) {
        echo "<script>alert('ID Permintaan tidak valid.'); window.location.href='" . htmlspecialchars($redirect_to) . "';</script>";
        exit;
    }

    // List status yang diizinkan untuk mencegah input yang tidak valid
    $allowed_statuses = ['pending', 'disetujui', 'ditolak'];
    if (!in_array($status_baru, $allowed_statuses)) {
        echo "<script>alert('Status tidak valid.'); window.location.href='" . htmlspecialchars($redirect_to) . "';</script>";
        exit;
    }

    // Gunakan prepared statement untuk mengupdate data
    $stmt_update = $conn->prepare("UPDATE permintaan_pembelian SET status = ? WHERE id = ?");
    if ($stmt_update) {
        $stmt_update->bind_param("si", $status_baru, $id_permintaan); // 's' untuk string, 'i' untuk integer

        if ($stmt_update->execute()) {
            if ($stmt_update->affected_rows > 0) {
                echo "<script>alert('Status permintaan berhasil diubah.');</script>";
            } else {
                echo "<script>alert('Status tidak berubah atau permintaan tidak ditemukan.');</script>";
            }
        } else {
            echo "<script>alert('Gagal mengubah status: " . $stmt_update->error . "');</script>";
        }
        $stmt_update->close();
    } else {
        echo "<script>alert('Terjadi kesalahan internal saat menyiapkan query update: " . $conn->error . "');</script>";
    }

    $conn->close();
    // Redirect kembali ke halaman sebelumnya
    echo "<script>window.location.href='" . htmlspecialchars($redirect_to) . "';</script>";
    exit;

} else {
    // Jika bukan POST request (misal, diakses langsung via URL)
    echo "<script>alert('Metode request tidak diizinkan.'); window.location.href='index.php';</script>";
    exit;
}

// Tidak ada tag penutup PHP ?> jika file hanya berisi kode PHP.