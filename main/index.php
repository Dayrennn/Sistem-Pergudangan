<?php
session_start();

$username = isset($_SESSION['username']) ? $_SESSION['username'] : 'Guest';
$role = isset($_SESSION['role']) ? $_SESSION['role'] : 'Unknown';   
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

require_once '../main/koneksi.php';

// Check connection
if (!$conn) {
    die("Database connection failed");
}

// --- START: Penanganan Hapus Permintaan Pembelian ---
if (isset($_POST['hapus_permintaan'])) {
            error_log("POST hapus_permintaan: " . print_r($_POST['hapus_permintaan'], true));
    $id_permintaan = intval($_POST['hapus_permintaan']); // Pastikan ini integer
     error_log("ID setelah intval: " . $id_permintaan);


    if ($id_permintaan > 0) {
        $stmt_delete = $conn->prepare("DELETE FROM permintaan_pembelian WHERE id = ?");
        if ($stmt_delete) {
            $stmt_delete->bind_param("i", $id_permintaan);
            if ($stmt_delete->execute()) {
                if ($stmt_delete->affected_rows > 0) {
                    echo "<script>alert('Permintaan berhasil dihapus.');</script>";
                } else {
                    echo "<script>alert('Permintaan tidak ditemukan atau sudah dihapus.');</script>";
                }
            } else {
                echo "<script>alert('Gagal menghapus permintaan: " . $stmt_delete->error . "');</script>";
            }
            $stmt_delete->close();
        } else {
            echo "<script>alert('Terjadi kesalahan saat menyiapkan query hapus: " . $conn->error . "');</script>";
        }
    } else {
        echo "<script>alert('ID permintaan tidak valid.');</script>";
    }
    // Refresh halaman setelah hapus untuk menghilangkan POST data dan melihat perubahan
    echo "<script>window.location.href = 'index.php';</script>";
    exit; // Penting untuk menghentikan eksekusi setelah redirect
}
// --- END: Penanganan Hapus Permintaan Pembelian ---

$sql = "SELECT * FROM permintaan_pembelian";
// Check connection
if (!$conn) {
    die("Database connection failed");
}

$sql = "SELECT * FROM permintaan_pembelian";

// Get item count
$query_barang = mysqli_query($conn, "SELECT COUNT(*) as total FROM barang") or die("Error barang: " . mysqli_error($conn));
$data_barang = mysqli_fetch_assoc($query_barang);
$total_barang = $data_barang ? $data_barang['total'] : 0;

// Get sales count
$query_penjualan = mysqli_query($conn, "SELECT COUNT(*) as total FROM pesanan") or die("Error penjualan: " . mysqli_error($conn));
$data_penjualan = mysqli_fetch_assoc($query_penjualan);
$total_penjualan = $data_penjualan ? $data_penjualan['total'] : 0;

// Get supplier count
$query_supplier = mysqli_query($conn, "SELECT COUNT(*) as total FROM supplier") or die("Error supplier: " . mysqli_error($conn));
$data_supplier = mysqli_fetch_assoc($query_supplier);
$total_supplier = $data_supplier ? $data_supplier['total'] : 0;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WEB PENYIMPANAN BARANG</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter&family=Open+Sans&family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/index.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">

</head>

