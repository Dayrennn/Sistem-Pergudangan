<?php
session_start(); // Mulai sesi untuk mengakses $_SESSION

include 'koneksi.php'; // Pastikan koneksi DB terhubung

// Cek apakah pengguna sudah login
if (!isset($_SESSION['username']) || !isset($_SESSION['role'])) {
    // Jika belum login, arahkan ke halaman login
    header("Location: login.php");
    exit;
}

// Definisikan peran yang diizinkan untuk mengedit pelanggan
// Anda bisa menambahkan 'kepala_marketing' atau 'admin' atau 'manager' di sini sesuai kebutuhan
$allowed_roles = ['admin', 'kepala_marketing']; // Contoh: hanya admin dan kepala marketing yang boleh mengedit

// Ambil peran pengguna dari sesi
$user_role = $_SESSION['role'];

// Cek apakah peran pengguna diizinkan
if (!in_array($user_role, $allowed_roles)) {
    // Jika peran tidak diizinkan, arahkan ke halaman lain atau tampilkan pesan error
    // Contoh: kembali ke halaman sebelumnya dengan pesan
    echo "<script>alert('Anda tidak memiliki izin untuk melakukan tindakan ini.'); history.back();</script>";
    exit;
}

// Cek apakah form dikirim
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $pelanggan_id = $_POST['pelanggan_id'];
    $nama_pelanggan = mysqli_real_escape_string($conn, $_POST['nama_pelanggan']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $kontak = mysqli_real_escape_string($conn, $_POST['kontak']);
    $alamat = mysqli_real_escape_string($conn, $_POST['alamat']);

    // Validasi sederhana
    if (empty($pelanggan_id) || empty($nama_pelanggan) || empty($email) || empty($kontak) || empty($alamat)) {
        echo "<script>alert('Semua data harus diisi.'); history.back();</script>";
        exit;
    }

    // Gunakan prepared statements untuk keamanan dan efisiensi
    $query = "UPDATE pelanggan SET
                nama_pelanggan = ?,
                email = ?,
                kontak = ?,
                alamat = ?
              WHERE pelanggan_id = ?";

    $stmt = mysqli_prepare($conn, $query);

    if ($stmt) {
        // 's' untuk string, 'i' untuk integer, dll. Urutan harus sesuai dengan tanda tanya di query
        mysqli_stmt_bind_param($stmt, "ssssi", $nama_pelanggan, $email, $kontak, $alamat, $pelanggan_id);

        if (mysqli_stmt_execute($stmt)) {
            echo "<script>alert('Data pelanggan berhasil diperbarui.'); window.location.href='pages/pelanggan-$user_role.php';</script>";
            // Anda mungkin ingin mengarahkan kembali ke halaman pelanggan yang spesifik untuk peran tersebut
            // Contoh: pelanggan-admin.php, pelanggan-kepala-marketing.php
        } else {
            echo "Gagal memperbarui data: " . mysqli_error($conn);
        }
        mysqli_stmt_close($stmt);
    } else {
        echo "Gagal mempersiapkan statement: " . mysqli_error($conn);
    }

} else {
    // Kalau akses tanpa POST
    header("Location: login.php"); // Arahkan ke login atau halaman lain yang relevan
    exit;
}

// Tutup koneksi database (opsional, karena PHP akan menutupnya secara otomatis di akhir skrip)
mysqli_close($conn);
?>