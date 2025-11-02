<?php
include '../main/koneksi.php';
session_start();

$username = isset($_SESSION['username']) ? $_SESSION['username'] : 'Guest';
$role = isset($_SESSION['role']) ? $_SESSION['role'] : 'Unknown';
// Tambah atau update supplier
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_supplier'])) {
    // Ambil semua data dari $_POST dengan pengecekan isset agar tidak undefined
    $supplier_id = isset($_POST['supplier_id']) ? $_POST['supplier_id'] : '';
    $nama_supplier = isset($_POST['nama_supplier']) ? $_POST['nama_supplier'] : '';
    $kontak = isset($_POST['kontak']) ? $_POST['kontak'] : '';
    $alamat = isset($_POST['alamat']) ? $_POST['alamat'] : '';
    $email = isset($_POST['email']) ? $_POST['email'] : '';
    $barang_supplier = isset($_POST['barang_supplier']) ? $_POST['barang_supplier'] : '';
    $harga = isset($_POST['harga']) ? $_POST['harga'] : '';

    if (!empty($supplier_id)) {
        // UPDATE data supplier
        $query = "UPDATE supplier SET
                    nama_supplier='$nama_supplier',
                    kontak='$kontak',
                    alamat='$alamat',
                    email='$email',
                    barang_supplier='$barang_supplier',
                    harga='$harga'
                  WHERE supplier_id='$supplier_id'";
    } else {
        // INSERT data supplier baru
        $query = "INSERT INTO supplier
                    (nama_supplier, kontak, alamat, email, barang_supplier, harga)
                  VALUES
                    ('$nama_supplier', '$kontak', '$alamat', '$email', '$barang_supplier', '$harga')";
    }

    mysqli_query($conn, $query);
    header("Location: supplier.php");
    exit;
}

// Hapus supplier
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus_supplier'])) {
    $id = $_POST['hapus_supplier'];
    mysqli_query($conn, "DELETE FROM supplier WHERE supplier_id='$id'");
    header("Location: supplier.php");
    exit;
}




// Ambil data supplier
$query_supplier = mysqli_query($conn, "SELECT * FROM supplier");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WEB PENYIMPANAN BARANG</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter&family=Open+Sans&family=Poppins:wght@400;600&display=swap"rel="stylesheet">
    <link rel="stylesheet" href="../css/supplier.css">
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
            <li><a href="../main/index.php"><i class='bx bx-home-alt-2'></i><span>Dashboard</span></a></li>
            <li><a href="barang.php"><i class='bx bx-package'></i><span>Data Barang</span></a></li>
            <li><a href="penjualan.php"><i class='bx bx-cart'></i><span>Data Penjualan</span></a></li>
            <li><a href="supplier.php"><i class='bx bx-store-alt'></i><span>Data Supplier</span></a></li>
            <li><a href="pelanggan.php"><i class='bx bx-user' ></i></i><span>Data Pelanggan</span></a></li>
            <li><a href="penjadwalan-pembelian.php"><i class='bx bx-user' ></i></i><span>Penjadwalan Pembelian</span></a></li>
            <li><a href="data-keuangan.php"><i class='bx bx-dollar'></i><span>Data Keuangan</span></a></li>
            <li><a href="data-pembelian.php"><i class='bx bx-dollar'></i><span>Data Pembelian</span></a></li>
            <li><a href="../main/register.php"><i class='bx bx-user' ></i></i><span>Daftarkan Pegawai</span></a></li>
            <li>
                <form action="../main/logout.php" method="POST">
                    <button type="submit" class="btn-logout-sidebar"><span>Logout</span></button>
                </form>
            </li>
        </ul>
    </aside>
    <div class="beranda">
        <br><br>
        <h2>Data Supplier</h2>
        <button id="openModalBtn" class="btn-submit">Tambah Supplier</button>
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Supplier</th>
                    <th>Kontak</th>
                    <th>Email</th>
                    <th>Alamat</th>
                    <th>Barang Supplier</th>
                    <th>Harga</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $no = 1;
            while ($row = mysqli_fetch_assoc($query_supplier)) {
                echo "<tr>
                        <td>{$no}</td>
                        <td>{$row['nama_supplier']}</td>
                        <td>{$row['kontak']}</td>
                        <td>{$row['email']}</td>
                        <td>{$row['alamat']}</td>
                        <td>{$row['barang_supplier']}</td>
                        <td>{$row['harga']}</td>
                        <td>
                            <button class='editSupplierBtn btn-edit'
                            data-id='{$row['supplier_id']}'
                            data-nama='{$row['nama_supplier']}'
                            data-kontak='{$row['kontak']}'
                            data-email='{$row['email']}'
                            data-alamat='{$row['alamat']}'
                            data-barang='{$row['barang_supplier']}'
                            data-harga='{$row['harga']}'>Edit</button>
                            <button class='hapusSupplierBtn btn-danger' data-id='{$row['supplier_id']}'>Hapus</button>
                        </td>
                    </tr>";
                $no++;
            }
            ?>
            </tbody>
        </table>
    </div>
</div>

<div id="modalSupplier" class="modal">
    <div class="modal-content">
        <span class="close-btn">&times;</span>
        <h2>Input Supplier</h2>
        <form method="POST" action="supplier.php">
            <input type="hidden" name="supplier_id" id="supplier_id">
            <label for="nama_supplier">Nama Supplier:</label>
            <input type="text" name="nama_supplier" id="nama_supplier" required>

            <label for="kontak">Kontak:</label>
            <input type="text" name="kontak" id="kontak" required>

            <label for="alamat">Alamat:</label>
            <textarea name="alamat" id="alamat" required></textarea>

            <label for="email">Email:</label>
            <input type="email" name="email" id="email" required>

            <label for="barang_supplier">Barang Supplier:</label>
            <input type="text" name="barang_supplier" id="barang_supplier" required>

            <label for="harga">Harga:</label>
            <input type="number" step="0.01" name="harga" id="harga" required>

            <button type="submit" name="simpan_supplier" class="btn-submit">Simpan</button>
        </form>
    </div>
</div>

<div id="modalHapus" class="modal">
  <div class="modal-content">
    <span class="close-btn-hapus">&times;</span>
    <h3>Konfirmasi Hapus</h3>
    <p>Yakin ingin menghapus data supplier ini?</p>
    <form method="POST" action="supplier.php">
        <input type="hidden" name="hapus_supplier" id="hapus_supplier_id">
        <button type="submit" class="btn-danger">Ya, Hapus</button>
        <button type="button" id="batalHapusBtn" class="btn-cancel">Batal</button>
    </form>
  </div>
</div>

<script src="../javascript/supplier.js"></script>
</body>
</html>