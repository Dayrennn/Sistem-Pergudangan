<?php
session_start();
include 'koneksi.php';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = mysqli_real_escape_string($conn, $_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = mysqli_real_escape_string($conn, $_POST['role'] ?? '');

    // Validasi input
    if (empty($username) || empty($password) || empty($role)) {
        $error = "Semua kolom wajib diisi.";
    } else {
        // Cek apakah username sudah digunakan
        $check = mysqli_query($conn, "SELECT * FROM users WHERE username='$username'");
        if (mysqli_num_rows($check) > 0) {
            $error = "Username sudah terdaftar.";
        } else {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $insert = mysqli_query($conn, "INSERT INTO users (username, password_hash, role) VALUES ('$username', '$password_hash', '$role')");
            if ($insert) {
                // Arahkan ke login setelah sukses daftar
                header("Location: login.php?register=success");
                exit;
            } else {
                $error = "Gagal mendaftar. Silakan coba lagi.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Sistem Gudang</title>
    <link rel="stylesheet" href="../css/login.css">
    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>

<body>
    <div class="bg-animated"></div>
    <div class="bg-overlay"></div>

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
            <h1>Daftar Akun Baru</h1>
            <p>Isi form di bawah untuk membuat akun baru</p>
        </div>

        <?php if ($error): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form class="login-form" method="POST" action="">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required>
                <i class="fas fa-user"></i>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
                <i class="fas fa-lock"></i>
            </div>
            <div class="form-group">
                <label for="role">Pilih Role</label>
                <select name="role" id="role" required>
                    <option value="">-- Pilih Role --</option>
                    <option value="admin">Admin</option>
                    <option value="komisaris">Komisaris</option>
                    <option value="direktur">Direktur</option>
                    <option value="kepala marketing">Kepala Marketing</option>
                    <option value="kepala keuangan">Kepala Keuangan</option>
                    <option value="kepala gudang">Kepala Gudang</option>
                    <option value="staff gudang">Staff Gudang</option>
                </select>
                <i class="fas fa-user-tag"></i>
            </div>
            <button type="submit" class="login-button">DAFTAR</button>
        </form>

        <div class="login-footer">
            <p>Sudah punya akun? <a href="login.php">Login</a></p>
            <p class="company-info">&copy; <?= date('Y'); ?> PT. Trimitra Abadi Lestari. All rights reserved.</p>
        </div>
    </div>
</body>

</html>
