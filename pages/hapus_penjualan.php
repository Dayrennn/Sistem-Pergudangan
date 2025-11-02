<?php
// hapus_penjualan.php

include '../koneksi.php'; // Pastikan path ini benar ke file koneksi database Anda
session_start();

// Fungsi pembantu untuk respons JSON (diambil dari penjualan.php)
function sendJson($success, $message, $data = []) {
    header('Content-Type: application/json');
    echo json_encode(['success' => $success, 'message' => $message, 'data' => $data]);
    exit; // Penting untuk menghentikan eksekusi setelah mengirim JSON
}

$id = $_GET['id'] ?? null; // Anda mengambil ID dari GET

if ($id) {
    $stmt = $conn->prepare("DELETE FROM penjadwalan_pembelian WHERE id_pembelian = ?"); // Tabel Anda
    if ($stmt) {
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                // Kirim respons JSON sukses
                sendJson(true, "Data penjadwalan pembelian berhasil dihapus.");
            } else {
                // Kirim respons JSON jika data tidak ditemukan
                sendJson(false, "Data penjadwalan pembelian tidak ditemukan atau sudah dihapus.");
            }
        } else {
            // Kirim respons JSON jika eksekusi query gagal
            sendJson(false, "Gagal menghapus data: " . $stmt->error);
        }
        $stmt->close();
    } else {
        // Kirim respons JSON jika persiapan query gagal
        sendJson(false, "Terjadi kesalahan saat menyiapkan query: " . $conn->error);
    }
} else {
    // Kirim respons JSON jika ID tidak valid
    sendJson(false, "ID tidak valid.");
}

$conn->close();
?>