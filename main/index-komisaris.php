<?php
include 'koneksi.php';
session_start();

$username = isset($_SESSION['username']) ? $_SESSION['username'] : 'Guest';
$role = isset($_SESSION['role']) ? $_SESSION['role'] : 'Unknown';   
// Handle edit pengeluaran
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_pengeluaran'])) {
    $id = (int)$_POST['edit_id'];
    $tanggal = mysqli_real_escape_string($conn, $_POST['edit_tanggal']);
    $kategori = mysqli_real_escape_string($conn, $_POST['edit_kategori']);
    $jumlah = (float)$_POST['edit_jumlah'];
    $keterangan = mysqli_real_escape_string($conn, $_POST['edit_keterangan']);
    $metode_pembayaran = mysqli_real_escape_string($conn, $_POST['edit_metode_pembayaran']);
    
    $query_update = "UPDATE pengeluaran SET 
                    tanggal = '$tanggal',
                    kategori = '$kategori',
                    jumlah = $jumlah,
                    keterangan = '$keterangan',
                    metode_pembayaran = '$metode_pembayaran'
                    WHERE id_pengeluaran = $id";
    
    if (mysqli_query($conn, $query_update)) {
        echo "<script>alert('Data pengeluaran berhasil diupdate');</script>";
        echo "<script>window.location.href='data-keuangan.php';</script>";
    } else {
        echo "<script>alert('Error: " . mysqli_error($conn) . "');</script>";
    }
}

// Handle hapus pengeluaran
if (isset($_GET['hapus_pengeluaran'])) {
    $id = (int)$_GET['hapus_pengeluaran'];
    $query_delete = "DELETE FROM pengeluaran WHERE id_pengeluaran = $id";
    
    if (mysqli_query($conn, $query_delete)) {
        echo "<script>alert('Data pengeluaran berhasil dihapus');</script>";
        echo "<script>window.location.href='data-keuangan.php';</script>";
    } else {
        echo "<script>alert('Error: " . mysqli_error($conn) . "');</script>";
    }
}

// Query untuk mendapatkan data pendapatan dari tabel pesanan
$query_pendapatan = "
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
    ORDER BY p.tanggal_pesan DESC
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
$query_total_pendapatan = "
    SELECT COALESCE(SUM(total_harga), 0) AS total
    FROM pesanan
    WHERE status = 'selesai' AND tanggal_pesan >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
";
$result_total_pendapatan = mysqli_query($conn, $query_total_pendapatan);

if (!$result_total_pendapatan) {
    die("Query total pendapatan gagal: " . mysqli_error($conn));
}

$total_pendapatan = 0;
if ($row = mysqli_fetch_assoc($result_total_pendapatan)) {
    $total_pendapatan = $row['total'];
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
        echo "<script>window.location.href='data-keuangan.php';</script>";
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
$query_hutang = "SELECT * FROM hutang_pegawai WHERE status = 'belum lunas' ORDER BY tanggal_hutang DESC";
$result_hutang = mysqli_query($conn, $query_hutang);

if (!$result_hutang) {
    die("Query hutang pegawai gagal: " . mysqli_error($conn));
}

// Hitung total values dengan pengecekan yang lebih aman
$query_total_pendapatan_all = "SELECT COALESCE(SUM(jumlah), 0) AS total FROM pendapatan";
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
            if ($kategori == 'Pembayaran Hutang' && isset($_POST['pegawai_id'])) {
                $pegawai_id = (int)$_POST['pegawai_id'];
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
                                              WHERE pegawai_id = $pegawai_id");
                        }
                    }
                }
            }
            
            echo "<script>alert('Data pendapatan berhasil ditambahkan');</script>";
            echo "<script>window.location.href='data-keuangan.php';</script>";
        } else {
            echo "<script>alert('Error: " . mysqli_error($conn) . "');</script>";
        }
    }
    
    if (isset($_POST['submit_hutang'])) {
        $pegawai_id = (int)$_POST['pegawai_id'];
        $jumlah = (float)$_POST['jumlah'];
        $keterangan = mysqli_real_escape_string($conn, $_POST['keterangan']);
        
        $query = "INSERT INTO hutang_pegawai 
                 (pegawai_id, jumlah, keterangan, tanggal_hutang, status) 
                 VALUES ($pegawai_id, $jumlah, '$keterangan', NOW(), 'belum lunas')";
        
        if (mysqli_query($conn, $query)) {
            echo "<script>alert('Data hutang berhasil ditambahkan');</script>";
            echo "<script>window.location.href='data-keuangan.php';</script>";
        } else {
            echo "<script>alert('Error: " . mysqli_error($conn) . "');</script>";
        }
    }
}
$current_year = date('Y'); // Mengambil tahun saat ini
$chart_labels = [];
$chart_laba_bersih_data = [];

