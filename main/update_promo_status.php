<?php
// update_promo_status.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

require_once 'koneksi.php'; // Pastikan file koneksi database Anda di-include

header('Content-Type: application/json'); // Respond with JSON

$response = ['success' => false, 'message' => '']; // Selalu inisialisasi 'message'

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $is_promo = isset($_POST['is_promo']) ? (int)$_POST['is_promo'] : 0; // 0 or 1

    if ($id > 0) {
        // Cek apakah koneksi database ada dan aktif
        if ($conn->connect_error) {
            $response['message'] = 'Koneksi database gagal: ' . $conn->connect_error;
        } else {
            $stmt = $conn->prepare("UPDATE landing_produk SET is_promo = ? WHERE id = ?");
            if ($stmt === false) {
                // Error saat prepare statement
                $response['message'] = 'Gagal mempersiapkan statement: ' . $conn->error;
            } else {
                $stmt->bind_param("ii", $is_promo, $id);

                if ($stmt->execute()) {
                    $response['success'] = true;
                    $response['message'] = 'Status promo berhasil diperbarui.';
                } else {
                    // Error saat execute statement
                    $response['message'] = 'Gagal mengeksekusi query: ' . $stmt->error;
                }
                $stmt->close();
            }
        }
    } else {
        $response['message'] = 'ID produk tidak valid.';
    }
} else {
    $response['message'] = 'Metode request tidak diizinkan.';
}

echo json_encode($response);
$conn->close();
?>