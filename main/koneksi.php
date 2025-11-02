<?php
$servername = "localhost";  // Ganti dengan alamat server database Anda
$username = "trix2829_rafly";         // Ganti dengan username database Anda
$password = "kukubimaplus";             // Ganti dengan password database Anda
$dbname = "trix2829_dbpergudangan";  // Ganti dengan nama database Anda

// Membuat koneksi
$conn = mysqli_connect($servername, $username, $password, $dbname);

// Memeriksa koneksi
if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}
?>
