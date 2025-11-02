<?php
include '../koneksi.php'; // Sesuaikan path

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    echo "<pre>";  // Untuk format var_dump
    var_dump($_POST);  // DEBUG: Lihat apa yang diterima
    echo "</pre>";

    $pelanggan_id = intval($_POST['pelanggan_id']);
    $nama_pelanggan = mysqli_real_escape_string($conn, $_POST['nama_pelanggan']);  // SESUAIKAN DENGAN NAMA INPUT
    $email = mysqli_real_escape_string($conn, $_POST['email']);  // SESUAIKAN DENGAN NAMA INPUT
    $kontak = mysqli_real_escape_string($conn, $_POST['kontak']);  // SESUAIKAN DENGAN NAMA INPUT
    $alamat = mysqli_real_escape_string($conn, $_POST['alamat']);  // SESUAIKAN DENGAN NAMA INPUT

    $query = "UPDATE pelanggan SET 
              nama_pelanggan = ?, 
              email = ?, 
              kontak = ?, 
              alamat = ? 
              WHERE pelanggan_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ssssi", $nama_pelanggan, $email, $kontak, $alamat, $pelanggan_id);  // SESUAIKAN DENGAN VARIABEL DI ATAS

    if (mysqli_stmt_execute($stmt)) {
        // Redirect atau tampilkan pesan sukses
        header("Location: pelanggan.php"); // Kembali ke halaman pelanggan
        exit;
    } else {
        echo "Gagal memperbarui data pelanggan: " . mysqli_error($conn);
    }
} else {
    // Jika bukan metode POST
    echo "Metode request tidak valid.";
}
?>