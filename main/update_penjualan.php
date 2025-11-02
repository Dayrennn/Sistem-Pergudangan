<?php
// update_penjualan.php

header('Content-Type: application/json');
session_start();

// Contoh pengecekan session (ubah sesuai kebutuhan)
if (!isset($_SESSION['user'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Anda harus login terlebih dahulu.'
    ]);
    exit;
}

// Koneksi database (sesuaikan konfigurasi dengan server Anda)
$host = "localhost";
$username = "root";
$password = "";
$dbname = "nama_database";

$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    echo json_encode([
        'success' => false,
        'message' => 'Koneksi database gagal: ' . $conn->connect_error
    ]);
    exit;
}

// Ambil data dari POST
$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$status = isset($_POST['status']) ? $_POST['status'] : '';

// Validasi data
if ($id <= 0 || empty($status)) {
    echo json_encode([
        'success' => false,
        'message' => 'Data tidak lengkap atau tidak valid.'
    ]);
    exit;
}

// Sanitasi input (misal status hanya boleh tertentu)
$allowed_status = ['pending', 'lunas', 'batal', 'proses'];
if (!in_array($status, $allowed_status)) {
    echo json_encode([
        'success' => false,
        'message' => 'Status tidak valid.'
    ]);
    exit;
}

// Query update status penjualan (sesuaikan nama tabel dan kolom)
$sql = "UPDATE penjualan SET status = ? WHERE id = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode([
        'success' => false,
        'message' => 'Prepare statement gagal: ' . $conn->error
    ]);
    exit;
}

$stmt->bind_param("si", $status, $id);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Status penjualan berhasil diperbarui.'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Gagal memperbarui status: ' . $stmt->error
    ]);
}

$stmt->close();
$conn->close();
