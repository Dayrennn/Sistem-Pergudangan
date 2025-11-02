<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
include '../main/koneksi.php';
session_start();

$username = isset($_SESSION['username']) ? $_SESSION['username'] : 'Guest';
$role = isset($_SESSION['role']) ? $_SESSION['role'] : 'Unknown';   

// Handle AJAX request to fetch single pengeluaran data
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['action']) && $_GET['action'] == 'fetch_pengeluaran' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $query = "SELECT * FROM pengeluaran WHERE id_pengeluaran = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $data = mysqli_fetch_assoc($result); // $data akan null jika tidak ada baris ditemukan

    ob_clean(); // Bersihkan output buffer sebelum mengirim header
    header('Content-Type: application/json');
    if ($data) {
        echo json_encode(['found' => true, 'data' => $data]);
    } else {
        echo json_encode(['found' => false, 'message' => 'Data pengeluaran tidak ditemukan.']);
    }
    exit; // Hentikan eksekusi di sini!
}

// Handle AJAX request to fetch single hutang data
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['action']) && $_GET['action'] == 'fetch_hutang' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $query = "SELECT * FROM hutang_pegawai WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $data = mysqli_fetch_assoc($result); // $data akan null jika tidak ada baris ditemukan

    ob_clean(); // Bersihkan output buffer sebelum mengirim header
    header('Content-Type: application/json');
    if ($data) {
        echo json_encode(['found' => true, 'data' => $data]);
    } else {
        echo json_encode(['found' => false, 'message' => 'Data hutang tidak ditemukan.']);
    }
    exit; // Hentikan eksekusi di sini!
}

$query_pegawai = "SELECT user_id, username FROM users";
$result_pegawai = mysqli_query($conn, $query_pegawai);
$pegawai_list = [];
if ($result_pegawai) {
    while ($row_pegawai = mysqli_fetch_assoc($result_pegawai)) {
        $pegawai_list[] = $row_pegawai;
    }
} else {
    // Tangani error jika query gagal
    error_log("Gagal mengambil data pegawai: " . mysqli_error($conn));
}

// Handle edit pengeluaran (BARU DITAMBAHKAN)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_pengeluaran'])) {
    $id = (int)$_POST['edit_id'];
    $tanggal = mysqli_real_escape_string($conn, $_POST['edit_tanggal']);
    $kategori = mysqli_real_escape_string($conn, $_POST['edit_kategori']);
    $jumlah = (float)$_POST['edit_jumlah'];
    $keterangan = mysqli_real_escape_string($conn, $_POST['edit_keterangan']);
    $metode_pembayaran = mysqli_real_escape_string($conn, $_POST['edit_metode_pembayaran']);

    $query_update = "UPDATE pengeluaran SET
                     tanggal = ?,
                     kategori = ?,
                     jumlah = ?,
                     keterangan = ?,
                     metode_pembayaran = ?
                     WHERE id_pengeluaran = ?";

    $stmt = mysqli_prepare($conn, $query_update);
    mysqli_stmt_bind_param($stmt, "ssdssi", $tanggal, $kategori, $jumlah, $keterangan, $metode_pembayaran, $id);

    if (mysqli_stmt_execute($stmt)) {
        echo "<script>alert('Data pengeluaran berhasil diupdate.');</script>";
        echo "<script>window.location.href='index-kepala-keuangan.php';</script>";
    } else {
        echo "<script>alert('Error: " . mysqli_error($conn) . "');</script>";
    }
    mysqli_stmt_close($stmt);
}


// Handle edit hutang pegawai
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_hutang_pegawai'])) {
    $id = (int)$_POST['edit_hutang_id'];
    $pegawai_id = (int)$_POST['edit_hutang_pegawai_id'];
    $jumlah = (float)$_POST['edit_hutang_jumlah'];
    $keterangan = mysqli_real_escape_string($conn, $_POST['edit_hutang_keterangan']);
    $tanggal_hutang = mysqli_real_escape_string($conn, $_POST['edit_hutang_tanggal_hutang']);
    $status = mysqli_real_escape_string($conn, $_POST['edit_hutang_status']);
    $tanggal_lunas = null; // Default null

    if ($status == 'lunas' && !empty($_POST['edit_hutang_tanggal_lunas'])) {
        $tanggal_lunas = mysqli_real_escape_string($conn, $_POST['edit_hutang_tanggal_lunas']);
    } else if ($status == 'lunas') {
        // Jika status diubah ke lunas tapi tanggal lunas tidak diisi, gunakan tanggal hari ini
        $tanggal_lunas = date('Y-m-d');
    }
    // Jika status kembali ke 'belum lunas', set tanggal_lunas menjadi NULL di database
    if ($status == 'belum lunas') {
        $tanggal_lunas = null;
    }

    $query_update_hutang = "UPDATE hutang_pegawai SET
                            pegawai_id = ?,
                            jumlah = ?,
                            keterangan = ?,
                            tanggal_hutang = ?,
                            status = ?,
                            tanggal_lunas = ?
                            WHERE id = ?";

    $stmt = mysqli_prepare($conn, $query_update_hutang);
    // Tipe parameter: i (integer), d (double), s (string)
    // Untuk tanggal_lunas, jika null, gunakan s (string) dan kirim null
    if ($tanggal_lunas === null) {
        mysqli_stmt_bind_param($stmt, "idssssi", $pegawai_id, $jumlah, $keterangan, $tanggal_hutang, $status, $tanggal_lunas, $id);
    } else {
        mysqli_stmt_bind_param($stmt, "idssssi", $pegawai_id, $jumlah, $keterangan, $tanggal_hutang, $status, $tanggal_lunas, $id);
    }

    if (mysqli_stmt_execute($stmt)) {
        echo "<script>alert('Data hutang pegawai berhasil diupdate.');</script>";
        echo "<script>window.location.href='index-kepala-keuangan.php';</script>";
    } else {
        echo "<script>alert('Error: " . mysqli_error($conn) . "');</script>";
    }
    mysqli_stmt_close($stmt);
}

