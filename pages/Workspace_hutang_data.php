<?php
// Aktifkan pelaporan error untuk debugging selama pengembangan
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Sertakan file koneksi database
// Pastikan path ini benar. Jika koneksi.php berada di direktori yang sama, gunakan 'koneksi.php'.
// Jika koneksi.php berada satu level di atas (misalnya, jika Workspace_hutang_data.php di subfolder), gunakan '../koneksi.php'.
include '../koneksi.php'; 

// Set header Content-Type ke application/json
// Ini memberi tahu browser bahwa respons dari server adalah data JSON
header('Content-Type: application/json');

// Periksa apakah koneksi database berhasil dibuat
if (!$conn) {
    // Jika koneksi gagal, kirim pesan error JSON dan hentikan eksekusi
    echo json_encode(['error' => 'Koneksi database gagal: ' . mysqli_connect_error()]);
    exit; // Penting: Hentikan eksekusi skrip
}

// Periksa apakah parameter 'id' ada di URL (permintaan GET)
if (isset($_GET['id'])) {
    $id = (int)$_GET['id']; // Ambil dan bersihkan ID hutang dari URL

    // Query untuk mengambil data hutang pegawai berdasarkan ID
    // Menggunakan JOIN dengan tabel 'users' untuk mendapatkan nama pegawai
    $query = "
        SELECT 
            hp.id, 
            hp.pegawai_id, 
            u.username AS nama_pegawai, 
            hp.jumlah, 
            hp.keterangan, 
            hp.tanggal_hutang, 
            hp.tanggal_lunas, 
            hp.status 
        FROM hutang_pegawai hp
        JOIN users u ON hp.pegawai_id = u.user_id
        WHERE hp.id = ?
    ";
    
    // Siapkan prepared statement untuk keamanan (mencegah SQL Injection)
    $stmt = mysqli_prepare($conn, $query);

    // Periksa apakah prepared statement berhasil disiapkan
    if ($stmt) {
        // Ikat parameter 'id' ke placeholder (?) dalam query
        mysqli_stmt_bind_param($stmt, "i", $id); // "i" menandakan integer
        
        // Jalankan prepared statement
        mysqli_stmt_execute($stmt);
        
        // Ambil hasil dari statement yang dijalankan
        $result = mysqli_stmt_get_result($stmt);

        // Ambil baris data sebagai array asosiatif
        if ($row = mysqli_fetch_assoc($result)) {
            // Jika data ditemukan, encode data tersebut ke format JSON dan kirim
            echo json_encode($row);
        } else {
            // Jika tidak ada data ditemukan untuk ID tersebut, kirim pesan error JSON
            echo json_encode(['error' => 'Data hutang tidak ditemukan untuk ID ini.']); 
        }
        
        // Tutup statement
        mysqli_stmt_close($stmt);
    } else {
        // Jika prepared statement gagal disiapkan, kirim pesan error JSON
        echo json_encode(['error' => 'Gagal menyiapkan query: ' . mysqli_error($conn)]);
    }
} else {
    // Jika parameter 'id' tidak diberikan, kirim pesan error JSON
    echo json_encode(['error' => 'ID hutang tidak diberikan.']); 
}

// Tutup koneksi database setelah semua operasi selesai
// Penting: Tutup koneksi hanya jika koneksi berhasil dibuka
if ($conn) {
    mysqli_close($conn);
}

// Penting: Hentikan eksekusi skrip PHP untuk memastikan hanya output JSON yang dikirim
exit; 
?>
