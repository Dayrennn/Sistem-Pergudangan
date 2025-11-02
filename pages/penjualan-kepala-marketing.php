<?php
include '../main/koneksi.php';
if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}
session_start();

$username = isset($_SESSION['username']) ? $_SESSION['username'] : 'Guest';
$role = isset($_SESSION['role']) ? $_SESSION['role'] : 'Unknown';   
$action = $_POST['action'] ?? '';


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', 'php_error.log'); // Pastikan ini bisa ditulis

// Ambil data session (pelanggan & pesanan)
$data_pelanggan = $_SESSION['data_pelanggan'] ?? [];
$data_pesanan = $_SESSION['data_pesanan'] ?? [];

// Fungsi flash message dengan redirect
function flashMessage($message, $type = 'success') {
    $_SESSION['message'] = ['text' => $message, 'type' => $type];
    header("Location: penjualan-kepala-marketing.php");
    exit;
}

// Fungsi pembantu untuk respons JSON (untuk AJAX)
function sendJson($success, $message) {
    header('Content-Type: application/json');
    echo json_encode(['success' => $success, 'message' => $message]);
    exit;
}

// Proses tambah pesanan baru (baik dari session atau form)
if ((!empty($data_pelanggan) && !empty($data_pesanan)) || 
    ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['pelanggan_id'], $_POST['barang_id'], $_POST['jumlah_pesan'], $_POST['harga_produk'], $_POST['status']))) {

    if (!empty($data_pelanggan) && !empty($data_pesanan)) {
        $pelanggan_id = mysqli_real_escape_string($conn, $data_pelanggan['pelanggan_id']);
        $barang_id = mysqli_real_escape_string($conn, $data_pesanan['barang_id']);
        $jumlah_pesan = (int)$data_pesanan['jumlah_pesan'];
        $tanggal_terjual = date('Y-m-d');
        $status = 'Pending'; // Status default untuk pesanan baru dari session

        // Ambil harga produk
        $query_harga = "SELECT harga_jual FROM produk_jadi WHERE id_produk_jadi = ?"; // PERBAIKAN: PRODUK_JADI -> produk_jadi
        $stmt_harga = mysqli_prepare($conn, $query_harga);
        mysqli_stmt_bind_param($stmt_harga, "s", $barang_id);
        mysqli_stmt_execute($stmt_harga);
        $result_harga = mysqli_stmt_get_result($stmt_harga);
        $produk = mysqli_fetch_assoc($result_harga);
        mysqli_stmt_close($stmt_harga);
        $harga_produk = $produk ? (float)$produk['harga_jual'] : 0;

        // Ambil alamat pelanggan
        $query_alamat = "SELECT alamat FROM pelanggan WHERE pelanggan_id = ?";
        $stmt_alamat = mysqli_prepare($conn, $query_alamat);
        mysqli_stmt_bind_param($stmt_alamat, "s", $pelanggan_id);
        mysqli_stmt_execute($stmt_alamat);
        $result_alamat = mysqli_stmt_get_result($stmt_alamat);
        $pelanggan = mysqli_fetch_assoc($result_alamat);
        mysqli_stmt_close($stmt_alamat);
        $alamat = $pelanggan ? mysqli_real_escape_string($conn, $pelanggan['alamat']) : '';

    } else {
        // Data dari form penjualan-kepala-marketing.php (saat pesanan dibuat dari form)
        $pelanggan_id = mysqli_real_escape_string($conn, $_POST['pelanggan_id']);
        $barang_id = mysqli_real_escape_string($conn, $_POST['barang_id']);
        $jumlah_pesan = (int)$_POST['jumlah_pesan'];
        $harga_produk = (float)$_POST['harga_produk'];
        $status = mysqli_real_escape_string($conn, $_POST['status']); // Status yang dipilih saat input
        $tanggal_terjual = date('Y-m-d');

        // Ambil alamat pelanggan
        $query_alamat = "SELECT alamat FROM pelanggan WHERE pelanggan_id = ?";
        $stmt_alamat = mysqli_prepare($conn, $query_alamat);
        mysqli_stmt_bind_param($stmt_alamat, "s", $pelanggan_id);
        mysqli_stmt_execute($stmt_alamat);
        $result_alamat = mysqli_stmt_get_result($stmt_alamat);
        $pelanggan = mysqli_fetch_assoc($result_alamat);
        mysqli_stmt_close($stmt_alamat);
        if (!$pelanggan) {
            flashMessage("Pelanggan tidak ditemukan.", 'error');
        }
        $alamat = mysqli_real_escape_string($conn, $pelanggan['alamat']);
    }

    // Validasi input
    if (empty($pelanggan_id) || empty($barang_id) || $jumlah_pesan <= 0 || $harga_produk <= 0) {
        flashMessage("Data yang dikirimkan tidak lengkap atau tidak valid.", 'error');
    }

    // Ambil data produk & stok
    $cek_produk_query = "SELECT id_produk_jadi, stok, nama_produk FROM produk_jadi WHERE id_produk_jadi = ?"; // PERBAIKAN: PRODUK_JADI -> produk_jadi
    $stmt_cek_produk = mysqli_prepare($conn, $cek_produk_query);
    mysqli_stmt_bind_param($stmt_cek_produk, "s", $barang_id);
    mysqli_stmt_execute($stmt_cek_produk);
    $result_cek_produk = mysqli_stmt_get_result($stmt_cek_produk);
    $produk_data = mysqli_fetch_assoc($result_cek_produk);
    mysqli_stmt_close($stmt_cek_produk);

    if (!$produk_data) {
        flashMessage("Produk tidak ditemukan", 'error');
    }

    $stok_saat_ini = (int)$produk_data['stok'];
    $nama_produk = mysqli_real_escape_string($conn, $produk_data['nama_produk']);

    // Cek stok cukup atau tidak
    if ($stok_saat_ini < $jumlah_pesan) {
        flashMessage("Stok $nama_produk tidak mencukupi! Stok tersedia: $stok_saat_ini", 'error');
    }

    $total_harga = $jumlah_pesan * $harga_produk;

    mysqli_begin_transaction($conn);
    try {
        // --- MODIFIKASI DIMULAI DI SINI ---

        // Masukkan ke tabel `pesanan` (SELALU dimasukkan, tidak peduli status)
        $insert_pesanan_query = "INSERT INTO pesanan 
                                        (pelanggan_id, barang_id, jumlah_pesan, total_harga, status, tanggal_pesan) 
                                        VALUES (?, ?, ?, ?, ?, ?)";
        $stmt_insert_pesanan = mysqli_prepare($conn, $insert_pesanan_query);
        mysqli_stmt_bind_param($stmt_insert_pesanan, "sidsis", $pelanggan_id, $barang_id, $jumlah_pesan, $total_harga, $status, $tanggal_terjual);
        if (!mysqli_stmt_execute($stmt_insert_pesanan)) {
            throw new Exception("Gagal menambahkan data ke pesanan: " . mysqli_error($conn));
        }
        $inserted_pesanan_id = mysqli_insert_id($conn); // Dapatkan ID pesanan yang baru saja dimasukkan
        mysqli_stmt_close($stmt_insert_pesanan);

        // Masukkan ke tabel `produk_terjual` HANYA JIKA statusnya 'Selesai'
        if (strtolower($status) === 'selesai') {
            $insert_terjual_query = "INSERT INTO produk_terjual 
                                     (pesanan_id, id_produk_jadi, jumlah_terjual, tanggal_terjual, alamat, pelanggan_id, harga, status, status_pembayaran) 
                                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt_insert_terjual = mysqli_prepare($conn, $insert_terjual_query);
            // Asumsi 'Belum Lunas' untuk pesanan 'Selesai' yang baru, sesuaikan jika ada input status pembayaran
            $status_pembayaran_default = 'Belum Lunas'; 
            mysqli_stmt_bind_param($stmt_insert_terjual, "isissidis", 
                $inserted_pesanan_id, // Terhubung ke pesanan_id yang baru
                $barang_id, 
                $jumlah_pesan, 
                $tanggal_terjual, 
                $alamat, 
                $pelanggan_id, 
                $total_harga, 
                $status, // Status 'Selesai'
                $status_pembayaran_default
            );
            if (!mysqli_stmt_execute($stmt_insert_terjual)) {
                throw new Exception("Gagal menambahkan data ke produk_terjual: " . mysqli_error($conn));
            }
            mysqli_stmt_close($stmt_insert_terjual);
        }

        // --- MODIFIKASI BERAKHIR DI SINI ---

        // Perbarui stok produk_jadi (stok selalu dikurangi saat pesanan dibuat, tidak peduli status awal)
        $update_stok_query = "UPDATE produk_jadi SET stok = stok - ? WHERE id_produk_jadi = ?"; // PERBAIKAN: PRODUK_JADI -> produk_jadi
        $stmt_update_stok = mysqli_prepare($conn, $update_stok_query);
        mysqli_stmt_bind_param($stmt_update_stok, "is", $jumlah_pesan, $barang_id);
        if (!mysqli_stmt_execute($stmt_update_stok)) {
            throw new Exception("Gagal mengurangi stok produk: " . mysqli_error($conn));
        }
        mysqli_stmt_close($stmt_update_stok);

        mysqli_commit($conn);
        flashMessage("Pesanan berhasil ditambahkan! Stok produk diperbarui.", 'success');

    } catch (Exception $e) {
        mysqli_rollback($conn);
        flashMessage("Terjadi kesalahan: " . $e->getMessage(), 'error');
    }
}