// Array untuk menyimpan data laba bersih per bulan, diinisialisasi dengan 0
$monthly_laba_data_map = [];
$month_names = [
    '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
    '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
    '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
];

// Isi map dengan semua bulan dan nilai awal 0
foreach ($month_names as $num => $name) {
    $formatted_month_year = $current_year . '-' . $num;
    $monthly_laba_data_map[$formatted_month_year] = [
        'label' => $name . ' ' . $current_year,
        'laba_bersih' => 0
    ];
}$query_laba_per_bulan = "
    SELECT
        DATE_FORMAT(p.tanggal_pesan, '%Y-%m') AS bulan,
        COALESCE(SUM(p.total_harga), 0) AS total_pendapatan,
        (
            SELECT COALESCE(SUM(jumlah), 0)
            FROM pengeluaran
            WHERE DATE_FORMAT(tanggal, '%Y-%m') = DATE_FORMAT(p.tanggal_pesan, '%Y-%m')
        ) AS total_pengeluaran
    FROM pesanan p
    WHERE p.status = 'selesai'
    AND DATE_FORMAT(p.tanggal_pesan, '%Y') = '{$current_year}' -- Hanya ambil data tahun ini
    GROUP BY bulan
    ORDER BY bulan ASC
";

$result_laba_per_bulan = mysqli_query($conn, $query_laba_per_bulan);

if ($result_laba_per_bulan) {
    while ($row = mysqli_fetch_assoc($result_laba_per_bulan)) {
        $laba_bersih = $row['total_pendapatan'] - $row['total_pengeluaran'];
        // Update nilai laba bersih di map jika ada data untuk bulan tersebut
        if (isset($monthly_laba_data_map[$row['bulan']])) {
            $monthly_laba_data_map[$row['bulan']]['laba_bersih'] = $laba_bersih;
        }
    }
} else {
    error_log("Query laba per bulan gagal: " . mysqli_error($conn));
}

// Setelah mengisi map, ekstrak labels dan data dari map yang sudah lengkap 12 bulan
foreach ($monthly_laba_data_map as $data) {
    $chart_labels[] = $data['label'];
    $chart_laba_bersih_data[] = $data['laba_bersih'];
}

// Ubah array PHP ke JSON untuk diteruskan ke JavaScript
$chart_labels_json = json_encode($chart_labels);
$chart_laba_bersih_data_json = json_encode($chart_laba_bersih_data);

$query_laba_per_bulan = "
    SELECT 
        DATE_FORMAT(p.tanggal_pesan, '%Y-%m') AS bulan,
        COALESCE(SUM(p.total_harga), 0) AS total_pendapatan,
        (
            SELECT COALESCE(SUM(jumlah), 0) 
            FROM pengeluaran 
            WHERE DATE_FORMAT(tanggal, '%Y-%m') = DATE_FORMAT(p.tanggal_pesan, '%Y-%m')
        ) AS total_pengeluaran
    FROM pesanan p
    WHERE p.status = 'selesai'
    GROUP BY bulan
    ORDER BY bulan ASC
    LIMIT 6
";

$result_laba_per_bulan = mysqli_query($conn, $query_laba_per_bulan);

$labels = [];
$dataLabaBersih = [];

