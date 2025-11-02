<?php
session_start();
require_once 'koneksi.php'; // Pastikan koneksi.php sudah benar

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

if (isset($_POST['tambah'])) {
    $nama_produk = $_POST['nama']; // Mengambil dari name="nama" di form
    $harga = (int)$_POST['harga'];
    $diskon = (int)$_POST['diskon'];

    $nama_gambar_untuk_db = ''; // Default kosong
    $ext_gambar = pathinfo($nama_gambar_asli, PATHINFO_EXTENSION);
        $nama_gambar_unik = uniqid() . '.' . $ext_gambar; // <-- Ini HARUS ADA!
        $upload_path = 'uploads/' . $nama_gambar_unik;

        if (move_uploaded_file($tmp_name, $upload_path)) {
            $nama_gambar_untuk_db = $nama_gambar_unik; // Pastikan ini yang disimpan
        }

    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK && !empty($_FILES['gambar']['name'])) {
        $nama_gambar_asli = $_FILES['gambar']['name'];
        $tmp_name = $_FILES['gambar']['tmp_name'];

        $ext_gambar = pathinfo($nama_gambar_asli, PATHINFO_EXTENSION);
        $nama_gambar_unik = uniqid() . '.' . $ext_gambar; // KRITIS: PASTIKAN .EKSTENSI ADA
        $upload_path = 'uploads/' . $nama_gambar_unik;

        if (move_uploaded_file($tmp_name, $upload_path)) {
            $nama_gambar_untuk_db = $nama_gambar_unik;
        } else {
            $_SESSION['pesan_error'] = "Gagal memindahkan file gambar saat tambah produk.";
            header("Location: index-kepala-marketing.php");
            exit;
        }
    } else if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] !== UPLOAD_ERR_NO_FILE && $_FILES['gambar']['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['pesan_error'] = "Terjadi error upload gambar saat tambah produk: " . $_FILES['gambar']['error'];
        header("Location: index-kepala-marketing.php");
        exit;
    }

    $query = "INSERT INTO landing_produk (nama_produk, harga, diskon, gambar) VALUES (?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $query);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "siis", $nama_produk, $harga, $diskon, $nama_gambar_untuk_db);
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['pesan_sukses'] = "Produk berhasil ditambahkan.";
        } else {
            $_SESSION['pesan_error'] = "Error database saat menambahkan produk: " . mysqli_error($conn);
        }
        mysqli_stmt_close($stmt);
    } else {
        $_SESSION['pesan_error'] = "Error mempersiapkan statement saat menambah produk: " . mysqli_error($conn);
    }
    mysqli_close($conn);
    header("Location: index-kepala-marketing.php");
    exit;
} else {
    header("Location: index-kepala-marketing.php");
    exit;
}
?>