// Handle hapus hutang pegawai
if (isset($_GET['hapus_hutang'])) {
    $id = (int)$_GET['hapus_hutang'];
    $query_delete_hutang = "DELETE FROM hutang_pegawai WHERE id = ?";

    $stmt = mysqli_prepare($conn, $query_delete_hutang);
    mysqli_stmt_bind_param($stmt, "i", $id);

    if (mysqli_stmt_execute($stmt)) {
        echo "<script>alert('Data hutang pegawai berhasil dihapus.');</script>";
        echo "<script>window.location.href='index-kepala-keuangan.php';</script>";
    } else {
        echo "<script>alert('Error: " . mysqli_error($conn) . "');</script>";
    }
    mysqli_stmt_close($stmt);
}

// Handle hapus pengeluaran
if (isset($_GET['hapus_pengeluaran'])) {
    $id = (int)$_GET['hapus_pengeluaran'];
    $query_delete = "DELETE FROM pengeluaran WHERE id_pengeluaran = $id";

    if (mysqli_query($conn, $query_delete)) {
        echo "<script>alert('Data pengeluaran berhasil dihapus');</script>";
        echo "<script>window.location.href='index-kepala-keuangan.php';</script>";
    } else {
        echo "<script>alert('Error: " . mysqli_error($conn) . "');</script>";
    }
}

$query_pendapatan = "
    SELECT
        tanggal,
        '' AS no_transaksi,
        'Pemasukan' AS jenis,
        kategori,
        jumlah AS nominal,
        'selesai' AS status,
        '' AS nama_pelanggan
    FROM pendapatan
    
    UNION ALL
    
    SELECT
        p.tanggal_pesan AS tanggal,
        p.pesanan_id AS no_transaksi,
        'Pemasukan' AS jenis,
        'Penjualan Produk' AS kategori,
        p.total_harga AS nominal,
        p.status,
        pl.nama_pelanggan
    FROM pesanan p
    JOIN pelanggan pl ON p.pelanggan_id = pl.pelanggan_id
    WHERE p.status = 'selesai'
    ORDER BY tanggal DESC
";

$result_pendapatan = mysqli_query($conn, $query_pendapatan);

if (!$result_pendapatan) {
    die("Query data pendapatan gagal: " . mysqli_error($conn));
}

// Query untuk mendapatkan data pengeluaran
$query_pengeluaran = "
    SELECT
        id_pengeluaran,
        tanggal,
        'Pengeluaran' AS jenis,
        kategori,
        jumlah AS nominal,
        keterangan,
        metode_pembayaran
    FROM pengeluaran
    ORDER BY tanggal DESC
";

$result_pengeluaran = mysqli_query($conn, $query_pengeluaran);

if (!$result_pengeluaran) {
    die("Query data pengeluaran gagal: " . mysqli_error($conn));
}

// Hitung total pendapatan 30 hari terakhir dari pesanan yang selesai
// Ganti query_total_pendapatan dengan ini:
$query_total_pendapatan = "
    SELECT COALESCE(SUM(total_harga), 0) AS total
    FROM pesanan
    WHERE status = 'selesai' AND tanggal_pesan >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    
    UNION ALL
    
    SELECT COALESCE(SUM(jumlah), 0) AS total
    FROM pendapatan
    WHERE tanggal >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
";

$result_total_pendapatan = mysqli_query($conn, $query_total_pendapatan);

if (!$result_total_pendapatan) {
    die("Query total pendapatan gagal: " . mysqli_error($conn));
}

$total_pendapatan = 0;
if ($result_total_pendapatan = mysqli_query($conn, $query_total_pendapatan)) {
    while ($row = mysqli_fetch_assoc($result_total_pendapatan)) {
        $total_pendapatan += $row['total'];
    }
}
// Hitung total pengeluaran 30 hari terakhir
$query_total_pengeluaran = "
    SELECT COALESCE(SUM(jumlah), 0) AS total
    FROM pengeluaran
    WHERE tanggal >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
