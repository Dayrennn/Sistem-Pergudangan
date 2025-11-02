<?php
// Mulai session di awal file untuk menggunakan $_SESSION
session_start();

// Izinkan laporan kesalahan untuk debugging (hapus atau nonaktifkan di produksi)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Detail koneksi database (sesuaikan dengan pengaturan Anda)
// Gunakan file koneksi.php jika Anda sudah memilikinya. Pastikan koneksi.php menginisialisasi $conn
require_once 'koneksi.php'; 

// Periksa koneksi
if (!$conn) { // $conn harus sudah didefinisikan di koneksi.php
    $_SESSION['message_type'] = 'error';
    $_SESSION['message'] = "Koneksi database gagal.";
    header("Location: index-kepala-marketing.php"); // Redirect kembali ke halaman admin
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama_produk = $_POST['nama_produk'] ?? '';
    $harga = $_POST['harga'] ?? 0;
    $diskon = $_POST['diskon'] ?? 0;
    $is_promo = isset($_POST['is_promo']) ? 1 : 0; // Cekbox tercentang = 1, tidak tercentang = 0

    // Validasi input dasar
    if (empty($nama_produk) || !is_numeric($harga) || !is_numeric($diskon)) {
        $_SESSION['message_type'] = 'error';
        $_SESSION['message'] = 'Data input tidak lengkap atau tidak valid.';
        header("Location: index-kepala-marketing.php");
        $conn->close();
        exit();
    }

    $gambar_name_to_db = null; // Nama file yang akan disimpan ke database
    $target_dir = "uploads/"; // Direktori tempat gambar akan diunggah (Pastikan path ini benar)

    // Pastikan direktori uploads ada dan dapat ditulis
    if (!is_dir($target_dir)) {
        if (!mkdir($target_dir, 0777, true)) { // Hati-hati dengan izin 0777 di produksi, gunakan 0755 jika memungkinkan
            $_SESSION['message_type'] = 'error';
            $_SESSION['message'] = 'Gagal membuat direktori unggahan. Pastikan izin benar.';
            header("Location: index-kepala-marketing.php");
            $conn->close();
            exit();
        }
    }

    // Penanganan unggahan gambar
    if (isset($_FILES["gambar"]) && $_FILES["gambar"]["error"] == UPLOAD_ERR_OK) {
        // Dapatkan ekstensi file dari nama asli
        $imageFileType = strtolower(pathinfo($_FILES["gambar"]["name"], PATHINFO_EXTENSION));
        
        // Buat nama file unik
        $new_file_name = uniqid('produk_', true) . '.' . $imageFileType; // Tambahkan `true` untuk entropi lebih tinggi
        $target_file = $target_dir . $new_file_name; // Path lengkap file tujuan

        $uploadOk = 1;

        // Periksa apakah file gambar asli atau palsu
        $check = getimagesize($_FILES["gambar"]["tmp_name"]);
        if($check === false) {
            $_SESSION['message_type'] = 'error';
            $_SESSION['message'] = 'File bukan gambar.';
            $uploadOk = 0;
        }

        // HAPUS BARIS INI: TIDAK PERLU CEK file_exists KARENA NAMA SUDAH UNIK
        /*
        if ($uploadOk == 1 && file_exists($target_file)) {
            $_SESSION['message_type'] = 'error';
            $_SESSION['message'] = 'Maaf, gambar dengan nama yang sama sudah ada. Harap ganti nama file atau pilih gambar lain.';
            $uploadOk = 0;
        }
        */

        // Periksa ukuran file (maksimal 5MB)
        if ($uploadOk == 1 && $_FILES["gambar"]["size"] > 5000000) {
            $_SESSION['message_type'] = 'error';
            $_SESSION['message'] = 'Maaf, ukuran gambar terlalu besar (maks 5MB).';
            $uploadOk = 0;
        }

        // Izinkan format file tertentu
        if($uploadOk == 1 && !in_array($imageFileType, ["jpg", "png", "jpeg", "gif"])) {
            $_SESSION['message_type'] = 'error';
            $_SESSION['message'] = 'Maaf, hanya format JPG, JPEG, PNG & GIF yang diizinkan.';
            $uploadOk = 0;
        }

        // Jika semua pemeriksaan lolos, coba unggah file
        if ($uploadOk == 1) {
            if (move_uploaded_file($_FILES["gambar"]["tmp_name"], $target_file)) {
                $gambar_name_to_db = $new_file_name; // Ini nama yang akan disimpan ke database
                // $_SESSION['message'] akan diatur di akhir
            } else {
                $_SESSION['message_type'] = 'error';
                $_SESSION['message'] = 'Gagal mengunggah gambar. Pastikan folder uploads/ ada dan memiliki izin tulis.';
                header("Location: index-kepala-marketing.php");
                $conn->close();
                exit();
            }
        } else {
            // Jika $uploadOk 0, artinya sudah ada error message yang disimpan di session
            header("Location: index-kepala-marketing.php");
            $conn->close();
            exit();
        }
    } else {
        // Ini akan menangani kasus di mana tidak ada file diunggah sama sekali
        // atau ada error upload selain UPLOAD_ERR_OK.
        // Jika gambar diperlukan, ini adalah tempat untuk menghentikan proses.
        $_SESSION['message_type'] = 'error';
        $_SESSION['message'] = 'Tidak ada gambar yang diunggah atau terjadi kesalahan unggah. Kode Error: ' . $_FILES["gambar"]["error"];
        header("Location: index-kepala-marketing.php");
        $conn->close();
        exit();
    }

    // Masukkan data produk ke database
    // Pastikan nama kolom 'gambar' di database sesuai dengan `gambar_name_to_db`
    $sql = "INSERT INTO landing_produk (nama_produk, harga, diskon, gambar, is_promo) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        $_SESSION['message_type'] = 'error';
        $_SESSION['message'] = 'Gagal menyiapkan statement SQL: ' . $conn->error;
        header("Location: index-kepala-marketing.php");
        $conn->close();
        exit();
    }

    $stmt->bind_param("sdisi", $nama_produk, $harga, $diskon, $gambar_name_to_db, $is_promo);

    if ($stmt->execute()) {
        $_SESSION['message_type'] = 'success';
        $_SESSION['message'] = 'Produk baru berhasil ditambahkan!';
    } else {
        $_SESSION['message_type'] = 'error';
        $_SESSION['message'] = 'Gagal menambahkan produk ke database: ' . $stmt->error;
    }

    $stmt->close();
    header("Location: index-kepala-marketing.php"); // Redirect kembali ke halaman admin
    exit();

} else {
    // Jika bukan POST request
    $_SESSION['message_type'] = 'error';
    $_SESSION['message'] = 'Metode request tidak diizinkan.';
    header("Location: index-kepala-marketing.php"); // Redirect kembali ke halaman admin
    exit();
}

$conn->close();
?>