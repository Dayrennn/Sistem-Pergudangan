<?php
session_start();

$username = isset($_SESSION['username']) ? $_SESSION['username'] : 'Guest';
$role = isset($_SESSION['role']) ? $_SESSION['role'] : 'Unknown';   
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}
require_once 'koneksi.php';

// Ambil stok barang tertinggi (dari tabel 'barang')
$query_stok_barang_tertinggi = mysqli_query($conn, "SELECT MAX(stok) AS stok_tertinggi FROM barang") or die("Error stok barang tertinggi: " . mysqli_error($conn));
$data_stok_barang_tertinggi = mysqli_fetch_assoc($query_stok_barang_tertinggi);
$stok_barang_tertinggi = $data_stok_barang_tertinggi ? $data_stok_barang_tertinggi['stok_tertinggi'] : 0;

// Ambil stok barang terendah (dari tabel 'barang')
$query_stok_barang_terendah = mysqli_query($conn, "SELECT MIN(stok) AS stok_terendah FROM barang") or die("Error stok barang terendah: " . mysqli_error($conn));
$data_stok_barang_terendah = mysqli_fetch_assoc($query_stok_barang_terendah);
$stok_barang_terendah = $data_stok_barang_terendah ? $data_stok_barang_terendah['stok_terendah'] : 0;

// Ambil stok produk tertinggi (dari tabel 'produk_jadi')
// Menggunakan 'stok' sebagai nama kolom
$query_stok_produk_tertinggi = mysqli_query($conn, "SELECT MAX(stok) AS stok_tertinggi FROM produk_jadi") or die("Error stok produk tertinggi: " . mysqli_error($conn));
$data_stok_produk_tertinggi = mysqli_fetch_assoc($query_stok_produk_tertinggi);
$stok_produk_tertinggi = $data_stok_produk_tertinggi ? $data_stok_produk_tertinggi['stok_tertinggi'] : 0;

// Ambil stok produk terendah (dari tabel 'produk_jadi')
// Menggunakan 'stok' sebagai nama kolom
$query_stok_produk_terendah = mysqli_query($conn, "SELECT MIN(stok) AS stok_terendah FROM produk_jadi") or die("Error stok produk terendah: " . mysqli_error($conn));
$data_stok_produk_terendah = mysqli_fetch_assoc($query_stok_produk_terendah);
$stok_produk_terendah = $data_stok_produk_terendah ? $data_stok_produk_terendah['stok_terendah'] : 0;

// Cek koneksi
if (!$conn) {
    die("Koneksi database gagal");
}

$statusCounts = [
    'Terjadwal' => 0,
    'Dalam Proses' => 0,
    'Selesai' => 0,
    'Dibatalkan' => 0
];

$query_status = mysqli_query($conn, "SELECT status, COUNT(*) as jumlah FROM penjadwalan_pembelian GROUP BY status") or die("Error status: " . mysqli_error($conn));

while ($row = mysqli_fetch_assoc($query_status)) {
    $status = $row['status'];
    $jumlah = $row['jumlah'];
    if (isset($statusCounts[$status])) {
        $statusCounts[$status] = $jumlah;
    }
}

