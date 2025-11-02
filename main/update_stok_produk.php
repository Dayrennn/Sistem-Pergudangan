<?php
session_start();
include 'koneksi.php';


// Cek login dan role
if (!isset($_SESSION['username']) || !isset($_SESSION['role'])) {
    echo "<script>alert('Anda harus login terlebih dahulu.'); window.location.href='login.php';</script>";
    exit();
}

// Role yang diizinkan
$allowed_roles = ['staff_gudang', 'kepala_gudang', 'direktur', 'admin'];
if (!in_array($_SESSION['role'], $allowed_roles)) {
    echo "<script>alert('Anda tidak memiliki izin.'); window.location.href='dashboard.php';</script>";
    exit();
}

// Tentukan halaman redirect berdasarkan role
$redirect = match ($_SESSION['role']) {
    'admin' => '../pages/barang.php',
    'staff_gudang' => '../pages/barang-staff-gudang.php',
    'kepala_gudang' => '../pages/barang-kepala-gudang.php',
    'direktur' => '../pages/barang-direktur.php',
    default => '../index.html'
};

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['id_produk_jadi']) && isset($_POST['stok'])) {
        $id_produk_jadi = intval($_POST['id_produk_jadi']);
        $stok_baru = intval($_POST['stok']);

        if ($stok_baru < 0) {
            echo "<script>alert('Stok produk tidak boleh negatif.'); window.location.href='$redirect';</script>";
            exit();
        }

        $stmt = $conn->prepare("UPDATE produk_jadi SET stok = ? WHERE id_produk_jadi = ?");
        if ($stmt) {
            $stmt->bind_param("ii", $stok_baru, $id_produk_jadi);
            if ($stmt->execute()) {
                echo "<script>alert('Stok produk berhasil diperbarui!'); window.location.href='$redirect';</script>";
            } else {
                echo "<script>alert('Gagal memperbarui stok produk: " . $stmt->error . "'); window.location.href='$redirect';</script>";
            }
            $stmt->close();
        } else {
            echo "<script>alert('Kesalahan query: " . $conn->error . "'); window.location.href='$redirect';</script>";
        }
    } else {
        echo "<script>alert('Input tidak lengkap.'); window.location.href='$redirect';</script>";
    }
} else {
    echo "<script>alert('Akses tidak sah.'); window.location.href='$redirect';</script>";
}

$conn->close();
?>