while ($row = mysqli_fetch_assoc($result_laba_per_bulan)) {
    $labels[] = $row['bulan'];
    $laba_bersih = $row['total_pendapatan'] - $row['total_pengeluaran'];
    $dataLabaBersih[] = $laba_bersih;
}
$margin_laba = ($total_pendapatan > 0) ? ($laba_bersih / $total_pendapatan) * 100 : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Data Keuangan</title>
    <link rel="stylesheet" href="../css/index-kepala-keuangan.css">
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
            <li><a href="index-komisaris.php"><i class='bx bx-user' ></i></i><span>Dashboard</span></a></li>
            <li><a href="../pages/data-keuangan-komisaris.php"><i class='bx bx-user' ></i></i><span>Data Keuangan</span></a></li>
            <li>
                <form action="logout.php" method="POST">
                    <button type="submit" class="btn-logout-sidebar"><span>Logout</span></button>
                </form>
            </li>
        </ul>
    </aside>
    <div class="beranda">
        <br><br>
         <h2>Ringkasan Bisnis</h2>
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
                <h3><?= number_format($margin_laba, 2, ',', '.') ?>%</h3>
                <p>Margin Laba</p>
                <i class='bx bx-bar-chart-alt-2 fa-2xl'></i>
            </div>
        </div>
        <br><br>
        <div class="chart-container">
            <canvas style="height:500px;"id="labaBersihBulananChart"></canvas>
        </div>
        <br><br>
        
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src=".javascript/data-keuangan.js"></script>
<script>
$(document).ready(function() {
    // Variabel untuk menyimpan ID yang akan dihapus
    let pengeluaranIdToDelete = null;
    
    // Fungsi untuk membuka modal edit
    $('.edit-btn').click(function() {
        const id = $(this).data('id');
        
        // Ambil data pengeluaran via AJAX
        $.ajax({
            url: 'get_pengeluaran.php',
            method: 'GET',
            data: { id: id },
            dataType: 'json',
            success: function(data) {
                if (data) {
                    $('#edit_id').val(data.id_pengeluaran);
                    $('#edit_tanggal').val(data.tanggal);
                    $('#edit_kategori').val(data.kategori);
                    $('#edit_jumlah').val(data.jumlah);
                    $('#edit_keterangan').val(data.keterangan);
                    $('#edit_metode_pembayaran').val(data.metode_pembayaran);
                    
                    // Tampilkan modal edit
                    $('#editModal').show();
                }
            },
            error: function() {
                alert('Gagal mengambil data pengeluaran');
            }
        });
    });
    
    // Fungsi untuk membuka modal hapus
    $('.delete-btn').click(function() {
        pengeluaranIdToDelete = $(this).data('id');
        $('#deleteModal').show();
    });
    
    // Konfirmasi hapus
    $('#confirmDelete').click(function() {
        if (pengeluaranIdToDelete) {
            window.location.href = 'data-keuangan.php?hapus_pengeluaran=' + pengeluaranIdToDelete;
        }
    });
    
    // Batal hapus
    $('#cancelDelete').click(function() {
        $('#deleteModal').hide();
    });
    
    // Tutup modal ketika klik tombol close
    $('.close').click(function() {
        $('#editModal').hide();
        $('#deleteModal').hide();
    });
    
    // Tutup modal ketika klik di luar modal
    $(window).click(function(event) {
        if ($(event.target).hasClass('modal')) {
            $('#editModal').hide();
            $('#deleteModal').hide();
        }
    });
});
</script>
<script>
// Modal functionality
document.addEventListener('DOMContentLoaded', function() {
    // Get modal elements
    const modal = document.getElementById('pengeluaranModal');
    const btn = document.getElementById('openPengeluaranModal');
    const span = document.getElementsByClassName('close-modal')[0];

    // When user clicks the button, open modal
    btn.onclick = function() {
        modal.style.display = 'block';
    }

    // When user clicks on (x), close modal
    span.onclick = function() {
        modal.style.display = 'none';
    }

    // When user clicks anywhere outside modal, close it
    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }

    // Set today's date as default
    document.getElementById('tanggal').valueAsDate = new Date();
});
</script>
<script>
$(document).ready(function() {
    // Modal functionality
    const modalPendapatan = document.getElementById('modalPendapatan');
    const modalHutang = document.getElementById('modalHutang');
    const btnPendapatan = document.getElementById('btnTambahPendapatan');
    const btnHutang = document.getElementById('btnTambahHutang');
    const spans = document.getElementsByClassName('close');
    
    // Open modals
    btnPendapatan.onclick = function() { modalPendapatan.style.display = 'block'; }
    btnHutang.onclick = function() { modalHutang.style.display = 'block'; }
    
    // Close modals
    for (let span of spans) {
        span.onclick = function() {
            modalPendapatan.style.display = 'none';
            modalHutang.style.display = 'none';
        }
    }
    
    window.onclick = function(event) {
        if (event.target == modalPendapatan) modalPendapatan.style.display = 'none';
        if (event.target == modalHutang) modalHutang.style.display = 'none';
    }
    
    // Show/hide debt payment fields based on category selection
    $('#kategoriPendapatan').change(function() {
        if ($(this).val() == 'Pembayaran Hutang') {
            $('#hutangFields').show();
        } else {
            $('#hutangFields').hide();
        }
    });
    
    // Set today's date as default
    $('input[type="date"]').val(new Date().toISOString().substr(0, 10));
});
</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const labels = <?= $chart_labels_json ?? '[]' ?>;
    const labaBersihData = <?= $chart_laba_bersih_data_json ?? '[]' ?>;
    const currentYear = new Date().getFullYear(); // Untuk judul grafik

    const ctx = document.getElementById('labaBersihBulananChart');

    if (ctx) {
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels, // Ini sekarang akan berisi "Januari 2024", "Februari 2024", dst.
                datasets: [{
                    label: 'Laba Bersih per Bulan',
                    data: labaBersihData,
                    backgroundColor: 'rgba(54, 162, 235, 0.6)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Jumlah (Rp)'
                        },
                        ticks: {
                            callback: function(value) {
                                return 'Rp ' + value.toLocaleString('id-ID');
                            }
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Bulan'
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'Grafik Laba Bersih per Bulan Tahun ' + currentYear // Judul grafik
                    }
                }
            }
        });
    }
});
</script>

</body>
</html>