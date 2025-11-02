<?php
session_start();
require_once 'koneksi.php'; // Pastikan path ini benar

// =======================================================
// KEAMANAN: Batasi Akses Hanya untuk Kepala Marketing dan Admin
// =======================================================
if (!isset($_SESSION['username'])) {
    // Jika belum login, redirect ke halaman login
    $_SESSION['pesan_error'] = "Anda harus login untuk mengakses halaman ini.";
    header("Location: login.php");
    exit;
}

// Cek role pengguna
$allowed_roles = ['admin', 'kepala_marketing'];
if (!in_array($_SESSION['role'], $allowed_roles)) {
    // Jika role tidak diizinkan, redirect atau tampilkan pesan error
    $_SESSION['pesan_error'] = "Akses ditolak. Anda tidak memiliki izin untuk mengedit produk.";
    header("Location: " . ($_SESSION['role'] == 'direktur' ? 'index-direktur.php' : 'dashboard.php')); // Sesuaikan redirect untuk role lain
    exit;
}
// =======================================================

// Aktifkan tampilan error untuk debugging (HAPUS DI LINGKUNGAN PRODUKSI!)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Pastikan koneksi database berhasil
if (!$conn) {
    $_SESSION['pesan_error'] = "Koneksi database gagal.";
    header("Location: index-kepala-marketing.php");
    exit;
}

