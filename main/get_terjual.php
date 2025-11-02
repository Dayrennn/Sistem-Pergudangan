<?php
require_once 'koneksi.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    
    $query = "SELECT pt.*, p.alamat 
              FROM produk_terjual pt
              JOIN pelanggan p ON pt.pelanggan_id = p.pelanggan_id
              WHERE pt.id_terjual = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    
    header('Content-Type: application/json');
    echo json_encode($data);
}
?>