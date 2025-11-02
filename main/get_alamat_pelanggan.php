<?php
header('Content-Type: text/plain'); // Set header untuk response type

// Koneksi ke database dengan error handling
try {
    $conn = mysqli_connect("localhost", "root", "", "dbpergudangan");
    
    if (!$conn) {
        throw new Exception("Koneksi database gagal: " . mysqli_connect_error());
    }

    // Validasi input
    if (!isset($_GET['pelanggan_id']) || empty($_GET['pelanggan_id'])) {
        throw new Exception("Parameter pelanggan_id tidak valid");
    }

    $pelanggan_id = (int)$_GET['pelanggan_id']; // Sanitasi input

    if ($pelanggan_id <= 0) {
        throw new Exception("ID Pelanggan harus bilangan positif");
    }

    // Gunakan prepared statement untuk keamanan
    $query = "SELECT alamat FROM pelanggan WHERE pelanggan_id = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $query);

    if (!$stmt) {
        throw new Exception("Persiapan query gagal: " . mysqli_error($conn));
    }

    // Bind parameter dan eksekusi
    mysqli_stmt_bind_param($stmt, "i", $pelanggan_id);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Eksekusi query gagal: " . mysqli_stmt_error($stmt));
    }

    // Bind result
    mysqli_stmt_bind_result($stmt, $alamat);
    
    if (mysqli_stmt_fetch($stmt)) {
        echo $alamat; // Output alamat jika ditemukan
    } else {
        echo "Alamat tidak ditemukan untuk ID pelanggan ini";
    }

    // Tutup statement dan koneksi
    mysqli_stmt_close($stmt);
    mysqli_close($conn);

} catch (Exception $e) {
    // Log error untuk debugging (dalam produksi, simpan ke file log)
    error_log($e->getMessage());
    
    // Response error yang aman untuk dikirim ke client
    echo "Terjadi kesalahan saat memproses permintaan";
    exit;
}
?>