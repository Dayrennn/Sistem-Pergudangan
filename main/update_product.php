<?php
// update_product.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'koneksi.php'; // Pastikan file koneksi database Anda di-include

// Hanya izinkan akses melalui metode POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'];
    $nama_produk = mysqli_real_escape_string($conn, $_POST['nama_produk']);
    $harga = mysqli_real_escape_string($conn, $_POST['harga']);
    $diskon = isset($_POST['diskon']) ? (int)$_POST['diskon'] : 0;
    $is_promo = isset($_POST['is_promo']) ? 1 : 0; // Checkbox, jika dicentang nilainya 1, jika tidak 0

    $gambar_produk_lama = ''; // Untuk menyimpan nama gambar lama
    $new_gambar_name = ''; // Untuk menyimpan nama gambar baru (jika ada upload baru)

    // Langkah 1: Ambil nama gambar lama dari database (jika ada)
    $stmt_get_gambar = $conn->prepare("SELECT gambar FROM landing_produk WHERE id = ?");
    if ($stmt_get_gambar) {
        $stmt_get_gambar->bind_param("i", $id);
        $stmt_get_gambar->execute();
        $stmt_get_gambar->bind_result($gambar_produk_lama);
        $stmt_get_gambar->fetch();
        $stmt_get_gambar->close();
    }

    // Langkah 2: Proses upload gambar baru (jika ada file yang diunggah)
    if (isset($_FILES['gambar_produk_baru']) && $_FILES['gambar_produk_baru']['error'] === UPLOAD_ERR_OK) {
        $file_tmp_name = $_FILES['gambar_produk_baru']['tmp_name'];
        $file_name = $_FILES['gambar_produk_baru']['name'];
        $file_size = $_FILES['gambar_produk_baru']['size'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        $max_file_size = 5 * 1024 * 1024; // 5 MB

        if (!in_array($file_ext, $allowed_extensions)) {
            $_SESSION['message_type'] = 'error';
            $_SESSION['message'] = 'Ekstensi file tidak diizinkan. Hanya JPG, JPEG, PNG, GIF yang diizinkan.';
            header("Location: index-kepala-marketing.php");
            exit();
        } elseif ($file_size > $max_file_size) {
            $_SESSION['message_type'] = 'error';
            $_SESSION['message'] = 'Ukuran file terlalu besar. Maksimal 5 MB.';
            header("Location: index-kepala-marketing.php");
            exit();
        } else {
            // Buat nama file unik
            $new_gambar_name = uniqid('produk_', true) . '.' . $file_ext;
            $target_dir = "uploads/"; // PASTIKAN PATH INI SESUAI DENGAN LOKASI FOLDER UPLOADS
            $target_file = $target_dir . $new_gambar_name;

            // Pastikan folder uploads ada dan writable
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }

            if (move_uploaded_file($file_tmp_name, $target_file)) {
                // Jika upload berhasil, hapus gambar lama (jika ada dan bukan default/kosong)
                if ($gambar_produk_lama && file_exists($target_dir . $gambar_produk_lama)) {
                    unlink($target_dir . $gambar_produk_lama);
                }
            } else {
                $_SESSION['message_type'] = 'error';
                $_SESSION['message'] = 'Gagal mengunggah gambar baru.';
                header("Location: index-kepala-marketing.php");
                exit();
            }
        }
    } else if (isset($_FILES['gambar_produk_baru']) && $_FILES['gambar_produk_baru']['error'] !== UPLOAD_ERR_NO_FILE) {
        // Tangani error upload lainnya
        $_SESSION['message_type'] = 'error';
        $_SESSION['message'] = 'Terjadi kesalahan saat mengunggah gambar: Error Code ' . $_FILES['gambar_produk_baru']['error'];
        header("Location: index-kepala-marketing.php");
        exit();
    }

    // Langkah 3: Perbarui data di database
    if ($new_gambar_name) {
        // Jika ada gambar baru, update kolom 'gambar' juga
        $stmt = $conn->prepare("UPDATE landing_produk SET nama_produk=?, harga=?, diskon=?, is_promo=?, gambar=? WHERE id=?");
        if ($stmt) {
            $stmt->bind_param("siiii", $nama_produk, $harga,  $diskon, $is_promo, $new_gambar_name, $id);
        }
    } else {
        // Jika tidak ada gambar baru, jangan update kolom 'gambar'
        $stmt = $conn->prepare("UPDATE landing_produk SET nama_produk=?, harga=?,  diskon=?, is_promo=? WHERE id=?");
        if ($stmt) {
            $stmt->bind_param("siiii", $nama_produk, $harga,  $diskon, $is_promo, $id);
        }
    }

    if ($stmt) {
        if ($stmt->execute()) {
            $_SESSION['message_type'] = 'success';
            $_SESSION['message'] = 'Produk berhasil diperbarui!';
        } else {
            $_SESSION['message_type'] = 'error';
            $_SESSION['message'] = 'Gagal memperbarui produk: ' . $stmt->error;
        }
        $stmt->close();
    } else {
        $_SESSION['message_type'] = 'error';
        $_SESSION['message'] = 'Gagal menyiapkan statement: ' . $conn->error;
    }

    $conn->close();
    header("Location: index-kepala-marketing.php"); // Redirect kembali ke halaman admin
    exit();

} else {
    // Jika bukan metode POST, redirect ke halaman admin
    $_SESSION['message_type'] = 'error';
    $_SESSION['message'] = 'Akses tidak sah.';
    header("Location: index-kepala-marketing.php");
    exit();
}
?>