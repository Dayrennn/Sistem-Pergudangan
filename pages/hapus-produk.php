<?php
session_start();
include '../main/koneksi.php'; // Sesuaikan path jika berbeda

// Daftar role yang diizinkan dan halaman tujuan masing-masing
$redirect_pages = [
    'admin' => 'barang.php',
    'direktur' => 'barang-direktur.php',
    'kepala_gudang' => 'barang-kepala-gudang.php',
    'staff_gudang' => 'barang-staff-gudang.php',
];

// Cek role login
if (!isset($_SESSION['role']) || !array_key_exists($_SESSION['role'], $redirect_pages)) {
    echo "<script>alert('Akses ditolak.'); window.location.href='../login.php';</script>";
    exit;
}

// Ambil halaman redirect sesuai role
$redirect_page = $redirect_pages[$_SESSION['role']];

// Validasi ID dari parameter GET
if (isset($_GET['id'])) {
    $id_produk_jadi = intval($_GET['id']);
    if ($id_produk_jadi <= 0) {
        echo "<script>alert('ID Produk Jadi tidak valid.'); window.location.href='$redirect_page';</script>";
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM produk_jadi WHERE id_produk_jadi = ?");
    if ($stmt) {
        $stmt->bind_param("i", $id_produk_jadi);
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                echo "<script>alert('Produk berhasil dihapus.'); window.location.href='$redirect_page';</script>";
            } else {
                echo "<script>alert('Produk tidak ditemukan atau sudah dihapus.'); window.location.href='$redirect_page';</script>";
            }
        } else {
            echo "<script>alert('Gagal menghapus produk: " . $stmt->error . "'); window.location.href='$redirect_page';</script>";
        }
        $stmt->close();
    } else {
        echo "<script>alert('Kesalahan internal: " . $conn->error . "'); window.location.href='$redirect_page';</script>";
    }

    $conn->close();
    exit;
} else {
    echo "<script>alert('Parameter ID tidak ditemukan.'); window.location.href='$redirect_page';</script>";
    exit;
}
