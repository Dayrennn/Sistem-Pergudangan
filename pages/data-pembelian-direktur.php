<?php
include '../main/koneksi.php';
session_start();

$username = isset($_SESSION['username']) ? $_SESSION['username'] : 'Guest';
$role = isset($_SESSION['role']) ? $_SESSION['role'] : 'Unknown';   
$sql = "SELECT * FROM permintaan_pembelian";


// Inisialisasi variabel $status dan $id_permintaan
$status = '';
$id_permintaan = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ubah_status'])) {
    $id_permintaan = $_POST['id_permintaan'];
    $status = $_POST['status'];

    // Update status
    mysqli_query($conn, "UPDATE permintaan_pembelian SET status='$status' WHERE id='$id_permintaan'");

    if ($status == 'diterima') {
        $result = mysqli_query($conn, "SELECT * FROM permintaan_pembelian WHERE id='$id_permintaan'");
        if ($row = mysqli_fetch_assoc($result)) {
            $tanggal = date('Y-m-d');
            $kategori = "Pembelian Barang";
            $jumlah = $row['total_harga'];
            $keterangan = "Pembelian dari permintaan ID #" . $row['id'];
            $metode_pembayaran = "Transfer";

            mysqli_query($conn, "INSERT INTO pengeluaran (tanggal, kategori, jumlah, keterangan, metode_pembayaran)
                                     VALUES ('$tanggal', '$kategori', $jumlah, '$keterangan', '$metode_pembayaran')");
        }
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}


// Simpan perubahan edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_edit'])) {
    $id = $_POST['edit_id'];
    $jumlah_baru = (int)$_POST['edit_jumlah'];

    $res = mysqli_query($conn, "SELECT harga FROM permintaan_pembelian WHERE id='$id'");
    $data = mysqli_fetch_assoc($res);
    $harga = (int)$data['harga'];
    $total_harga_baru = $harga * $jumlah_baru;

    mysqli_query($conn, "UPDATE permintaan_pembelian SET jumlah='$jumlah_baru', total_harga='$total_harga_baru' WHERE id='$id'");
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}


// Hapus permintaan pembelian
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus_permintaan'])) {
    $id = $_POST['hapus_permintaan'];
    mysqli_query($conn, "DELETE FROM permintaan_pembelian WHERE id='$id'");
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}



if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajukan_pembelian'])) {
    $supplier_id = $_POST['supplier_id'];
    $jumlah = isset($_POST['jumlah']) ? (int)$_POST['jumlah'] : 0;

    $result = mysqli_query($conn, "SELECT * FROM supplier WHERE supplier_id='$supplier_id'");
    $supplier = mysqli_fetch_assoc($result);

    if ($supplier && $jumlah > 0) {
        $nama = $supplier['nama_supplier'];
        $barang = $supplier['barang_supplier'];
        $harga = $supplier['harga'];
        $tanggal = date('Y-m-d');
        $total_harga = $harga * $jumlah;

        mysqli_query($conn, "INSERT INTO permintaan_pembelian
                                (supplier_id, nama_supplier, barang, harga, jumlah, total_harga, status, tanggal_permintaan)
                                VALUES ('$supplier_id', '$nama', '$barang', '$harga', '$jumlah', '$total_harga', 'Pending', '$tanggal')");
    }
}


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
    <link rel="stylesheet" href="../css/data-pembelian.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
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
            <li><a href="../main/index-direktur.php"><i class='bx bx-home-alt-2'></i><span>Dashboard</span></a></li>
            <li><a href="barang-direktur.php"><i class='bx bx-package'></i><span>Data Barang</span></a></li>
            <li><a href="penjualan-direktur.php"><i class='bx bx-cart'></i><span>Data Penjualan</span></a></li>
            <li><a href="penjadwalan-pembelian-direktur.php"><i class='bx bx-user' ></i></i><span>Penjadwalan Pembelian</span></a></li>
            <li><a href="data-pembelian-direktur.php"><i class='bx bx-dollar'></i><span>Data Pembelian</span></a></li>
            <li>
                <form action="../main/logout.php" method="POST">
                    <button type="submit" class="btn-logout-sidebar"><span>Logout</span></button>
                </form>
            </li>
        </ul>
    </aside>
    <div class="beranda">
        <br><br>
        <h2>Pengajuan Pembelian</h2>
<table>
    <thead>
        <tr>
            <th>No</th>
            <th>Nama Supplier</th>
            <th>Barang</th>
            <th>Harga</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
        <?php
        // Ambil ulang data supplier (bisa pakai $query_supplier jika belum habis)
        $query_pembelian = mysqli_query($conn, "SELECT * FROM supplier");
        $no = 1;
     while ($row = mysqli_fetch_assoc($query_pembelian)) {
            echo "<tr>
                    <td>{$no}</td>
                    <td>{$row['nama_supplier']}</td>
                    <td>{$row['barang_supplier']}</td>
                    <td>Rp " . number_format($row['harga'], 0, ',', '.') . "</td>
                    <td>
                        <button type='button' class='btn-ajukan' onclick=\"openModal('{$row['supplier_id']}', '{$row['nama_supplier']}', '{$row['barang_supplier']}', '{$row['harga']}')\">+ Ajukan Pembelian</button>
                    </td>
                </tr>";
            $no++;
        }

        ?>
    </tbody>
