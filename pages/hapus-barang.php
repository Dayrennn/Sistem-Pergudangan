<?php
require_once '../main/koneksi.php';

header('Content-Type: application/json');

// Aktifkan error reporting untuk debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Logging function (sederhana ke file)
function log_message($message) {
    error_log(date("[Y-m-d H:i:s] ") . $message . "\n", 3, '../error.log');
}

log_message("Mulai hapus-barang.php");

// Validasi input
$id = $_POST['id'] ?? $_GET['id'] ?? null;
if (!$id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID barang tidak valid']);
    log_message("Error: ID barang tidak valid");
    exit;
}

// Validasi ID (lebih ketat)
if (!preg_match('/^[a-zA-Z0-9]+$/', $id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Format ID tidak valid']);
    log_message("Error: Format ID tidak valid: " . $id);
    exit;
}

try {
    // Mulai transaksi
    $conn->begin_transaction();
    log_message("Transaksi dimulai");

    // 1. Nonaktifkan sementara foreign key checks (untuk MySQL)
    $conn->query("SET FOREIGN_KEY_CHECKS = 0");
    log_message("FOREIGN_KEY_CHECKS dinonaktifkan");

    // 2. Hapus data terkait terlebih dahulu
    $tablesToClean = ['barang_keluar', 'produk_jadi', 'produk_terjual']; // Sesuaikan dengan tabel Anda
    foreach ($tablesToClean as $table) {
        // Periksa apakah tabel ada dan kolom barang_id ada
        $check_table_query = "SHOW TABLES LIKE '$table'";
        $check_table_result = $conn->query($check_table_query);
        if ($check_table_result->num_rows > 0) {
            $check_column_query = "SHOW COLUMNS FROM $table LIKE 'barang_id'";
            $check_column_result = $conn->query($check_column_query);
            if ($check_column_result->num_rows > 0) {
                $delete_query = "DELETE FROM $table WHERE barang_id = '$id'";
                if ($conn->query($delete_query) === false) {
                    throw new Exception("Gagal menghapus dari $table: " . $conn->error);
                }
                log_message("Berhasil menghapus dari $table");
            } else {
                log_message("Tabel $table tidak memiliki kolom barang_id, dilewati");
            }
        } else {
            log_message("Tabel $table tidak ditemukan, dilewati");
        }
    }

    // 3. Hapus dari tabel utama
    $stmt = $conn->prepare("DELETE FROM barang WHERE barang_id = ?");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    log_message("Menghapus dari tabel barang, affected rows: " . $stmt->affected_rows);

    if ($stmt->affected_rows > 0) {
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Barang berhasil dihapus']);
        log_message("Transaksi di-commit, barang berhasil dihapus");
    } else {
        $conn->rollback();
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Barang tidak ditemukan']);
        log_message("Transaksi di-rollback, barang tidak ditemukan");
    }

    // 4. Aktifkan kembali foreign key checks
    $conn->query("SET FOREIGN_KEY_CHECKS = 1");
    log_message("FOREIGN_KEY_CHECKS diaktifkan kembali");

} catch (Exception $e) {
    $conn->rollback();
    $conn->query("SET FOREIGN_KEY_CHECKS = 1");
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Terjadi kesalahan saat menghapus',
        'error' => $e->getMessage()
    ]);
    log_message("Error menghapus barang: " . $e->getMessage());
}

$conn->close();
log_message("Selesai hapus-barang.php");
?>