";
$result_total_pengeluaran = mysqli_query($conn, $query_total_pengeluaran);

if (!$result_total_pengeluaran) {
    die("Query total pengeluaran gagal: " . mysqli_error($conn));
}

$total_pengeluaran = 0;
if ($row = mysqli_fetch_assoc($result_total_pengeluaran)) {
    $total_pengeluaran = $row['total'];
}

$laba_bersih = $total_pendapatan - $total_pengeluaran;

// Handle form submission for new expense
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_pengeluaran'])) {
    $tanggal = mysqli_real_escape_string($conn, $_POST['tanggal']);
    $kategori = mysqli_real_escape_string($conn, $_POST['kategori']);
    $jumlah = (float)$_POST['jumlah'];
    $keterangan = mysqli_real_escape_string($conn, $_POST['keterangan']);
    $metode_pembayaran = mysqli_real_escape_string($conn, $_POST['metode_pembayaran']);

    $query_insert = "INSERT INTO pengeluaran (tanggal, kategori, jumlah, keterangan, metode_pembayaran)
                     VALUES ('$tanggal', '$kategori', $jumlah, '$keterangan', '$metode_pembayaran')";

    if (mysqli_query($conn, $query_insert)) {
        echo "<script>alert('Data pengeluaran berhasil ditambahkan');</script>";
        echo "<script>window.location.href='index-kepala-keuangan.php';</script>";
    } else {
        echo "<script>alert('Error: " . mysqli_error($conn) . "');</script>";
    }
}

// Query data pendapatan (from pendapatan table)
$query_pendapatan_table = "SELECT * FROM pendapatan ORDER BY tanggal DESC";
$result_pendapatan_table = mysqli_query($conn, $query_pendapatan_table);

if (!$result_pendapatan_table) {
    die("Query pendapatan table gagal: " . mysqli_error($conn));
}

// Query data pengeluaran
$query_pengeluaran_table = "SELECT * FROM pengeluaran ORDER BY tanggal DESC";
$result_pengeluaran_table = mysqli_query($conn, $query_pengeluaran_table);

if (!$result_pengeluaran_table) {
    die("Query pengeluaran table gagal: " . mysqli_error($conn));
}

// Query hutang pegawai
$query_hutang = "
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
    ORDER BY hp.tanggal_hutang DESC
";
// Hapus WHERE hp.status = 'belum lunas' jika Anda ingin menampilkan semua hutang (lunas dan belum lunas)
// Jika hanya ingin menampilkan yang belum lunas, tambahkan kembali:
// WHERE hp.status = 'belum lunas'

$result_hutang = mysqli_query($conn, $query_hutang);

if (!$result_hutang) {
    die("Query hutang pegawai gagal: " . mysqli_error($conn));
}

// Hitung total values dengan pengecekan yang lebih aman
$query_total_pendapatan_all = "
    SELECT COALESCE(SUM(total_harga), 0) AS total FROM pesanan WHERE status = 'selesai'
    UNION ALL
    SELECT COALESCE(SUM(jumlah), 0) AS total FROM pendapatan
";
$result_total_pendapatan_all = mysqli_query($conn, $query_total_pendapatan_all);
$total_pendapatan_all = 0;
if ($result_total_pendapatan_all && $row = mysqli_fetch_assoc($result_total_pendapatan_all)) {
    $total_pendapatan_all = $row['total'];
}

$query_total_pengeluaran_all = "SELECT COALESCE(SUM(jumlah), 0) AS total FROM pengeluaran";
$result_total_pengeluaran_all = mysqli_query($conn, $query_total_pengeluaran_all);
$total_pengeluaran_all = 0;
if ($result_total_pengeluaran_all && $row = mysqli_fetch_assoc($result_total_pengeluaran_all)) {
    $total_pengeluaran_all = $row['total'];
}

$query_total_hutang = "SELECT COALESCE(SUM(jumlah), 0) AS total FROM hutang_pegawai WHERE status = 'belum lunas'";
$result_total_hutang = mysqli_query($conn, $query_total_hutang);
$total_hutang = 0;
if ($result_total_hutang && $row = mysqli_fetch_assoc($result_total_hutang)) {
    $total_hutang = $row['total'];
}

$laba_bersih_all = $total_pendapatan_all - $total_pengeluaran_all;

