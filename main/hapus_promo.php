<?php
// hapus_promo.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

require_once 'koneksi.php'; // Pastikan file koneksi database Anda di-include

header('Content-Type: application/json'); // Beri tahu browser bahwa responsnya adalah JSON

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

    if ($id > 0) {
        // 1. Ambil nama file gambar dari database sebelum menghapus record
        $stmt_select = $conn->prepare("SELECT gambar FROM landing_produk WHERE id = ?");
        $stmt_select->bind_param("i", $id);
        $stmt_select->execute();
        $stmt_select->bind_result($gambar_produk);
        $stmt_select->fetch();
        $stmt_select->close();

        // 2. Hapus record dari database
        $stmt_delete = $conn->prepare("DELETE FROM landing_produk WHERE id = ?");
        $stmt_delete->bind_param("i", $id);

        if ($stmt_delete->execute()) {
            // 3. Jika penghapusan dari database berhasil, hapus file gambar fisik
            if ($gambar_produk && $gambar_produk != 'default.jpg' && !empty($gambar_produk)) { // Tambahkan kondisi agar tidak menghapus default.jpg atau jika kolom kosong
                $file_path = __DIR__ . "/uploads/" . $gambar_produk; // Sesuaikan path ini jika folder uploads bukan di lokasi yang sama
                
                // Debugging: Log path file yang akan dihapus
                // error_log("Attempting to delete file: " . $file_path);

                if (file_exists($file_path)) {
                    if (unlink($file_path)) {
                        $response['success'] = true;
                        $response['message'] = 'Produk dan gambar berhasil dihapus!';
                    } else {
                        $response['success'] = true; // Tetap sukses karena record DB sudah terhapus
                        $response['message'] = 'Produk berhasil dihapus, tetapi gagal menghapus file gambar: Tidak ada izin atau file terkunci.';
                        // error_log("Failed to unlink file: " . $file_path);
                    }
                } else {
                    $response['success'] = true; // Tetap sukses karena record DB sudah terhapus
                    $response['message'] = 'Produk berhasil dihapus, tetapi file gambar tidak ditemukan di server.';
                }
            } else {
                $response['success'] = true; // Tetap sukses jika tidak ada gambar atau gambar default
                $response['message'] = 'Produk berhasil dihapus (tidak ada gambar terkait atau gambar default).';
            }
        } else {
            $response['message'] = 'Gagal menghapus produk dari database: ' . $stmt_delete->error;
        }
        $stmt_delete->close();
    } else {
        $response['message'] = 'ID produk tidak valid.';
    }
} else {
    $response['message'] = 'Metode request tidak diizinkan.';
}

echo json_encode($response);
$conn->close();
?>