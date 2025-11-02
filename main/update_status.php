<?php
header('Content-Type: application/json');
include 'koneksi.php'; // sesuaikan koneksi DB kamu

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Metode tidak didukung']);
    exit;
}

$pesananId = $_POST['pesananId'] ?? null;
$status = $_POST['status'] ?? null;

if (!$pesananId || !$status) {
    echo json_encode(['error' => 'Data tidak lengkap']);
    exit;
}

// update status di tabel pesanan
$query = "UPDATE pesanan SET status = ? WHERE pesanan_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('si', $status, $pesananId);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['error' => 'Gagal update status']);
}
?>
