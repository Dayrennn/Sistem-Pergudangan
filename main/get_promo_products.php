<?php
// get_promo_products.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'koneksi.php'; // Pastikan file koneksi database Anda di-include

header('Content-Type: application/json'); // Beri tahu browser bahwa responsnya adalah JSON

$response = [
    'success' => false,
    'data' => [], // Inisialisasi data sebagai array kosong
    'error' => ''
];

try {
    // Query untuk mengambil produk yang berstatus promo (is_promo = 1)
    $query = "SELECT id, nama_produk, harga, diskon, gambar, is_promo FROM landing_produk WHERE is_promo = 1";
    $result = mysqli_query($conn, $query);

    if ($result) {
        $products = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $products[] = $row;
        }
        $response['success'] = true;
        $response['data'] = $products;
    } else {
        $response['error'] = 'Gagal mengambil data produk: ' . mysqli_error($conn);
    }
} catch (Exception $e) {
    $response['error'] = 'Terjadi kesalahan server: ' . $e->getMessage();
} finally {
    // Tutup koneksi database
    if ($conn) {
        mysqli_close($conn);
    }
}

echo json_encode($response);
?>