</table><br><br>

<h2>Daftar Permintaan Pembelian</h2>
    <table border="1" cellpadding="5" cellspacing="0">
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
                            <input type='hidden' name='hapus_permintaan' value='{$p['id']}'>
                            <button type='submit' class='btn-hapus'>Hapus</button>
                        </form>
                        <button type='button' class='btn-edit' onclick=\"editPermintaan(
                            '{$p['id']}',
                            '{$p['jumlah']}'
                        )\">Edit</button>
                    </td>
                </tr>";
            $no++;
        }
        if ($status == 'diterima') {
    // Ambil data permintaan pembelian
    $query = "SELECT * FROM permintaan_pembelian WHERE id_permintaan = $id_permintaan";
    $result = mysqli_query($conn, $query);
    if ($row = mysqli_fetch_assoc($result)) {
        $tanggal = date('Y-m-d');
        $kategori = "Pembelian Barang";
        $jumlah = $row['total_harga']; // atau gunakan kolom sesuai nama yang benar
        $keterangan = "Pembelian dari permintaan ID #" . $row['id_permintaan'];
        $metode_pembayaran = "Transfer"; // atau sesuaikan jika ada input metode
        
        // Masukkan ke pengeluaran
        $insert_query = "INSERT INTO pengeluaran (tanggal, kategori, jumlah, keterangan, metode_pembayaran) 
                         VALUES ('$tanggal', '$kategori', $jumlah, '$keterangan', '$metode_pembayaran')";
        mysqli_query($conn, $insert_query);
    }
}


        ?>
    </tbody>
</table><br><br>
</div>
<div id="modalPembelian" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; 
    background-color: rgba(0,0,0,0.6); z-index:999; justify-content:center; align-items:center;">
    <div style="background:#fff; padding:20px; border-radius:8px; width:400px; position:relative;">
        <h3>Ajukan Pembelian</h3>
        <form method="POST" action="proses-pembelian.php">
            <input type="hidden" name="supplier_id" id="modalSupplierId">
            <input type="hidden" name="nama_supplier" id="modalNamaSupplier">
            <input type="hidden" name="barang" id="modalBarang">
            <input type="hidden" name="harga" id="modalHarga">
            <label for="jumlah">Jumlah:</label>
            <input type="number" id="modalJumlah" name="jumlah" min="1" required oninput="hitungTotal()"><br><br>
            <label>Total Harga:</label>
            <input type="text" id="modalTotalHarga" readonly><br><br>
            <input type="hidden" name="total_harga" id="inputTotalHarga">
            <button type="submit" name="ajukan_pembelian" class="btn-ajukan">Ajukan</button>
            <button type="button" onclick="closeModal()" class="btn-batal-modal">Batal</button>
        </form>
    </div>
</div>
<div id="modalEditPermintaan" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background-color: rgba(0,0,0,0.6); z-index:999; justify-content:center; align-items:center;">
    <div style="background:#fff; padding:20px; border-radius:8px; width:400px; position:relative;">
        <h3>Edit Permintaan</h3>
        <form method="POST" action="">
            <input type="hidden" name="edit_id" id="editId">
            <label for="editJumlah">Jumlah:</label>
            <input type="number" id="editJumlah" name="edit_jumlah" min="1" required><br><br>
            <button type="submit" name="simpan_edit" class="btn-edit">Simpan Perubahan</button>
            <button type="button" onclick="closeEditModal()" class="btn-batal-modal">Batal</button>
        </form>
    </div>
</div>





<script src="../javascript/supplier.js"></script>
<script>
    let hargaSaatIni = 0;

    function openModal(id, nama, barang, harga) {
        document.getElementById('modalSupplierId').value = id;
        document.getElementById('modalNamaSupplier').value = nama;
        document.getElementById('modalBarang').value = barang;
        document.getElementById('modalHarga').value = harga;
        hargaSaatIni = parseFloat(harga);

        document.getElementById('modalJumlah').value = '';
        document.getElementById('modalTotalHarga').value = '';
        document.getElementById('inputTotalHarga').value = '';

        document.getElementById('modalPembelian').style.display = 'flex';
    }

    function closeModal() {
        document.getElementById('modalPembelian').style.display = 'none';
    }

    function hitungTotal() {
        const jumlah = parseInt(document.getElementById('modalJumlah').value);
        if (!isNaN(jumlah) && jumlah > 0) {
            const total = hargaSaatIni * jumlah;
            document.getElementById('modalTotalHarga').value = "Rp " + total.toLocaleString("id-ID");
            document.getElementById('inputTotalHarga').value = total;
        } else {
            document.getElementById('modalTotalHarga').value = '';
            document.getElementById('inputTotalHarga').value = '';
        }
    }
    function editPermintaan(id, jumlah) {
    document.getElementById('editId').value = id;
    document.getElementById('editJumlah').value = jumlah;
    document.getElementById('modalEditPermintaan').style.display = 'flex';
}

function closeEditModal() {
    document.getElementById('modalEditPermintaan').style.display = 'none';
}
</script>


</body>
</html>
