<?php
include '../main/koneksi.php';
session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id_pembelian'] ?? null;
    $supplier_id = $_POST['supplier_id'] ?? null;
    $barang_supplier = $_POST['barang_id'] ?? null;
    $jumlah = $_POST['jumlah'] ?? null;
    $tanggal = $_POST['tanggal'] ?? null;
    $status = $_POST['status'] ?? null;

    if (!$id || !$supplier_id || !$barang_supplier || !$jumlah || !$tanggal || !$status) {
        echo json_encode([
            'success' => false,
            'message' => 'Form belum lengkap'
        ]);
        exit;
    }

    // Validasi data
    $check_barang = mysqli_query($conn, "SELECT * FROM supplier WHERE supplier_id = '$supplier_id' AND barang_supplier LIKE '%$barang_supplier%'");
    if (!$check_barang || mysqli_num_rows($check_barang) === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Barang tidak sesuai dengan supplier yang dipilih'
        ]);
        exit;
    }

    // Update data
    $stmt = $conn->prepare("UPDATE penjadwalan_pembelian SET 
        supplier_id = ?, 
        barang_supplier = ?, 
        jumlah = ?, 
        tanggal = ?, 
        status = ? 
        WHERE id_pembelian = ?");
    
    if (!$stmt) {
        echo json_encode([
            'success' => false,
            'message' => 'Prepare statement gagal: ' . $conn->error
        ]);
        exit;
    }

    $stmt->bind_param("issssi", $supplier_id, $barang_supplier, $jumlah, $tanggal, $status, $id);

    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Data berhasil diupdate'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Gagal mengupdate data: ' . $stmt->error
        ]);
    }
    
    $stmt->close();
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Akses tidak sah'
    ]);
}

$conn->close();
?>