// Tangani permintaan AJAX untuk pembaruan status pesanan
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["aksi"])) {
    $aksi = $_POST["aksi"];

    if ($aksi === "update_status") {
        error_log("Aksi update_status dipanggil");
        error_log("Seluruh Data POST: " . print_r($_POST, true));

        if (isset($_POST["pesanan_id"]) && isset($_POST["status"])) {
            error_log("pesanan_id dan status ada");

            $pesanan_id = mysqli_real_escape_string($conn, $_POST["pesanan_id"]);
            $status = mysqli_real_escape_string($conn, $_POST["status"]);
            
            mysqli_begin_transaction($conn);
            try {
                // Perbarui status di tabel 'pesanan'
                $stmt = $conn->prepare("UPDATE pesanan SET status = ? WHERE pesanan_id = ?");
                if ($stmt === false) {
                    throw new Exception("Gagal menyiapkan statement untuk pesanan: " . $conn->error);
                }
                $stmt->bind_param("si", $status, $pesanan_id);
                if (!$stmt->execute()) {
                    throw new Exception("Gagal menjalankan query update pesanan: " . $stmt->error);
                }
                $stmt->close();

                // Cek jika status yang baru adalah 'Selesai'
                if (strtolower($status) === 'selesai') {
                    // Cek apakah pesanan ini sudah ada di produk_terjual
                    $check_terjual_query = "SELECT id_terjual FROM produk_terjual WHERE pesanan_id = ?";
                    $stmt_check_terjual = mysqli_prepare($conn, $check_terjual_query);
                    mysqli_stmt_bind_param($stmt_check_terjual, "i", $pesanan_id);
                    mysqli_stmt_execute($stmt_check_terjual);
                    $result_check_terjual = mysqli_stmt_get_result($stmt_check_terjual);
                    $existing_terjual = mysqli_fetch_assoc($result_check_terjual);
                    mysqli_stmt_close($stmt_check_terjual);

                    if (!$existing_terjual) {
                        // Jika belum ada di produk_terjual, ambil detail pesanan dari `pesanan` untuk dimasukkan
                        $get_pesanan_data_query = "SELECT barang_id, jumlah_pesan, pelanggan_id, total_harga, tanggal_pesan FROM pesanan WHERE pesanan_id = ?";
                        $stmt_get_pesanan = mysqli_prepare($conn, $get_pesanan_data_query);
                        mysqli_stmt_bind_param($stmt_get_pesanan, "i", $pesanan_id);
                        mysqli_stmt_execute($stmt_get_pesanan);
                        $result_pesanan_data = mysqli_stmt_get_result($stmt_get_pesanan);
                        $pesanan_data = mysqli_fetch_assoc($result_pesanan_data);
                        mysqli_stmt_close($stmt_get_pesanan);

                        if ($pesanan_data) {
                            // Ambil alamat pelanggan
                            $query_alamat = "SELECT alamat FROM pelanggan WHERE pelanggan_id = ?";
                            $stmt_alamat = mysqli_prepare($conn, $query_alamat);
                            mysqli_stmt_bind_param($stmt_alamat, "s", $pesanan_data['pelanggan_id']);
                            mysqli_stmt_execute($stmt_alamat);
                            $result_alamat = mysqli_stmt_get_result($stmt_alamat);
                            $pelanggan_alamat_data = mysqli_fetch_assoc($result_alamat);
                            mysqli_stmt_close($stmt_alamat);
                            $alamat_terjual = $pelanggan_alamat_data ? mysqli_real_escape_string($conn, $pelanggan_alamat_data['alamat']) : '';

                            // Masukkan ke produk_terjual
                            $insert_terjual_query = "INSERT INTO produk_terjual 
                                                     (pesanan_id, id_produk_jadi, jumlah_terjual, tanggal_terjual, alamat, pelanggan_id, harga, status, status_pembayaran) 
                                                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                            $stmt_insert_terjual = mysqli_prepare($conn, $insert_terjual_query);
                            $status_pembayaran_default = 'Belum Lunas'; // Default untuk pesanan yang baru diselesaikan
                            mysqli_stmt_bind_param($stmt_insert_terjual, "isissidis", 
                                $pesanan_id, 
                                $pesanan_data['barang_id'], 
                                $pesanan_data['jumlah_pesan'], 
                                $pesanan_data['tanggal_pesan'], // Gunakan tanggal pesanan yang sudah ada atau NOW()
                                $alamat_terjual, 
                                $pesanan_data['pelanggan_id'], 
                                $pesanan_data['total_harga'], 
                                $status, 
                                $status_pembayaran_default
                            );
                            if (!mysqli_stmt_execute($stmt_insert_terjual)) {
                                throw new Exception("Gagal menambahkan data ke produk_terjual saat update status: " . mysqli_error($conn));
                            }
                            mysqli_stmt_close($stmt_insert_terjual);
                        } else {
                            throw new Exception("Data pesanan tidak ditemukan untuk ID: " . $pesanan_id);
                        }
                    } else {
                        // Jika sudah ada di produk_terjual, perbarui saja statusnya
                        $update_terjual_status_query = "UPDATE produk_terjual SET status = ? WHERE pesanan_id = ?";
                        $stmt_update_terjual_status = mysqli_prepare($conn, $update_terjual_status_query);
                        mysqli_stmt_bind_param($stmt_update_terjual_status, "si", $status, $pesanan_id);
                        if (!mysqli_stmt_execute($stmt_update_terjual_status)) {
                            throw new Exception("Gagal memperbarui status produk_terjual: " . mysqli_error($conn));
                        }
                        mysqli_stmt_close($stmt_update_terjual_status);
                    }
                } else {
                    // Jika status BUKAN 'Selesai', pastikan data dihapus dari produk_terjual jika ada.
                    // Ini untuk memastikan hanya pesanan 'Selesai' yang tercatat sebagai pendapatan.
                    $delete_terjual_query = "DELETE FROM produk_terjual WHERE pesanan_id = ?";
                    $stmt_delete_terjual = mysqli_prepare($conn, $delete_terjual_query);
                    mysqli_stmt_bind_param($stmt_delete_terjual, "i", $pesanan_id);
                    mysqli_stmt_execute($stmt_delete_terjual); 
                    mysqli_stmt_close($stmt_delete_terjual);
                }

                mysqli_commit($conn);
                sendJson(true, "Status pesanan dan data penjualan berhasil diperbarui.");

            } catch (Exception $e) {
                mysqli_rollback($conn);
                error_log("Error updating status via AJAX: " . $e->getMessage());
                sendJson(false, "Terjadi kesalahan: " . $e->getMessage());
            }

        } else {
            error_log("Data tidak lengkap di PHP");
            sendJson(false, "Data tidak lengkap.");
        }
    }
}