<body>
    <div id="content">
        <nav class="navbar">
            <div class="profile-company">
                <img src="../assets/logo.png" alt="Logo">
            </div>
        </nav>

        <div class="container">
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
                    <li><a href="index.php"><i class='bx bx-home-alt-2'></i><span>Dashboard</span></a></li>
                    <li><a href="../pages/barang.php"><i class='bx bx-package'></i><span>Data Barang</span></a></li>
                    <li><a href="../pages/penjualan.php"><i class='bx bx-cart'></i><span>Data Penjualan</span></a></li>
                    <li><a href="../pages/supplier.php"><i class='bx bx-store-alt'></i><span>Data Supplier</span></a></li>
                    <li><a href="../pages/pelanggan.php"><i class='bx bx-user' ></i></i><span>Data Pelanggan</span></a></li>
                    <li><a href="../pages/penjadwalan-pembelian.php"><i class='bx bx-user' ></i></i><span>Penjadwalan Pembelian</span></a></li>
                    <li><a href="../pages/data-keuangan.php"><i class='bx bx-dollar'></i><span>Data Keuangan</span></a></li>
                    <li><a href="../pages/data-pembelian.php"><i class='bx bx-dollar'></i><span>Data Pembelian</span></a></li>
                    <li><a href="register.php"><i class='bx bx-user' ></i></i><span>Daftarkan Pegawai</span></a></li>
                    <li>
                        <form action="logout.php" method="POST">
                            <button type="submit" class="btn-logout-sidebar"><span>Logout</span></button>
                        </form>
                    </li>
                </ul>
            </aside>

            <div class="beranda">
                <br><br>
                <h2>Dashboard</h2>
                <div class="container-card">
                    <a href="../pages/barang.php" style="text-decoration: none;">
                        <div class="card" style="background-color: #ebedff;">
                            <h3><?php echo $total_barang; ?></h3>
                            <p>Data Barang</p>
                            <i class='bx bxs-box fa-2xl'></i>
                        </div>
                    </a>
                    <a href="../pages/penjualan.php" style="text-decoration: none;">
                        <div class="card" style="background-color: #ebf4ff;">
                            <h3><?php echo $total_penjualan; ?></h3>
                            <p>Data Penjualan</p>
                            <i class='bx bx-cart fa-2xl'></i>
                        </div>
                    </a>
                    <a href="../pages/supplier.php" style="text-decoration: none;">
                        <div class="card" style="background-color: #ebedff;">
                            <h3><?php echo $total_supplier; ?></h3>
                            <p>Data Supplier</p>
                            <i class='bx bxs-user-account fa-2xl'></i>
                        </div>
                    </a>
                    <a href="../pages/data-keuangan.php" style="text-decoration: none;">
                        <div class="card" style="background-color: #ebedff;">
                            <h3>-</h3>
                            <p>Data Keuangan</p>
                            <i class='bx bx-dollar fa-2xl'></i>
                        </div>
                    </a>
                </div><br><br>
                <div class="data-table-container">
                    <h2>Daftar Permintaan Pembelian</h2>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Supplier</th>
                                <th>Barang</th>
                                <th>Harga</th>
                                <th>Jumlah</th>
                                <th>Total Harga</th>
                                <th>Tanggal</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $q_permintaan = mysqli_query($conn, "SELECT * FROM permintaan_pembelian ORDER BY tanggal_permintaan DESC");
                            $no = 1;
                            while ($p = mysqli_fetch_assoc($q_permintaan)) {
                               echo "<tr>
                                    <td>{$no}</td>
                                    <td>{$p['nama_supplier']}</td>
                                    <td>{$p['barang']}</td>
                                    <td>Rp " . number_format($p['harga'], 0, ',', '.') . "</td>
                                    <td>{$p['jumlah']}</td>
                                    <td>Rp " . number_format($p['total_harga'], 0, ',', '.') . "</td>
                                    <td>{$p['tanggal_permintaan']}</td>
                                    <td>{$p['status']}</td>
                                    <td>
                                        <form method='POST' action='' onsubmit=\"return confirm('Yakin ingin menghapus permintaan ini?');\" style='display:inline;'>
                                            <input type='hidden' name='hapus_permintaan' value='{$p['id']}'> <button type='submit' class='btn-hapus'>Hapus</button>
                                        </form>
                                        <button type='button' class='btn-edit' onclick=\"openModalUbahStatus('{$p['id']}', '{$p['status']}')\">Edit</button> </td>
                                </tr>";
                            }
                            ?>
                        </tbody>
                    </table><br><br>
                </div>
            </div>
        </div>
    </div>
</div>

    <!-- Modal for Edit Status -->
    <div id="modalUbahStatus" class="modal" method="POST" action="update_permintaan.php">
        <div class="modal-content">
            <h3>Ubah Status Permintaan</h3>
            <form id="formUbahStatus" method="POST" action="update_permintaan.php">
                <input type="hidden" id="modal-id" name="id" value="">
                <div class="form-group">
                    <label for="modal-status">Status:</label>
                    <select id="modal-status" name="status" class="form-control">
                        <option value="pending">Pending</option>
                        <option value="disetujui">Disetujui</option>
                        <option value="ditolak">Ditolak</option>
                    </select>
                </div>
                <div class="modal-buttons">
                    <button type="button" class="btn-batal" onclick="closeModalUbahStatus()">Batal</button>
                    <button type="submit" class="btn-simpan">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../javascript/index.js"></script>
</body>
</html>