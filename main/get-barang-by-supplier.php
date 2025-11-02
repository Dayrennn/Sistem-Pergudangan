<?php
include 'koneksi.php';
header('Content-Type: application/json');

// Cek koneksi database
if ($conn->connect_error) {
    die(json_encode(['error' => 'Koneksi database gagal: ' . $conn->connect_error]));
}

// Validasi parameter
if (!isset($_GET['supplier_id']) || empty($_GET['supplier_id'])) {
    echo json_encode(['error' => 'Parameter supplier_id diperlukan']);
    exit;
}

$supplier_id = intval($_GET['supplier_id']);

// Query untuk mendapatkan data barang dari tabel supplier
$query = $conn->query("SELECT barang_supplier FROM supplier WHERE supplier_id = $supplier_id");

if (!$query) {
    echo json_encode(['error' => 'Query gagal: ' . $conn->error]);
    exit;
}

if ($query->num_rows === 0) {
    echo json_encode(['error' => 'Supplier tidak ditemukan']);
    exit;
}

$row = $query->fetch_assoc();
$barangList = explode(',', $row['barang_supplier']);

// Format response
$response = array_map(function($item) {
    return [
        'id' => trim($item),
        'nama' => trim($item)
    ];
}, $barangList);

echo json_encode($response);
$conn->close();
?>