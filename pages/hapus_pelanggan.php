<?php
include '../main/koneksi.php';
session_start();
header('Content-Type: application/json'); // Pastikan ini adalah baris pertama yang dieksekusi

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus_pelanggan'])) {
    $id = intval($_POST['hapus_pelanggan']);
    
    $conn->begin_transaction();
    try {
        // 1. Hapus produk_terjual terkait
        $stmt = $conn->prepare("DELETE pt FROM produk_terjual pt 
                              JOIN pesanan p ON pt.pesanan_id = p.pesanan_id 
                              WHERE p.pelanggan_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        // 2. Hapus pesanan
        $stmt = $conn->prepare("DELETE FROM pesanan WHERE pelanggan_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        // 3. Hapus pelanggan
        $stmt = $conn->prepare("DELETE FROM pelanggan WHERE pelanggan_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        $conn->commit();
        echo json_encode(["status" => "success", "message" => "Pelanggan berhasil dihapus"]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(["status" => "error", "message" => "Gagal menghapus: " . $e->getMessage()]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Invalid request"]);
}
?>