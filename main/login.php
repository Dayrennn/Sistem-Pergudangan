<?php
session_start();
include 'koneksi.php';  // Memasukkan file koneksi.php untuk koneksi ke database


$error = '';  // Menyimpan pesan error

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Mengambil data input dan mencegah SQL Injection
    $username = mysqli_real_escape_string($conn, $_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // Cek user berdasarkan username
    $query = mysqli_query($conn, "SELECT * FROM users WHERE username='$username'");
    $user = mysqli_fetch_assoc($query);

    if ($user) {
        // Verifikasi password yang di-hash
        if (password_verify($password, $user['password_hash'])) {
            // Jika login berhasil, simpan data ke session
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            // Arahkan berdasarkan role
            switch ($_SESSION['role']) {
                case 'admin':
                    header("Location: index.php"); // Halaman admin
                    break;
                case 'komisaris':
                    header("Location: index-komisaris.php"); // Halaman komisaris
                    break;
                case 'direktur':
                    header("Location: index-direktur.php"); // Halaman direktur
                    break;
                case 'kepala_marketing':
                    header("Location: index-kepala-marketing.php"); // Halaman kepala marketing
                    break;
                case 'kepala_keuangan':
                    header("Location: index-kepala-keuangan.php"); // Halaman kepala keuangan
                    break;
                case 'kepala_gudang':
                    header("Location: index-kepala-gudang.php"); // Halaman kepala gudang
                    break;
                case 'staff_gudang':
                    header("Location: index-staff-gudang.php"); // Halaman staff gudang
                    break;
                default:
                    $error = "Role tidak dikenali.";
                    break;
            }
            exit;  // Keluar setelah redirection
        } else {
            $error = "Password salah.";  // Jika password tidak sesuai
        }
    } else {
        $error = "Username tidak ditemukan.";  // Jika username tidak ada di database
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem Gudang</title>
    <link rel="stylesheet" href="../css/login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>

<body>
    <!-- Background Elements -->
    <div class="bg-animated"></div>
    <div class="bg-overlay"></div>

    <!-- Floating Warehouse Elements -->
    <div class="warehouse-element box element1"></div>
    <div class="warehouse-element forklift element2"></div>
    <div class="warehouse-element clipboard element3"></div>
    <div class="warehouse-element box element4"></div>

    <div class="login-container">
        <div class="login-header">
            <div class="company-logo">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#2C3E50">
                    <path d="M12 2L1 12h3v9h6v-6h4v6h6v-9h3L12 2zm0 2.8L18 10v9h-2v-6H8v6H6v-9l6-7.2z" />
                </svg>
            </div>
            <h1>Selamat Datang</h1>
            <p>Sistem Manajemen Gudang PT. Trimitra Abadi Lestari</p>
        </div>
        <!-- Menampilkan pesan sukses setelah register -->
        <?php if (isset($_GET['register']) && $_GET['register'] == 'success'): ?>
        <div class="success-message">
            Pendaftaran berhasil. Silakan login.
        </div>
        <?php endif; ?>


        <!-- Menampilkan pesan error jika ada -->
        <?php if ($error): ?>
        <div class="error-message">
            <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <!-- Form login -->
        <form class="login-form" method="POST" action="login.php">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required autofocus>
                <i class="fas fa-user"></i>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
                <i class="fas fa-lock"></i>
            </div>
            <button type="submit" class="login-button">MASUK</button>
        </form>

        <div class="login-footer">
            <p class="company-info">&copy;
                <?php echo date('Y'); ?> PT. Trimitra Abadi Lestari. All rights reserved.
            </p>
        </div>
    </div>
</body>

</html>