// Ambil data penjadwalan pembelian untuk tabel
$jadwal = mysqli_query($conn, "SELECT p.id_pembelian, s.nama_supplier, p.barang_supplier, p.jumlah, p.tanggal, p.status, p.supplier_id 
                               FROM penjadwalan_pembelian p
                               JOIN supplier s ON p.supplier_id = s.supplier_id")
          or die("Error jadwal: " . mysqli_error($conn));

// Ambil jumlah data pembelian terjadwal
$query_pembelian = mysqli_query($conn, "SELECT COUNT(*) as total FROM penjadwalan_pembelian") or die("Error pembelian: " . mysqli_error($conn));
$data_pembelian = mysqli_fetch_assoc($query_pembelian);
$total_pembelian = $data_pembelian ? $data_pembelian['total'] : 0;


// Ambil jumlah data barang
$query_barang = mysqli_query($conn, "SELECT COUNT(*) as total FROM barang") or die("Error barang: " . mysqli_error($conn));
$data_barang = mysqli_fetch_assoc($query_barang);
$total_barang = $data_barang ? $data_barang['total'] : 0;

// Ambil jumlah data penjualan dari tabel pesanan (seperti di pelanggan.php)
$query_penjualan = mysqli_query($conn, "SELECT COUNT(*) as total FROM pesanan") or die("Error penjualan: " . mysqli_error($conn));
$data_penjualan = mysqli_fetch_assoc($query_penjualan);
$total_penjualan = $data_penjualan ? $data_penjualan['total'] : 0;

// Ambil jumlah data supplier
$query_supplier = mysqli_query($conn, "SELECT COUNT(*) as total FROM supplier") or die("Error supplier: " . mysqli_error($conn));
$data_supplier = mysqli_fetch_assoc($query_supplier);
$total_supplier = $data_supplier ? $data_supplier['total'] : 0;

// Data penjualan per bulan (asumsi ada kolom 'tanggal' di tabel pesanan)
$penjualan_per_bulan = [];
$query_per_bulan = mysqli_query($conn, "
    SELECT MONTH(tanggal_pesan) as bulan, COUNT(*) as total 
    FROM pesanan 
    GROUP BY MONTH(tanggal_pesan)
") or die("Error grafik penjualan: " . mysqli_error($conn));


while ($row = mysqli_fetch_assoc($query_per_bulan)) {
    $penjualan_per_bulan[(int)$row['bulan']] = $row['total'];
}

// Lengkapi data agar 12 bulan selalu terisi (0 jika kosong)
for ($i = 1; $i <= 12; $i++) {
    if (!isset($penjualan_per_bulan[$i])) {
        $penjualan_per_bulan[$i] = 0;
    }
}
ksort($penjualan_per_bulan);

$query_barang_detail = mysqli_query($conn, "SELECT nama_barang, harga, stok FROM barang") or die("Error data barang: " . mysqli_error($conn));

$query_pelanggan = mysqli_query($conn, "
    SELECT pl.*, ps.pesanan_id, ps.barang_id, pj.nama_produk, pj.harga_jual, ps.jumlah_pesan, ps.total_harga, ps.tanggal_pesan, ps.status 
    FROM pelanggan pl
    JOIN pesanan ps ON pl.pelanggan_id = ps.pelanggan_id
    JOIN produk_jadi pj ON ps.barang_id = pj.id_produk_jadi
") or die("Error pelanggan: " . mysqli_error($conn));


?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WEB PENYIMPANAN BARANG</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter&family=Open+Sans&family=Poppins:wght@400;600&display=swap"rel="stylesheet">
    <link rel="stylesheet" href="../css/index-staff-gudang.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    

</head>

<body>

    <!-- HEADER START-->
    <div id="content">
        <nav class="navbar">
            
            <div class="profile-company">
                <img src="../assets/logo.png" alt="Logo">
            </div>
        </nav>
    
        <!-- HEADER END -->


        <div class="container">
            <!-- SIDEBAR -->
            <aside class="sidebar" id="sidebar">

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
                    <li><a href="index-staff-gudang.php"><i class='bx bx-home-alt-2'></i><span>Dashboard</span></a></li>
                    <li><a href="../pages/barang-staff-gudang.php"><i class='bx bx-package'></i><span>Data Barang</span></a></li>
                    <li><a href="../pages/penjualan-staff-gudang.php"><i class='bx bx-cart'></i><span>Data Penjualan</span></a></li>
                    <li><a href="../pages/pelanggan-staff-gudang.php"><i class='bx bx-cart'></i><span>Data Pelanggan</span></a></li>
                    <li><a href="../pages/penjadwalan-pembelian-staff-gudang.php"><i class='bx bx-user' ></i></i><span>Penjadwalan Pembelian</span></a></li>
                    <li><a href="../pages/data-pembelian-staff-gudang.php"><i class='bx bx-dollar'></i><span>Data Pembelian</span></a></li>
                    <li>
                        <form action="logout.php" method="POST">
                            <button type="submit" class="btn-logout-sidebar"><span>Logout</span></button>
                        </form>
                    </li>
                </ul>
            </aside>



            <!-- HOME / MAIN CONTENT -->
             
            
             <div class="beranda">
                <br><br>
                <h2>Dashboard</h2>

                <div class="container-card"> <div class="card" style="background-color: #ebedff;">
                        <h3><?= $stok_barang_tertinggi ?></h3>
                        <p>Stok Barang Tertinggi</p>
                        <i class='bx bx-up-arrow-alt fa-2xl'></i>
                    </div>
                    <div class="card" style="background-color: #ebedff;">
                        <h3><?= $stok_barang_terendah ?></h3>
                        <p>Stok Barang Terendah</p>
                        <i class='bx bx-down-arrow-alt fa-2xl' ></i>
                    </div>
                    <div class="card" style="background-color: #ebedff;">
                        <h3><?= $stok_produk_tertinggi ?></h3>
                        <p>Stok Produk Tertinggi</p>
                        <i class='bx bx-up-arrow-alt fa-2xl'></i>
                    </div>
                    <div class="card" style="background-color: #ebedff;">
                        <h3><?= $stok_produk_terendah ?></h3>
                        <p>Stok Produk Terendah</p>
                        <i class='bx bx-down-arrow-alt fa-2xl'></i>
                    </div>
                </div>
                <br><br>
                <div class="box">
                    <div class="data-grafik-container">
                        <br>
                        <div style="flex: 1;">
                            <h2>Data Produk</h2>
                            <table class="data-table" style="width: 660px; margin: 15px;">
                                <thead>
                                    <tr>
                                        <th>ID Produk</th>
                                        <th>Nama Produk</th>
                                        <th>Stok</th>
                                        <th>Tanggal Produksi</th>
                                        <th>Harga Jual</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $query_produk_jadi = mysqli_query($conn, "SELECT * FROM produk_jadi") or die("Error produk_jadi: " . mysqli_error($conn)); // Query ini sudah ada di bagian atas, ini hanya memastikan loopnya
                                    while ($row = mysqli_fetch_assoc($query_produk_jadi)) {
                                        $stok = $row['stok'];
                                        if ($stok == 0) {
                                            $status = '<span class="status-habis">Habis</span>';
                                        } elseif ($stok < 100) {
                                            $status = '<span class="status-menipis">Stok Menipis</span>';
                                        } else {
                                            $status = '<span class="status-tersedia">Tersedia</span>';
                                        }
                                    ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($row['id_produk_jadi']); ?></td>
                                            <td><?php echo htmlspecialchars($row['nama_produk']); ?></td>
                                            <td><?php echo $stok; ?></td>
                                            <td><?php echo htmlspecialchars($row['tanggal_produksi']); ?></td>
                                            <td><?php echo number_format($row['harga_jual'], 0, ',', '.'); ?></td>
                                            <td><?php echo $status; ?></td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table> <br>
                            <a href="../pages/barang-staff-gudang.php" style="margin-left:20px;">Lihat Selengkapnya</a>
                        </div>
                    </div>
                    <div class="data-jual-container">
                            <div style="flex: 1;">
                                <br>
                                <h2>Data Barang</h2>
                                <table class="data-table" style="width: 100%; margin: 15px;">
                                    <thead>
                                        <tr>
                                            <th>Nama Barang</th>
                                            <th>Harga</th>
                                            <th>Stok</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($row = mysqli_fetch_assoc($query_barang_detail)) { ?>
                                            <tr>
                                                <td><?= htmlspecialchars($row['nama_barang']) ?></td>
                                                <td><?= htmlspecialchars($row['harga']) ?></td>
                                                <td><?= htmlspecialchars($row['stok']) ?></td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                                <a href="../pages/penjadwalan-pembelian-staff-gudang.php" style="margin-left:20px;">Lihat Selengkapnya</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
    </div>

    <script> src="../javascript/index-direktur.js"</script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
function updateStats() {
    $.ajax({
        url: 'get_stats.php',
        method: 'GET',
        dataType: 'json',
        success: function(data) {
            $('div.card').eq(0).find('h3').text(data.total_barang);
            $('div.card').eq(1).find('h3').text(data.total_penjualan);
            $('div.card').eq(2).find('h3').text(data.total_supplier);
        }
    });
}

// Panggil sekali saat halaman dimuat
updateStats();

// Update tiap 5 detik (5000 ms)
setInterval(updateStats, 5000);
</script>

<script>
function loadStockStats() {
  $.ajax({
    url: "get_stock_stats.php",
    method: "GET",
    dataType: "json",
    success: function (data) {
      $("#stok-tertinggi").text(data.stok_tertinggi);
      $("#stok-terendah").text(data.stok_terendah);
    },
    error: function () {
      console.error("Gagal mengambil data stok produk");
    },
  });
}

$(document).ready(function () {
  loadStockStats(); // Load pertama kali
  setInterval(loadStockStats, 5000); // Update tiap 5 detik
});
</script>

</body>

</html>

