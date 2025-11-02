<?php
// Pastikan tidak ada spasi atau karakter lain sebelum baris ini
include '../main/koneksi.php';
header('Content-Type: application/json');

// Pastikan hanya metode POST yang diterima
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Metode request tidak valid'
    ]);
    exit;
}

// Ambil dan validasi ID Pembelian
$id_pembelian = isset($_POST['id_pembelian']) ? intval($_POST['id_pembelian']) : null;

if (!$id_pembelian || $id_pembelian <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'ID Pembelian tidak valid'
    ]);
    exit;
}

// Gunakan prepared statement untuk keamanan
$sql = "DELETE FROM penjadwalan_pembelian WHERE id_pembelian = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode([
        'success' => false,
        'message' => 'Prepare statement gagal: ' . $conn->error
    ]);
    exit;
}

$stmt->bind_param("i", $id_pembelian);

if ($stmt->execute()) {
    // Periksa apakah ada baris yang terpengaruh
    if ($stmt->affected_rows > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Data berhasil dihapus'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Data tidak ditemukan'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Gagal menghapus data: ' . $stmt->error
    ]);
}

$stmt->close();
$conn->close();
// TIDAK ADA TAG PENUTUP PHP DI SINI