// Handle form submission for income and debt
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['submit_pendapatan'])) {
        $tanggal = mysqli_real_escape_string($conn, $_POST['tanggal']);
        $kategori = mysqli_real_escape_string($conn, $_POST['kategori']);
        $jumlah = (float)$_POST['jumlah'];
        $keterangan = mysqli_real_escape_string($conn, $_POST['keterangan']);

        // Insert pendapatan
        $query = "INSERT INTO pendapatan (tanggal, kategori, jumlah, keterangan)
                 VALUES ('$tanggal', '$kategori', $jumlah, '$keterangan')";

        if (mysqli_query($conn, $query)) {
            // Jika pembayaran hutang
           if ($kategori == 'Pembayaran Hutang' && isset($_POST['pegawai_id'])) { // <-- Perhatikan ini
                $pegawai_id = (int)$_POST['pegawai_id']; // <-- ini harusnya 'pegawai_id_hutang' dari modal pendapatan
                $jumlah_hutang = (float)$_POST['jumlah_hutang'];

                // Update hutang pegawai
                $query_update = "UPDATE hutang_pegawai
                                SET jumlah = jumlah - $jumlah_hutang
                                WHERE pegawai_id = $pegawai_id";

                if (mysqli_query($conn, $query_update)) {
                    // Cek jika hutang lunas
                    $query_check = "SELECT jumlah FROM hutang_pegawai WHERE pegawai_id = $pegawai_id";
                    $result_check = mysqli_query($conn, $query_check);

                    if ($result_check && $row = mysqli_fetch_assoc($result_check)) {
                        if ($row['jumlah'] <= 0) {
                            mysqli_query($conn, "UPDATE hutang_pegawai
                                                SET status = 'lunas',
                                                    tanggal_lunas = NOW()
                                                WHERE pegawai_id = $pegawai_id"); // <-- Ini yang Anda cari!
                        }
                    }
                }
            }

            echo "<script>alert('Data pendapatan berhasil ditambahkan');</script>";
            echo "<script>window.location.href='index-kepala-keuangan.php';</script>";
        } else {
            echo "<script>alert('Error: " . mysqli_error($conn) . "');</script>";
        }
    }

   if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tambah_hutang_pegawai'])) {
        $pegawai_id = (int)$_POST['pegawai_id_hutang']; // Ambil langsung dari dropdown
        $jumlah = (float)$_POST['jumlah_hutang_pegawai'];
        $keterangan = mysqli_real_escape_string($conn, $_POST['keterangan_hutang_pegawai']);
        $tanggal_hutang = date('Y-m-d'); // Tanggal hutang otomatis hari ini
        $status = 'belum lunas'; // Status otomatis

        $query_insert_hutang = "INSERT INTO hutang_pegawai (pegawai_id, jumlah, keterangan, tanggal_hutang, status) VALUES (?, ?, ?, ?, ?)";

        // Menggunakan prepared statements untuk keamanan
        $stmt = mysqli_prepare($conn, $query_insert_hutang);
        mysqli_stmt_bind_param($stmt, "idsss", $pegawai_id, $jumlah, $keterangan, $tanggal_hutang, $status);

        if (mysqli_stmt_execute($stmt)) {
            echo "<script>alert('Data hutang pegawai berhasil ditambahkan.');</script>";
            echo "<script>window.location.href='index-kepala-keuangan.php';</script>";
        } else {
            echo "<script>alert('Error: " . mysqli_error($conn) . "');</script>";
        }
        mysqli_stmt_close($stmt);
    }
}
$reminders_hutang = []; // Array untuk menyimpan notifikasi hutang

$query_reminder_hutang = "
    SELECT
        hp.id,
        u.username AS nama_pegawai,
        hp.jumlah,
        hp.tanggal_hutang,
        hp.keterangan
    FROM hutang_pegawai hp
    JOIN users u ON hp.pegawai_id = u.user_id
    WHERE hp.status = 'belum lunas'
    ORDER BY hp.tanggal_hutang ASC -- Urutkan dari yang paling lama dulu
";