// Tangani permintaan Hapus pesanan (melalui AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'delete') {
    $pesanan_id_to_delete = $_POST['pesanan_id'] ?? ''; 
    if ($pesanan_id_to_delete) {
        mysqli_begin_transaction($conn);
        try {
            // Hapus dari produk_terjual terlebih dahulu (jika ada)
            $stmt_delete_terjual = $conn->prepare("DELETE FROM produk_terjual WHERE pesanan_id = ?");
            $stmt_delete_terjual->bind_param("i", $pesanan_id_to_delete);
            $stmt_delete_terjual->execute();
            $stmt_delete_terjual->close();

            // Kemudian hapus dari pesanan
            $stmt_delete_pesanan = $conn->prepare("DELETE FROM pesanan WHERE pesanan_id = ?");
            $stmt_delete_pesanan->bind_param("i", $pesanan_id_to_delete);
            if ($stmt_delete_pesanan->execute()) {
                mysqli_commit($conn);
                echo json_encode(['success' => true, 'message' => 'Pesanan dan data penjualan berhasil dihapus']);
            } else {
                throw new Exception("Gagal menghapus pesanan: " . mysqli_error($conn));
            }
            $stmt_delete_pesanan->close();
        } catch (Exception $e) {
            mysqli_rollback($conn);
            echo json_encode(['success' => false, 'message' => 'Gagal menghapus pesanan: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'ID pesanan tidak ditemukan']);
    }
    exit;
}


$edit_pesanan_id = isset($_POST['pesanan_id']) ? mysqli_real_escape_string($conn, $_POST['pesanan_id']) : null;

// Ambil Data Pesanan untuk ditampilkan di tabel
// Perbarui JOIN condition untuk produk_terjual agar lebih akurat menggunakan pesanan_id
$query_pesanan = "
    SELECT
        ps.pesanan_id,
        ps.barang_id,
        ps.pelanggan_id,
        ps.jumlah_pesan,
        ps.total_harga,
        ps.status,
        ps.tanggal_pesan,
        pt.id_terjual,
        pt.jumlah_terjual,
        pt.tanggal_terjual,
        pel.alamat AS alamat,
        pel.nama_pelanggan,
        pj.nama_produk,
        pj.harga_jual AS harga_satuan,
        pj.stok
    FROM pesanan ps
    LEFT JOIN produk_terjual pt ON 
        ps.pesanan_id = pt.pesanan_id 
    JOIN pelanggan pel ON ps.pelanggan_id = pel.pelanggan_id
    JOIN produk_jadi pj ON ps.barang_id = pj.id_produk_jadi    -- PERBAIKAN DILAKUKAN DI SINI
    ORDER BY ps.tanggal_pesan DESC
";

$result_pesanan = mysqli_query($conn, $query_pesanan);

if (!$result_pesanan) {
    die("Query gagal: " . mysqli_error($conn));
}
$query_data_penjualan = "
    SELECT
        p.tanggal_pesan AS tanggal,
        p.pesanan_id AS no_transaksi,
        p.total_harga AS nominal,
        p.status,
        pl.nama_pelanggan
    FROM pesanan p
    JOIN pelanggan pl ON p.pelanggan_id = pl.pelanggan_id
    ORDER BY p.tanggal_pesan DESC
";

$result_data_penjualan = mysqli_query($conn, $query_data_penjualan);

// Cek jika query gagal
if (!$result_data_penjualan) {
    die("Query data penjualan gagal: " . mysqli_error($conn));
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', 'php_error.log'); // Pastikan ini bisa ditulis

// --- QUERY UTAMA UNTUK MENAMPILKAN SEMUA DATA PENJUALAN ---
// Diperbarui agar LEFT JOIN produk_terjual menggunakan pesanan_id
$query_all_sales_data = mysqli_query($conn, "
    SELECT
        p.pesanan_id,
        p.jumlah_pesan,
        p.status,
        p.tanggal_pesan,
        p.barang_id,      
        p.pelanggan_id,   
        pl.nama_pelanggan,
        pl.kontak,
        pl.email,
        pl.alamat,
        pj.nama_produk,
        pj.harga_jual,
        pj.id_produk_jadi,
        pt.id_terjual     
    FROM pesanan p
    JOIN pelanggan pl ON p.pelanggan_id = pl.pelanggan_id
    LEFT JOIN produk_jadi pj ON p.barang_id = pj.id_produk_jadi    -- PERBAIKAN: PRODUK_JADI -> produk_jadi
    LEFT JOIN produk_terjual pt ON pt.pesanan_id = p.pesanan_id
    ORDER BY p.tanggal_pesan DESC
") or die('Query utama data penjualan gagal: ' . mysqli_error($conn));

if (!$query_all_sales_data) {
    die("Query data penjualan gagal: " . mysqli_error($conn));
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Data Penjualan</title>
    <link rel="stylesheet" href="../css/penjualan.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter&family=Open+Sans&family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
</head>
<body>
<div class="container">
    <nav class="navbar">
        <div class="profile-company">
            <img src="../assets/logo.png" alt="Logo">
        </div>
    </nav>
    <aside class="sidebar">
        <ul>
            <li><div class="logo">
                    <h4 class="logo">PT. TRIMITRA ABADI LESTARI</h4>
                </div>
            </li>
            <hr style="margin-top: 35px; margin-bottom: 30px; border: none; height: 2px; background-color:rgb(145, 145, 145);">
            <div class="sidebar-profile">
                <span>
                            Selamat Datang <?php echo $_SESSION['role']; ?>,<br>
                            <a href="#"><?php echo $_SESSION['username']; ?></a>
                        </span>
            </div>
            <hr style="margin-top: 35px; margin-bottom: 30px; border: none; height: 2px; background-color:rgb(145, 145, 145);">
            <li><a href="../main/index-kepala-marketing.php"><i class='bx bx-home-alt-2'></i><span>Dashboard</span></a></li>
            <li><a href="pelanggan-kepala-marketing.php"><i class='bx bx-package'></i><span>Data Pelanggan</span></a></li>
            <li><a href="penjualan-kepala-marketing.php"><i class='bx bx-cart'></i><span>Data Penjualan</span></a></li>
            <li>
                <form action="../main/logout.php" method="POST">
                    <button type="submit" class="btn-logout-sidebar"><span>Logout</span></button>
                </form>
            </li>
        </ul>
    </aside>
    <div class="beranda">
        <br><br>
        <h2>Data Penjualan</h2>
        <div class="search-container">
            <form method="GET" action="penjualan-kepala-marketing.php">
                <input type="text" name="search" id="searchInput" placeholder="Cari nama pelanggan...">
            </form>
        </div>

           <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Pelanggan</th>
                        <th>Kontak</th>
                        <th>Email</th>
                        <th>Alamat</th>
                        <th>Barang yang Dipesan</th>
                        <th>Jumlah</th>
                        <th>Harga Satuan</th>
                        <th>Total Harga</th>
                        <th>Tanggal Pesanan</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                    <tbody>
                <?php
                $no = 1; // Inisialisasi nomor urut

                // Cek apakah ada data yang ditemukan dari query_all_sales_data
                if (mysqli_num_rows($query_all_sales_data) > 0) {
                    // Lakukan perulangan untuk setiap baris data pesanan/penjualan
                    while ($data_pesanan = mysqli_fetch_assoc($query_all_sales_data)) {
                        $pesanan_id = $data_pesanan['pesanan_id'];
                        $status = $data_pesanan['status'];
                        $harga_satuan = (float)$data_pesanan['harga_jual'];
                        $jumlah_pesan = (int)$data_pesanan['jumlah_pesan'];
                        $total_harga = $harga_satuan * $jumlah_pesan;

                        // Ambil id_terjual dari hasil join, bisa null jika belum ada di produk_terjual
                        $id_terjual = $data_pesanan['id_terjual'];

                        // Menentukan kelas CSS untuk status
                        $status_lower = strtolower($status);
                        $status_class = '';
                        switch ($status_lower) {
                            case 'pending':
                                $status_class = 'status-pending';
                                break;
                            case 'diproses':
                                $status_class = 'status-proses';
                                break;
                            case 'dikirim': 
                                $status_class = 'status-dikirim';
                                break;
                            case 'selesai':
                                $status_class = 'status-selesai';
                                break;
                            case 'dibatalkan':
                                $status_class = 'status-batal';
                                break;
                            default:
                                $status_class = 'status-default';
                        }
                ?>
                        <tr 
                            data-pesanan-id="<?= htmlspecialchars($pesanan_id) ?>" 
                            data-status="<?= htmlspecialchars($status_lower) ?>"
                            data-id-terjual="<?= htmlspecialchars($id_terjual) ?>">
                            <td><?= $no++ ?></td>
                            <td><?= htmlspecialchars($data_pesanan['nama_pelanggan']) ?></td>
                            <td><?= htmlspecialchars($data_pesanan['kontak']) ?></td>
                            <td><?= htmlspecialchars($data_pesanan['email']) ?></td>
                            <td><?= htmlspecialchars($data_pesanan['alamat']) ?></td>
                            <td><?= htmlspecialchars($data_pesanan['nama_produk']) ?></td>
                            <td><?= (int)$data_pesanan['jumlah_pesan'] ?></td>
                            <td>Rp <?= number_format($harga_satuan, 0, ',', '.') ?></td>
                            <td>Rp <?= number_format($total_harga, 0, ',', '.') ?></td>
                            <td><?= htmlspecialchars($data_pesanan['tanggal_pesan']) ?></td>
                            <td><span class='status <?= htmlspecialchars($status_class) ?>'><?= htmlspecialchars($status) ?></span></td>
                            <td class='action-buttons'>
                                <button class='hapusTerjualBtn' 
                                    data-pesanan-id="<?= htmlspecialchars($pesanan_id) ?>" 
                                    data-id-terjual="<?= htmlspecialchars($id_terjual) ?>">
                                    <i class='bx bx-trash'></i> Hapus
                                </button>
                            </td>
                        </tr>
                <?php
                    }
                } else {
                    // Pesan jika tidak ada data penjualan
                    echo "<tr><td colspan='12' style='text-align: center;'>Tidak ada data penjualan tersedia.</td></tr>";
                }
                ?>
                </tbody>
            </table>
    </div>
</div>
<div id="modalEdit" class="modal">
  <div class="modal-content">
    <span id="closeModalEdit" class="close-btn">&times;</span> 
    <h2>Edit Status Pesanan</h2>
    <form id="formEditPesanan" method="post">
        <input type="hidden" name="aksi" value="update_status" />
      <input type="hidden" name="id_terjual" id="editIdTerjual"> 
      <input type="hidden" name="pesanan_id" id="editPesananId">
      <div class="form-group"> 
        <label for="editStatus">Status:</label>
        <select name="status" id="editStatus" required class="form-control"> 
            <option value="Pending">Pending</option>
            <option value="Diproses">Diproses</option>
            <option value="Dikirim">Dikirim</option>
            <option value="Selesai">Selesai</option>
            <option value="Dibatalkan">Dibatalkan</option>
        </select>
      </div>
      <div class="form-actions"> 
        <button type="submit" name="edit_status_submit" class="btn-submit">Update Status</button>
      </div>
    </form>
  </div>
</div>
<div id="overlayEdit" class="modal-overlay"></div> 

<div id="modalPelanggan" class="modal">
    <div class="modal-content">
        <span class="close-btn">&times;</span>
        <h2>Input Pesanan Baru</h2> 
        <form method="POST" action=""> 
            <div class="form-group"> 
                <label for="pelanggan_id_select">Pilih Pelanggan:</label>
                <select name="pelanggan_id" id="pelanggan_id_select" required class="form-control"> 
                    <option value="">Pilih Pelanggan</option>
                    <?php
                    $query_pelanggan_select = mysqli_query($conn, "SELECT pelanggan_id, nama_pelanggan FROM pelanggan");
                    if (!$query_pelanggan_select) {
                        die('Query gagal: ' . htmlspecialchars(mysqli_error($conn), ENT_QUOTES));
                    }
                    while ($pelanggan_select = mysqli_fetch_assoc($query_pelanggan_select)) {
                        echo "<option value='".htmlspecialchars($pelanggan_select['pelanggan_id'], ENT_QUOTES)."'>"
                            .htmlspecialchars($pelanggan_select['nama_pelanggan'], ENT_QUOTES)."</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="form-group"> 
                <label for="barang_id">Pilih Produk:</label>
                <select name="barang_id" id="barang_id" required class="form-control"> 
                    <option value="">Pilih Produk</option>
                    <?php 
                    $query_produk = mysqli_query($conn, "SELECT id_produk_jadi, stok, nama_produk, harga_jual FROM produk_jadi");
                    if (!$query_produk) {
                        die('Query gagal: ' . htmlspecialchars(mysqli_error($conn), ENT_QUOTES));
                    }
                    while ($produk = mysqli_fetch_assoc($query_produk)) { 
                        $id_produk = htmlspecialchars($produk['id_produk_jadi'], ENT_QUOTES);
                        $nama_produk = htmlspecialchars($produk['nama_produk'], ENT_QUOTES);
                        $stok = htmlspecialchars($produk['stok'], ENT_QUOTES);
                        $harga_jual = htmlspecialchars($produk['harga_jual'], ENT_QUOTES);
                        ?>
                        <option value="<?= $id_produk ?>" data-stok="<?= $stok ?>" data-harga="<?= $harga_jual ?>">
                            <?= $nama_produk ?> (Stok: <?= $stok ?>)
                        </option>
                    <?php } ?>
                </select>
                <input type="hidden" name="harga_produk" id="harga_produk_hidden">
            </div>

            <div class="form-group"> 
                <label for="jumlah_pesan">Jumlah Barang:</label>
                <input type="number" name="jumlah_pesan" id="jumlah_pesan" min="1" value="1" required class="form-control"> 
                <small id="stok_msg" class="form-text text-muted"></small> 
            </div>
            
            <div class="form-group"> 
                <label for="status">Status Awal Pesanan:</label>
                <select name="status" id="status_awal_pesanan" required class="form-control"> 
                    <option value="Pending">Pending</option>
                    <option value="Diproses">Diproses</option>
                    <option value="Dikirim">Dikirim</option>
                    <option value="Selesai">Selesai</option> 
                    <option value="Dibatalkan">Dibatalkan</option>
                </select>
            </div>

            <div class="form-actions"> 
                <button type="submit" name="simpan_pesanan" class="btn-submit">Simpan Pesanan</button>
            </div>
        </form>
    </div>
</div>

<div id="modalHapus" class="modal">
    <div class="modal-content">
        <span class="close-btn close-btn-hapus">&times;</span>
        <h2>Konfirmasi Hapus</h2> 
        <p>Yakin ingin menghapus data pesanan ini?</p>
        <form method="POST" action="">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="pesanan_id" id="hapus_pesanan_id">
            
            <div class="button-group"> 
                <button type="button" id="batalHapusBtn" class="btn-cancel">Batal</button> 
                <button type="submit" class="btn-danger">Ya, Hapus</button> 
            </div>
        </form>
    </div>
</div>

<div id="customAlertModal" class="modal">
    <div class="modal-content">
        <h3 id="customAlertTitle" class="text-xl font-bold mb-4 text-gray-800">Pesan</h3>
        <p id="customAlertMessage" class="text-gray-700"></p>
        <div class="modal-footer flex justify-center mt-4">
            <button id="customAlertOkBtn" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition duration-300">OK</button>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    // Logika Modal Edit Status
    const modalEdit = $('#modalEdit');
    const overlayEdit = $('#overlayEdit');
    const closeModalEdit = $('#closeModalEdit');
    const formEditPesanan = $('#formEditPesanan');
    const editPesananId = $('#editPesananId');
    const editIdTerjual = $('#editIdTerjual');
    const editStatusSelect = $('#editStatus');

    $('.editTerjualBtn').on('click', function() {
        var pesananId = $(this).data('pesanan-id');
        var idTerjual = $(this).data('id-terjual'); // Akan null jika belum ada di produk_terjual
        var currentStatus = $(this).closest('tr').find('.status').text().trim();

        editPesananId.val(pesananId);
        editIdTerjual.val(idTerjual); 
        editStatusSelect.val(currentStatus); // Atur nilai pilihan berdasarkan status saat ini

        modalEdit.show();
        overlayEdit.show();
    });

    closeModalEdit.on('click', function() {
        modalEdit.hide();
        overlayEdit.hide();
    });

    overlayEdit.on('click', function() {
        modalEdit.hide();
        overlayEdit.hide();
    });

    formEditPesanan.on('submit', function(e) {
        e.preventDefault(); // Mencegah submit form secara default

        $.ajax({
            url: 'penjualan-kepala-marketing.php', // Submit ke file PHP saat ini
            method: 'POST',
            data: $(this).serialize(), // Serialisasi data form termasuk hidden input
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert(response.message);
                    location.reload(); // Muat ulang halaman untuk melihat perubahan
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error("AJAX Error:", textStatus, errorThrown, jqXHR.responseText);
                alert('Terjadi kesalahan pada server saat memperbarui status.');
            }
        });
    });

    // Logika Modal Hapus
    const modalHapus = $('#modalHapus');
    const closeBtnHapus = $('.close-btn-hapus');
    const batalHapusBtn = $('#batalHapusBtn');
    const hapusPesananId = $('#hapus_pesanan_id');

    $('.hapusTerjualBtn').on('click', function() {
        var pesananIdToDelete = $(this).data('pesanan-id');
        hapusPesananId.val(pesananIdToDelete);
        modalHapus.show();
    });

    closeBtnHapus.on('click', function() {
        modalHapus.hide();
    });

    batalHapusBtn.on('click', function() {
        modalHapus.hide();
    });

    // Fungsionalitas Pencarian
    $('#searchInput').on('keyup', function() {
        var value = $(this).val().toLowerCase();
        $("table tbody tr").filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
        });
    });

    // Logika Modal Pesanan Baru (modalPelanggan)
    const modalPelanggan = $('#modalPelanggan');
    const closeAddBtn = modalPelanggan.find('.close-btn');

    // Asumsi Anda memiliki tombol untuk membuka modal ini, misal: <button id="openAddOrderModal">Tambah Pesanan Baru</button>
    // Jika belum ada, Anda perlu menambahkannya di HTML.
    // Contoh pembuka modal:
    // $('#openAddOrderModal').on('click', function() {
    //     modalPelanggan.show();
    // });
    // closeAddBtn.on('click', function() {
    //     modalPelanggan.hide();
    // });

    // Mengisi input harga_produk_hidden ketika produk dipilih
    $('#barang_id').on('change', function() {
        var selectedOption = $(this).find('option:selected');
        var harga = selectedOption.data('harga');
        $('#harga_produk_hidden').val(harga);
    });

    // Validasi stok pada input jumlah
    $('#jumlah_pesan').on('input', function() {
        var requestedQty = parseInt($(this).val());
        var selectedProductStok = parseInt($('#barang_id option:selected').data('stok'));
        var stokMsg = $('#stok_msg');

        if (isNaN(requestedQty) || requestedQty <= 0) {
            stokMsg.text('Jumlah harus lebih dari 0.');
            $(this).val(1); // Reset ke 1 atau cegah pengiriman
        } else if (requestedQty > selectedProductStok) {
            stokMsg.text('Stok tidak cukup! Tersedia: ' + selectedProductStok);
        } else {
            stokMsg.text('');
        }
    });

    // Picu event change saat halaman dimuat jika ada opsi yang sudah terpilih
    if ($('#barang_id').val()) {
        $('#barang_id').trigger('change');
    }
});
</script>
<script src="../javascript/barang-terjual.js"></script> 
</body>
</html>