if (isset($_POST['update'])) {
    $id_produk = $_POST['id_produk'];
    $nama_produk = $_POST['nama_produk'];
    $harga = (int)$_POST['harga'];
    $diskon = (int)$_POST['diskon'];

    // 1. Ambil nama gambar lama dari database
    $gambar_lama = '';
    $query_get_gambar_lama = mysqli_query($conn, "SELECT gambar FROM landing_produk WHERE id = '$id_produk'");
    if ($query_get_gambar_lama && $row = mysqli_fetch_assoc($query_get_gambar_lama)) {
        $gambar_lama = $row['gambar'];
    }

    $nama_gambar_untuk_db = $gambar_lama; // Default: pertahankan gambar lama

    // 2. Logika penanganan upload gambar baru
    // Periksa apakah ada file gambar baru yang diupload DAN tidak ada error upload
    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK && !empty($_FILES['gambar']['name'])) {
        $nama_gambar_asli = $_FILES['gambar']['name'];
        $tmp_name = $_FILES['gambar']['tmp_name'];

        $ext_gambar = pathinfo($nama_gambar_asli, PATHINFO_EXTENSION);
        // KRITIS: PASTIKAN .EKSTENSI ADA DI SINI!
        $nama_gambar_baru_unik = uniqid() . '.' . $ext_gambar;
        $upload_path = 'uploads/' . $nama_gambar_baru_unik;

        // Coba pindahkan file yang diupload
        if (move_uploaded_file($tmp_name, $upload_path)) {
            // Gambar baru berhasil diupload. Hapus gambar lama jika ada dan bukan gambar default.

            // --- DEBUGGING START ---
            echo "DEBUG: Gambar Lama dari DB: " . htmlspecialchars($gambar_lama) . "<br>";
            echo "DEBUG: Nama Gambar Baru Unik: " . htmlspecialchars($nama_gambar_baru_unik) . "<br>";
            echo "DEBUG: Path Gambar Lama untuk cek: " . 'uploads/' . htmlspecialchars($gambar_lama) . "<br>";
            // --- DEBUGGING END ---

            // Pastikan gambar lama tidak kosong, file fisiknya ada, bukan 'default.jpg', dan bukan nama file yang sama dengan yang baru diupload
            if (!empty($gambar_lama) && file_exists('uploads/' . $gambar_lama) && $gambar_lama !== 'default.jpg' && $gambar_lama !== $nama_gambar_baru_unik) {
                // --- DEBUGGING START ---
                echo "DEBUG: Kondisi unlink terpenuhi. Mencoba menghapus: " . 'uploads/' . htmlspecialchars($gambar_lama) . "<br>";
                // --- DEBUGGING END ---
                if (unlink('uploads/' . $gambar_lama)) {
                    // --- DEBUGGING START ---
                    echo "DEBUG: Gambar lama berhasil dihapus: " . htmlspecialchars($gambar_lama) . "<br>";
                    // --- DEBUGGING END ---
                } else {
                    // --- DEBUGGING START ---
                    echo "DEBUG: Gagal menghapus gambar lama: " . htmlspecialchars($gambar_lama) . ". Periksa izin folder.<br>";
                    // --- DEBUGGING END ---
                    $_SESSION['pesan_error'] = "Gagal menghapus gambar lama. Periksa izin folder 'uploads'.";
                }
            } else {
                // --- DEBUGGING START ---
                echo "DEBUG: Kondisi unlink TIDAK terpenuhi. Gambar lama tidak dihapus.<br>";
                echo "DEBUG: isEmpty(\$gambar_lama): " . (empty($gambar_lama) ? 'true' : 'false') . "<br>";
                echo "DEBUG: file_exists('uploads/' . \$gambar_lama): " . (file_exists('uploads/' . $gambar_lama) ? 'true' : 'false') . "<br>";
                echo "DEBUG: \$gambar_lama == 'default.jpg': " . (($gambar_lama == 'default.jpg') ? 'true' : 'false') . "<br>";
                echo "DEBUG: \$gambar_lama == \$nama_gambar_baru_unik: " . (($gambar_lama == $nama_gambar_baru_unik) ? 'true' : 'false') . "<br>";
                // --- DEBUGGING END ---
            }
            $nama_gambar_untuk_db = $nama_gambar_baru_unik; // Update nama gambar untuk disimpan ke DB
        } else {
            // Jika move_uploaded_file gagal
            $_SESSION['pesan_error'] = "Gagal memindahkan file gambar baru saat edit produk.";
            header("Location: index-kepala-marketing.php");
            exit;
        }
    } else if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] !== UPLOAD_ERR_NO_FILE && $_FILES['gambar']['error'] !== UPLOAD_ERR_OK) {
        // Tangani error upload lainnya (selain UPLOAD_ERR_NO_FILE)
        $_SESSION['pesan_error'] = "Terjadi error upload gambar saat edit produk: " . $_FILES['gambar']['error'];
        header("Location: index-kepala-marketing.php");
        exit;
    }
    // Jika tidak ada gambar baru diupload (UPLOAD_ERR_NO_FILE), maka nama_gambar_untuk_db akan tetap gambar_lama.
    // Jika ada error upload, maka nama_gambar_untuk_db juga tetap gambar_lama (tapi kita sudah redirect di atas).

    // 3. Query UPDATE: perbarui data termasuk nama gambar
    $query = "UPDATE landing_produk SET nama_produk=?, harga=?, diskon=?, gambar=? WHERE id=?";
    $stmt = mysqli_prepare($conn, $query);

    if ($stmt) {
        // Tipe binding: s (nama_produk string), i (harga integer), i (diskon integer), s (gambar string), i (id integer)
        // Pastikan jumlah 's' dan 'i' sesuai dengan jumlah tanda tanya (?) di query dan jumlah variabel.
        // Ada 5 tanda tanya, jadi harus ada 5 tipe: s, i, i, s, i -> "siiis"
        mysqli_stmt_bind_param($stmt, "siiis", $nama_produk, $harga, $diskon, $nama_gambar_untuk_db, $id_produk);
        
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['pesan_sukses'] = "Produk berhasil diperbarui.";
        } else {
            $_SESSION['pesan_error'] = "Error database saat memperbarui produk: " . mysqli_error($conn);
        }
        mysqli_stmt_close($stmt);
    } else {
        $_SESSION['pesan_error'] = "Error mempersiapkan statement saat edit produk: " . mysqli_error($conn);
    }
    mysqli_close($conn);
    header("Location: index-kepala-marketing.php"); // Redirect kembali setelah proses selesai
    exit;
} else {
    // Jika diakses tanpa submit form update
    $_SESSION['pesan_error'] = "Akses tidak sah untuk halaman edit produk.";
    header("Location: index-kepala-marketing.php");
    exit;
}
?>