$stmt_reminder = mysqli_prepare($conn, $query_reminder_hutang);
if ($stmt_reminder) {
    mysqli_stmt_execute($stmt_reminder);
    $result_reminder = mysqli_stmt_get_result($stmt_reminder);

    while ($row = mysqli_fetch_assoc($result_reminder)) {
        // Hitung berapa hari hutang sudah berjalan
        $tanggal_hutang_obj = new DateTime($row['tanggal_hutang']);
        $today_obj = new DateTime();
        $interval = $today_obj->diff($tanggal_hutang_obj);
        $row['days_old'] = $interval->days; // Tambahkan informasi usia hutang ke array

        $reminders_hutang[] = $row;
    }
    mysqli_stmt_close($stmt_reminder);
} else {
    error_log("Gagal menyiapkan query reminder hutang: " . mysqli_error($conn));
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Data Keuangan</title>
    <link rel="stylesheet" href="../css/data-keuangan.css">
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
            <li><a href="index-kepala-keuangan.php"><i class='bx bx-user' ></i></i><span>Data Keuangan</span></a></li>
            <li><a href="../pages/penjualan-kepala-keuangan.php"><i class='bx bx-cart'></i><span>Data Penjualan</span></a></li>
            <li><a href="../pages/supplier-kepala-keuangan.php"><i class='bx bx-store-alt'></i><span>Data Supplier</span></a></li>
            <li><a href="../pages/data-pembelian-kepala-keuangan.php"><i class='bx bx-dollar'></i><span>Data Pembelian</span></a></li>
            <li>
                <form action="logout.php" method="POST">
                    <button type="submit" class="btn-logout-sidebar"><span>Logout</span></button>
                </form>
        </ul>
    </aside>
    <div class="beranda">
        <br><br>

         <div class="container-card">
            <div class="card" style="background-color: #ebedff;">
                <h3><?= number_format($total_pendapatan, 0, ',', '.') ?></h3>
                <p>Total Pendapatan (30 hari)</p>
                <i class='bx bx-calendar fa-2xl'></i>
            </div>
            <div class="card" style="background-color: #ebedff;">
                <h3><?= number_format($total_pengeluaran, 0, ',', '.') ?></h3>
                <p>Total Pengeluaran (30 hari)</p>
                <i class='bx bx-loader-circle fa-2xl' ></i>
            </div>
            <div class="card" style="background-color: #ebedff;">
                <h3><?= number_format($laba_bersih, 0, ',', '.') ?></h3>
                <p>Laba Bersih (30 hari)</p>
                <i class='bx bx-check-circle fa-2xl'></i>
            </div>
            <div class="card" style="background-color: #ebedff;">
                <h3><?= number_format($total_hutang, 0, ',', '.') ?></h3>
                <p>Hutang Pegawai</p>
                <i class='bx bx-user'></i>
            </div>
        </div>
        <br><br>

        <div class="data-table-container">
            <div class="table-header">
                <h2>Data Pendapatan</h2>
                <div class="table-controls">
                    <div class="action-buttons">
                        <button class="btn primary" id="btnTambahPendapatan">+ Tambah Pendapatan</button>
                        <button class="btn warning" id="btnTambahHutang">+ Tambah Hutang</button>
                    </div>
                </div>
            </div>

            <table class="data-table" id="tablePendapatan">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>No. Transaksi</th>
                        <th>Pelanggan</th>
                        <th>Jenis</th>
                        <th>Kategori</th>
                        <th>Nominal</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($result_pendapatan)): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['tanggal']) ?></td>
                            <td><?= htmlspecialchars($row['no_transaksi']) ?></td>
                            <td><?= htmlspecialchars($row['nama_pelanggan']) ?></td>
                            <td><?= htmlspecialchars($row['jenis']) ?></td>
                            <td><?= htmlspecialchars($row['kategori']) ?></td>
                            <td>Rp <?= number_format($row['nominal'], 0, ',', '.') ?></td>
                            <td>
                                <span class="status <?= strtolower($row['status']) ?>">
                                    <?= htmlspecialchars($row['status']) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <br>
            <div class="table-footer">
                <div class="pagination">
                    <button class="btn" id="prevPagePendapatan">Previous</button>
                    <span id="pageInfoPendapatan">Page 1 of 1</span>
                    <button class="btn" id="nextPagePendapatan">Next</button>
                </div>
            </div>
            <br>
        </div>

        <br><br>

        <div class="data-table-container">
            <div class="table-header">
                <h2>Data Pengeluaran</h2>
                <div class="table-controls">
                    <button id="btnTambahPengeluaran" class="btn danger">+ Tambah Pengeluaran</button>
                </div>
            </div>

            <table class="data-table" id="tablePengeluaran">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>ID</th>
                        <th>Jenis</th>
                        <th>Kategori</th>
                        <th>Nominal</th>
                        <th>Keterangan</th>
                        <th>Metode Pembayaran</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($result_pengeluaran)): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['tanggal']) ?></td>
                            <td><?= htmlspecialchars($row['id_pengeluaran']) ?></td>
                            <td><?= htmlspecialchars($row['jenis']) ?></td>
                            <td><?= htmlspecialchars($row['kategori']) ?></td>
                            <td>Rp <?= number_format($row['nominal'], 0, ',', '.') ?></td>
                            <td><?= htmlspecialchars($row['keterangan']) ?></td>
                            <td><?= htmlspecialchars($row['metode_pembayaran']) ?></td>
                            <td>
                                <button class="btn small primary edit-btn" data-id="<?= $row['id_pengeluaran'] ?>">Edit</button>
                                <button class="btn small danger delete-btn" data-id="<?= $row['id_pengeluaran'] ?>">Hapus</button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <br>
            <div class="table-footer">
                <div class="pagination">
                    <button class="btn" id="prevPagePengeluaran">Previous</button>
                    <span id="pageInfoPengeluaran">Page 1 of 1</span>
                    <button class="btn" id="nextPagePengeluaran">Next</button>
                </div>
            </div><br>
        </div>
        <div class="data-table-container">
            <div class="table-header">
                <h2>Data Hutang Pegawai</h2>
            </div>

            <table class="data-table" id="tableHutang">
                <thead>
                    <tr>
                        <th>ID Hutang</th>
                        <th>Nama Pegawai</th>
                        <th>Jumlah Hutang</th>
                        <th>Keterangan</th>
                        <th>Tanggal Hutang</th>
                        <th>Tanggal Lunas</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (mysqli_num_rows($result_hutang) > 0) {
                        while ($row_hutang = mysqli_fetch_assoc($result_hutang)):
                    ?>
                        <tr>
                            <td><?= htmlspecialchars($row_hutang['id']) ?></td>
                            <td><?= htmlspecialchars($row_hutang['nama_pegawai']) ?></td>
                            <td>Rp <?= number_format($row_hutang['jumlah'], 0, ',', '.') ?></td>
                            <td><?= htmlspecialchars($row_hutang['keterangan']) ?></td>
                            <td><?= htmlspecialchars($row_hutang['tanggal_hutang']) ?></td>
                            <td><?= htmlspecialchars($row_hutang['tanggal_lunas'] ?? '-') ?></td> <td>
                                <span class="status <?= str_replace(' ', '-', strtolower($row_hutang['status'])) ?>">
                                    <?= htmlspecialchars($row_hutang['status']) ?>
                                </span>
                            </td>
                            <td>
                                <button class="btn small primary edit-hutang-btn" data-id="<?= $row_hutang['id'] ?>">Edit</button>
                                <button class="btn small danger delete-hutang-btn" data-id="<?= $row_hutang['id'] ?>">Hapus</button>
                            </td>
                        </tr>
                    <?php
                        endwhile;
                    } else {
                        echo "<tr><td colspan='8'>Tidak ada data hutang pegawai.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
            <br>
            <div class="table-footer">
                <div class="pagination">
                    <button class="btn" id="prevPageHutang">Previous</button>
                    <span id="pageInfoHutang">Page 1 of 1</span>
                    <button class="btn" id="nextPageHutang">Next</button>
                </div>
            </div>
            <br>
        </div>

    </div>
</div>
<div id="pengeluaranModal" class="modal">
    <div class="modal-content">
        <span class="close-modal">&times;</span>
        <h2>Tambah Data Pengeluaran</h2>
        <form method="POST" action="">
            <div class="form-group">
                <label for="tanggal">Tanggal</label>
                <input type="date" id="tanggal" name="tanggal" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="kategori">Kategori</label>
                <select id="kategori" name="kategori" class="form-control" required>
                    <option value="">Pilih Kategori</option>
                    <option value="Pembelian Bahan Baku">Pembelian Bahan Baku</option>
                    <option value="Gaji Pegawai">Gaji Pegawai</option>
                    <option value="Biaya Operasional">Biaya Operasional</option>
                    <option value="Pemeliharaan">Pemeliharaan</option>
                    <option value="Lainnya">Lainnya</option>
                </select>
            </div>
            <div class="form-group">
                <label for="jumlah">Jumlah (Rp)</label>
                <input type="number" id="jumlah" name="jumlah" min="0" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="keterangan">Keterangan</label>
                <textarea id="keterangan" name="keterangan" rows="2" class="form-control"></textarea>
            </div>
            <div class="form-group">
                <label for="metode_pembayaran">Metode Pembayaran</label>
                <select id="metode_pembayaran" name="metode_pembayaran" class="form-control" required>
                    <option value="Tunai">Tunai</option>
                    <option value="Transfer Bank">Transfer Bank</option>
                    <option value="Kartu Kredit">Kartu Kredit</option>
                </select>
            </div>
            <div class="modal-buttons"> <button type="submit" name="submit_pengeluaran" class="btn primary">Simpan</button>
            </div>
        </form>
    </div>
</div>
<div id="editModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Edit Data Pengeluaran</h2>
        <form id="editForm" method="POST">
            <input type="hidden" id="edit_id" name="edit_id">
            <div class="form-group">
                <label for="edit_tanggal">Tanggal</label>
                <input type="date" id="edit_tanggal" name="edit_tanggal" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="edit_kategori">Kategori</label>
                <select id="edit_kategori" name="edit_kategori" class="form-control" required>
                    <option value="Pembelian Bahan Baku">Pembelian Bahan Baku</option>
                    <option value="Gaji Pegawai">Gaji Pegawai</option>
                    <option value="Biaya Operasional">Biaya Operasional</option>
                    <option value="Pemeliharaan">Pemeliharaan</option>
                    <option value="Lainnya">Lainnya</option>
                </select>
            </div>
            <div class="form-group">
                <label for="edit_jumlah">Jumlah (Rp)</label>
                <input type="number" id="edit_jumlah" name="edit_jumlah" min="0" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="edit_keterangan">Keterangan</label>
                <textarea id="edit_keterangan" name="edit_keterangan" rows="2" class="form-control"></textarea>
            </div>
            <div class="form-group">
                <label for="edit_metode_pembayaran">Metode Pembayaran</label>
                <select id="edit_metode_pembayaran" name="edit_metode_pembayaran" class="form-control" required>
                    <option value="Tunai">Tunai</option>
                    <option value="Transfer Bank">Transfer Bank</option>
                    <option value="Kartu Kredit">Kartu Kredit</option>
                </select>
            </div>
            <div class="modal-buttons"> <button type="submit" name="update_pengeluaran" class="btn primary">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>
<div id="modalPendapatan" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Tambah Pendapatan</h2>
        <form method="POST" action="">
            <div class="form-group">
                <label for="tanggal">Tanggal</label>
                <input type="date" name="tanggal" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="kategoriPendapatan">Kategori</label>
                <select name="kategori" id="kategoriPendapatan" class="form-control" required>
                    <option value="">Pilih Kategori</option>
                    <option value="Penjualan Produk">Penjualan Produk</option>
                    <option value="Pembayaran Hutang">Pembayaran Hutang</option>
                    <option value="Lainnya">Lainnya</option>
                </select>
            </div>

            <div id="hutangFields" style="display:none;">
               <div class="form-group">
                    <label for="pegawaiIdHutang">Nama Pegawai</label>
                    <select class="form-control" id="pegawaiIdHutang" name="pegawai_id_hutang">
                        <option value="">Pilih Pegawai</option>
                        <?php foreach ($pegawai_list as $pegawai): ?>
                            <option value="<?= $pegawai['user_id'] ?>"><?= htmlspecialchars($pegawai['username']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="jumlah_hutang">Jumlah Pembayaran Hutang</label>
                    <input type="number" name="jumlah_hutang" id="jumlah_hutang" min="0" class="form-control">
                </div>
            </div>

            <div class="form-group">
                <label for="jumlah">Jumlah (Rp)</label>
                <input type="number" name="jumlah" class="form-control" required min="0">
            </div>
            <div class="form-group">
                <label for="keterangan">Keterangan</label>
                <textarea name="keterangan" class="form-control" required></textarea>
            </div>
            <div class="modal-buttons"> <button type="submit" name="submit_pendapatan" class="btn primary">Simpan</button>
            </div>
        </form>
    </div>
</div>
<div id="modalHutang" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Tambah Hutang Pegawai</h2>
        <form method="POST" action="">
            <div class="form-group">
                <label for="pegawaiIdHutang">Nama Pegawai</label>
                <select class="form-control" id="pegawaiIdHutang" name="pegawai_id_hutang" required>
                    <option value="">Pilih Pegawai</option>
                    <?php
                    if (!empty($pegawai_list)) {
                        foreach ($pegawai_list as $pegawai):
                    ?>
                        <option value="<?= $pegawai['user_id'] ?>"><?= htmlspecialchars($pegawai['username']) ?></option>
                    <?php
                        endforeach;
                    } else {
                        echo "<option value=''>Tidak ada data pegawai ditemukan</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="form-group">
                <label for="jumlahHutangPegawai">Jumlah Hutang</label>
                <input type="number" step="0.01" class="form-control" id="jumlahHutangPegawai" name="jumlah_hutang_pegawai" required>
            </div>
            <div class="form-group">
                <label for="keteranganHutangPegawai">Keterangan</label>
                <textarea class="form-control" id="keteranganHutangPegawai" name="keterangan_hutang_pegawai"></textarea>
            </div>
            <div class="modal-buttons"> <button type="submit" name="tambah_hutang_pegawai" class="btn primary">Simpan Hutang</button>
            </div>
        </form>
    </div>
</div>
<div id="editHutangModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Edit Data Hutang Pegawai</h2>
        <form id="editHutangForm" method="POST">
            <input type="hidden" id="edit_hutang_id" name="edit_hutang_id">
            <div class="form-group">
                <label for="edit_hutang_pegawai_id">Nama Pegawai</label>
                <select class="form-control" id="edit_hutang_pegawai_id" name="edit_hutang_pegawai_id" required>
                    <option value="">Pilih Pegawai</option>
                    <?php foreach ($pegawai_list as $pegawai): ?>
                        <option value="<?= $pegawai['user_id'] ?>"><?= htmlspecialchars($pegawai['username']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="edit_hutang_jumlah">Jumlah Hutang</label>
                <input type="number" step="0.01" class="form-control" id="edit_hutang_jumlah" name="edit_hutang_jumlah" required>
            </div>
            <div class="form-group">
                <label for="edit_hutang_keterangan">Keterangan</label>
                <textarea class="form-control" id="edit_hutang_keterangan" name="edit_hutang_keterangan"></textarea>
            </div>
            <div class="form-group">
                <label for="edit_hutang_tanggal_hutang">Tanggal Hutang</label>
                <input type="date" class="form-control" id="edit_hutang_tanggal_hutang" name="edit_hutang_tanggal_hutang" required>
            </div>
            <div class="form-group">
                <label for="edit_hutang_status">Status</label>
                <select class="form-control" id="edit_hutang_status" name="edit_hutang_status" required>
                    <option value="belum lunas">Belum Lunas</option>
                    <option value="lunas">Lunas</option>
                </select>
            </div>
            <div class="form-group" id="edit_hutang_tanggal_lunas_group" style="display:none;">
                <label for="edit_hutang_tanggal_lunas">Tanggal Lunas</label>
                <input type="date" class="form-control" id="edit_hutang_tanggal_lunas" name="edit_hutang_tanggal_lunas">
            </div>
            <div class="modal-buttons"> <button type="submit" name="update_hutang_pegawai" class="btn primary">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<div id="deleteHutangModal" class="modal">
    <div class="modal-content" style="max-width: 500px;">
        <span class="close">&times;</span>
        <h2>Konfirmasi Hapus Hutang</h2>
        <p>Apakah Anda yakin ingin menghapus data hutang pegawai ini?</p>
        <div class="form-group" style="display: flex; justify-content: space-between; margin-top: 20px;">
            <button class="btn danger" id="confirmDeleteHutang">Ya, Hapus</button>
            <button class="btn" id="cancelDeleteHutang">Batal</button>
        </div>
    </div>
</div>

<div id="deleteModal" class="modal">
    <div class="modal-content" style="max-width: 500px;">
        <span class="close">&times;</span>
        <h2>Konfirmasi Hapus</h2>
        <p>Apakah Anda yakin ingin menghapus data pengeluaran ini?</p>
        <div class="form-group" style="display: flex; justify-content: space-between; margin-top: 20px;">
            <button class="btn danger" id="confirmDelete">Ya, Hapus</button>
            <button class="btn" id="cancelDelete">Batal</button>
        </div>
    </div>
</div>
<div id="hutangReminderModal" class="modal">
    <div class="modal-content" style="max-width: 600px;">
        <span class="close" id="closeHutangReminderModal">&times;</span>
        <h2 style="color: #cc0000; text-align: center;"><i class='bx bxs-bell' style="vertical-align: middle; margin-right: 10px;"></i>Peringatan Hutang Pegawai!</h2>
        <p style="text-align: center; margin-bottom: 20px;">Berikut daftar hutang pegawai yang belum lunas:</p>

        <?php if (!empty($reminders_hutang)): ?>
            <div class="hutang-reminder-list">
                <ul>
                    <?php foreach ($reminders_hutang as $hutang): ?>
                        <li>
                            <div class="hutang-item-header">
                                <strong><?= htmlspecialchars($hutang['nama_pegawai']) ?></strong>
                                <span>Rp <?= number_format($hutang['jumlah'], 0, ',', '.') ?></span>
                            </div>
                            <div class="hutang-item-detail">
                                Tanggal Hutang: <?= htmlspecialchars($hutang['tanggal_hutang']) ?>
                                (Sudah berjalan **<?= $hutang['days_old'] ?>** hari)
                                <?php if (!empty($hutang['keterangan'])): ?>
                                    <br>Keterangan: <?= htmlspecialchars($hutang['keterangan']) ?>
                                <?php endif; ?>
                            </div>
                            <button class="btn btn-sm primary edit-hutang-btn" data-id="<?= $hutang['id'] ?>" style="margin-top: 10px;">Kelola Hutang</button>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <p style="text-align: center; margin-top: 20px; font-size: 0.9em; color: #666;">
                *Klik "Kelola Hutang" untuk mengedit atau menandai hutang sebagai lunas.
            </p>
        <?php else: ?>
            <p style="text-align: center; color: #4CAF50;">Tidak ada hutang yang membutuhkan perhatian saat ini. Semua terkendali!</p>
        <?php endif; ?>

        <div style="text-align: center; margin-top: 30px;">
            <button class="btn" id="understoodHutangReminder">Mengerti</button>
        </div>
    </div>
</div>


<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- HAPUS BARIS DI BAWAH INI (DUPLIKAT JQUERY)
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
-->
<script src="../javascript/index-keuangan-kepala-keuangan.js"></script>
<!-- HAPUS SEMUA KODE JAVASCRIPT DI BAWAH INI (DUPLIKAT DENGAN data-keuangan.js)
<script>
$(document).ready(function() {
    // ... KODE JAVASCRIPT DUPLIKAT YANG SAMA DENGAN data-keuangan.js ...
});
</script>
-->
<script>
document.addEventListener("DOMContentLoaded", function () {
  const btnPengeluaran = document.getElementById("btnTambahPengeluaran");
  const modalPengeluaran = document.getElementById("modalPengeluaran");

  if (btnPengeluaran && modalPengeluaran) {
    btnPengeluaran.addEventListener("click", function () {
      modalPengeluaran.style.display = "block";
      document.body.style.overflow = "hidden";
    });
  } else {
    console.error("Tombol atau modal pengeluaran tidak ditemukan");
  }

  // Klik di luar modal untuk menutup
  window.addEventListener("click", function (event) {
    if (event.target === modalPengeluaran) {
      modalPengeluaran.style.display = "none";
      document.body.style.overflow = "auto";
    }
  });
});
</script>

</body>
</html>
