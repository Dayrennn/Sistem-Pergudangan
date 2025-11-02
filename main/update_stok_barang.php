<?php
session_start();
include 'koneksi.php';


if (!isset($_SESSION['username']) || !isset($_SESSION['role'])) {
    echo "<script>alert('Anda harus login terlebih dahulu.'); window.location.href='login.php';</script>";
    exit();
}

$allowed_roles = ['staff_gudang', 'kepala_gudang', 'direktur', 'admin'];
if (!in_array($_SESSION['role'], $allowed_roles)) {
    echo "<script>alert('Anda tidak memiliki izin.'); window.location.href='dashboard.php';</script>";
    exit();
}

$redirect = match ($_SESSION['role']) {
    'admin' => '../pages/barang.php',
    'staff_gudang' => '../pages/barang-staff-gudang.php',
    'kepala_gudang' => '../pages/barang-kepala-gudang.php',
    'direktur' => '../pages/barang-direktur.php',
    default => '../index.html'
};

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['barang_id']) && isset($_POST['stok'])) {
    $barang_id = intval($_POST['barang_id']);
    $stok_baru = intval($_POST['stok']);

    if ($stok_baru < 0) {
        echo "<script>alert('Stok tidak boleh negatif.'); window.location.href='$redirect';</script>";
        exit();
    }

    $stmt = $conn->prepare("UPDATE barang SET stok = ? WHERE barang_id = ?");
    if ($stmt) {
        $stmt->bind_param("ii", $stok_baru, $barang_id);
        if ($stmt->execute()) {
            echo "<script>alert('Stok barang berhasil diperbarui!'); window.location.href='$redirect';</script>";
        } else {
            echo "<script>alert('Gagal memperbarui stok barang: " . $stmt->error . "'); window.location.href='$redirect';</script>";
        }
        $stmt->close();
    } else {
        echo "<script>alert('Kesalahan query: " . $conn->error . "'); window.location.href='$redirect';</script>";
    }
}

} else {
    echo "<script>alert('Akses tidak sah.'); window.location.href='$redirect';</script>";
}

$conn->close();
?>
