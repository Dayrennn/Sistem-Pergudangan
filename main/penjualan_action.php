<?php
session_start();
header('Content-Type: application/json');
include 'koneksi.php';

file_put_contents("session_debug.txt", print_r($_SESSION, true));


// Cek apakah user sudah login
if (!isset($_SESSION['username'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Anda harus login terlebih dahulu.'
    ]);
    exit;
}

// Tangani hanya request POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';

    if (empty($_POST['pesanan_id']) || empty($_POST['status'])) {
    echo json_encode(['success' => false, 'message' => 'Data tidak lengkap']);
    exit;
}


    // ========== Aksi: Update Status ==========
    if ($aksi === 'update_status') {
        $id_terjual = $_POST['id_terjual'] ?? null;
        $status_baru = $_POST['status'] ?? null;

        if (!$id_terjual || !$status_baru) {
            echo json_encode(['success' => false, 'message' => 'Data tidak lengkap']);
            exit;
        }

        // Sanitasi input
        $id_terjual = mysqli_real_escape_string($conn, $id_terjual);
        $status_baru = mysqli_real_escape_string($conn, $status_baru);

        $query = "UPDATE produk_terjual SET status = '$status_baru' WHERE id_terjual = '$id_terjual'";

        if (mysqli_query($conn, $query)) {
            echo json_encode(['success' => true, 'message' => 'Status berhasil diupdate']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal update: ' . mysqli_error($conn)]);
        }
        exit;
    }

    // ========== Tambah aksi lainnya jika perlu ==========
    // Contoh: tambah penjualan, hapus penjualan, dll.

    echo json_encode(['success' => false, 'message' => 'Aksi tidak dikenali']);
    exit;
} else {
    echo json_encode(['success' => false, 'message' => 'Metode request tidak didukung']);
    exit;
}

if ($aksi === 'update_status') {
    $pesanan_id = $_POST['pesanan_id'] ?? null;
    $status_baru = $_POST['status'] ?? null;

    if (!$pesanan_id || !$status_baru) {
        echo json_encode(['success' => false, 'message' => 'Data tidak lengkap']);
        exit;
    }

    $pesanan_id = mysqli_real_escape_string($conn, $pesanan_id);
    $status_baru = mysqli_real_escape_string($conn, $status_baru);

    // Cek apakah sudah ada data produk_terjual dengan pesanan_id ini
    $cek = mysqli_query($conn, "SELECT id_terjual FROM produk_terjual WHERE pesanan_id = '$pesanan_id'");
    
    if (mysqli_num_rows($cek) > 0) {
        $row = mysqli_fetch_assoc($cek);
        $id_terjual = $row['id_terjual'];

        // Update status
        $update = mysqli_query($conn, "UPDATE produk_terjual SET status = '$status_baru' WHERE id_terjual = '$id_terjual'");

        if ($update) {
            echo json_encode(['success' => true, 'message' => 'Status berhasil diupdate']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal update status: ' . mysqli_error($conn)]);
        }

    } else {
        // Belum ada data produk_terjual, insert dulu
        // Ambil data pelanggan_id dan harga dari pesanan (kamu perlu sesuaikan query ini)
        $detail_pesanan = mysqli_query($conn, "SELECT pelanggan_id, barang_id, jumlah_pesan FROM pesanan WHERE pesanan_id = '$pesanan_id'");
        if (mysqli_num_rows($detail_pesanan) === 0) {
            echo json_encode(['success' => false, 'message' => 'Pesanan tidak ditemukan']);
            exit;
        }
        $pesanan_data = mysqli_fetch_assoc($detail_pesanan);

        // Ambil harga produk_jadi
        $barang_id = $pesanan_data['barang_id'];
        $harga_result = mysqli_query($conn, "SELECT harga_jual FROM produk_jadi WHERE id_produk_jadi = '$barang_id'");
        if (mysqli_num_rows($harga_result) === 0) {
            echo json_encode(['success' => false, 'message' => 'Produk tidak ditemukan']);
            exit;
        }
        $harga_data = mysqli_fetch_assoc($harga_result);
        $harga = $harga_data['harga_jual'];

        $pelanggan_id = $pesanan_data['pelanggan_id'];
        $jumlah_terjual = $pesanan_data['jumlah_pesan'];
        $tanggal = date('Y-m-d');
        
        // Insert produk_terjual baru
        $insert = mysqli_query($conn, "INSERT INTO produk_terjual 
            (id_produk_jadi, jumlah_terjual, tanggal_terjual, pelanggan_id, harga, status, pesanan_id) VALUES 
            ('$barang_id', '$jumlah_terjual', '$tanggal', '$pelanggan_id', '$harga', '$status_baru', '$pesanan_id')");

        if ($insert) {
            echo json_encode(['success' => true, 'message' => 'Data produk terjual berhasil dibuat dan status diupdate']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal membuat data produk terjual: ' . mysqli_error($conn)]);
        }
    }

    exit;
}

?>
