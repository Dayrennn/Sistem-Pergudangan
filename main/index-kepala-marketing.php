<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

$username = isset($_SESSION['username']) ? $_SESSION['username'] : 'Guest';
$role = isset($_SESSION['role']) ? $_SESSION['role'] : 'Unknown';   
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

require_once 'koneksi.php';

// Cek koneksi
if (!$conn) {
    die("Koneksi database gagal");
}

if (isset($_POST['submit_produk'])) {
    $nama_produk = mysqli_real_escape_string($conn, $_POST['nama_produk']);
    $harga = mysqli_real_escape_string($conn, $_POST['harga']);
    $stok = mysqli_real_escape_string($conn, $_POST['stok']);
    $diskon = isset($_POST['diskon']) ? (int)$_POST['diskon'] : 0;
    $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']); // Tambahkan jika ada deskripsi
    $is_promo = isset($_POST['is_promo']) ? 1 : 0; // Pastikan ini juga diambil dari form jika ada checkbox

    $gambar_produk = ''; // Inisialisasi nama file gambar

    // --- LOGIKA UPLOAD FILE GAMBAR ---
    if (isset($_FILES['gambar_produk']) && $_FILES['gambar_produk']['error'] === UPLOAD_ERR_OK) {
        $file_tmp_name = $_FILES['gambar_produk']['tmp_name'];
        $file_name = $_FILES['gambar_produk']['name'];
        $file_size = $_FILES['gambar_produk']['size'];
        $file_type = $_FILES['gambar_produk']['type'];

        // Dapatkan ekstensi file
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        // Tentukan folder tujuan upload
        $upload_dir = '../uploads/'; // Pastikan folder 'uploads' ini ada dan bisa ditulis (writable)

        // Buat nama unik untuk file agar tidak terjadi overwrite jika nama sama
        $new_file_name = uniqid('produk_') . '.' . $file_ext;
        $dest_path = $upload_dir . $new_file_name;

        // Tipe file yang diizinkan (opsional tapi sangat disarankan untuk keamanan)
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array($file_ext, $allowed_extensions)) {
            $_SESSION['message'] = 'Gagal menambah produk: Hanya file JPG, JPEG, PNG, GIF yang diizinkan.';
            $_SESSION['message_type'] = 'danger';
            // Redirect atau tampilkan error di sini
            header("Location: index-kepala-marketing.php");
            exit();
        }

        // Ukuran file maksimum (opsional)
        $max_file_size = 5 * 1024 * 1024; // 5 MB
        if ($file_size > $max_file_size) {
            $_SESSION['message'] = 'Gagal menambah produk: Ukuran file terlalu besar (maks 5MB).';
            $_SESSION['message_type'] = 'danger';
            header("Location: index-kepala-marketing.php");
            exit();
        }

        // Pindahkan file dari temporary location ke folder tujuan
        if (move_uploaded_file($file_tmp_name, $dest_path)) {
            $gambar_produk = $new_file_name; // Simpan nama file baru untuk disimpan ke database
            $_SESSION['message'] = 'Gambar berhasil diupload.';
        } else {
            $_SESSION['message'] = 'Gagal mengupload gambar.';
            $_SESSION['message_type'] = 'danger';
            // Jika upload gambar gagal, Anda mungkin ingin menghentikan proses penambahan produk
            header("Location: index-kepala-marketing.php");
            exit();
        }
    } else if (isset($_FILES['gambar_produk']) && $_FILES['gambar_produk']['error'] !== UPLOAD_ERR_NO_FILE) {
        // Menangani error upload lainnya (selain UPLOAD_ERR_OK dan UPLOAD_ERR_NO_FILE)
        $phpFileUploadErrors = array(
            0 => 'There is no error, the file uploaded with success',
            1 => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
            2 => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
            3 => 'The uploaded file was only partially uploaded',
            4 => 'No file was uploaded',
            6 => 'Missing a temporary folder',
            7 => 'Failed to write file to disk.',
            8 => 'A PHP extension stopped the file upload.',
        );
        $_SESSION['message'] = 'Gagal mengupload gambar: ' . (isset($phpFileUploadErrors[$_FILES['gambar_produk']['error']]) ? $phpFileUploadErrors[$_FILES['gambar_produk']['error']] : 'Unknown error');
        $_SESSION['message_type'] = 'danger';
        header("Location: index-kepala-marketing.php");
        exit();
    }
    // --- AKHIR LOGIKA UPLOAD FILE GAMBAR ---


    // Query untuk memasukkan data produk ke database
    // Pastikan nama kolom di database Anda sesuai (nama_produk, harga, stok, gambar_produk, diskon, is_promo, deskripsi)
    $query = "INSERT INTO landing_produk (nama_produk, harga, stok, gambar_produk, diskon, deskripsi, is_promo) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);

    if ($stmt) {
        $stmt->bind_param("sisdsbi", $nama_produk, $harga, $stok, $gambar_produk, $diskon, $deskripsi, $is_promo); // 's' string, 'i' integer, 'd' double (for diskon if decimal), 'b' boolean/tinyint

        if ($stmt->execute()) {
            $_SESSION['message'] = 'Produk berhasil ditambahkan!';
            $_SESSION['message_type'] = 'success';
        } else {
            $_SESSION['message'] = 'Gagal menambahkan produk ke database: ' . $stmt->error;
            $_SESSION['message_type'] = 'danger';
        }
        $stmt->close();
    } else {
        $_SESSION['message'] = 'Gagal mempersiapkan query: ' . $conn->error;
        $_SESSION['message_type'] = 'danger';
    }

    header("Location: index-kepala-marketing.php");
    exit();
}

$message = '';
$message_type = '';
if (isset($_SESSION['message']) && isset($_SESSION['message_type'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'];
    
    // Hapus pesan dari session setelah ditampilkan
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}

$data = mysqli_query($conn, "SELECT * FROM landing_produk");
// Inisialisasi status
$statusCounts = [
    'Terjadwal' => 0,
    'Dalam Proses' => 0,
    'Selesai' => 0,
    'Dibatalkan' => 0
];
// Query data barang
$query_barang_detail = mysqli_query($conn, "SELECT nama_barang, harga, stok FROM barang");
if (!$query_barang_detail) {
    die("Query data barang gagal: " . mysqli_error($conn));
}
// Total penjualan (jumlah total_harga dari semua pesanan)
$totalPenjualan = 0;
$queryTotal = mysqli_query($conn, "SELECT SUM(total_harga) as total FROM pesanan");
if ($queryTotal && $row = mysqli_fetch_assoc($queryTotal)) {
    $totalPenjualan = (float) $row['total'];
}

// Rata-rata penjualan per transaksi (rata-rata total_harga dari pesanan)
$rataTransaksi = 0;
$queryRata = mysqli_query($conn, "SELECT AVG(total_harga) as rata FROM pesanan");
if ($queryRata && $row = mysqli_fetch_assoc($queryRata)) {
    $rataTransaksi = round($row['rata'], 2);
}

// Hitung status penjadwalan pembelian
$query_status = mysqli_query($conn, "SELECT status, COUNT(*) as jumlah FROM penjadwalan_pembelian GROUP BY status");
if ($query_status) {
    while ($row = mysqli_fetch_assoc($query_status)) {
        $status = $row['status'];
        $jumlah = $row['jumlah'];
        if (isset($statusCounts[$status])) {
            $statusCounts[$status] = $jumlah;
        }
    }
}

// Data penjadwalan pembelian
$jadwal = mysqli_query($conn, "
    SELECT p.id_pembelian, s.nama_supplier, p.barang_supplier, p.jumlah, p.tanggal, p.status, p.supplier_id 
    FROM penjadwalan_pembelian p
    JOIN supplier s ON p.supplier_id = s.supplier_id
");

// Total data pembelian
$total_pembelian = 0;
$query_pembelian = mysqli_query($conn, "SELECT COUNT(*) as total FROM penjadwalan_pembelian");
if ($query_pembelian && $row = mysqli_fetch_assoc($query_pembelian)) {
    $total_pembelian = $row['total'];
}

// Total barang
$total_barang = 0;
$query_barang = mysqli_query($conn, "SELECT COUNT(*) as total FROM barang");
if ($query_barang && $row = mysqli_fetch_assoc($query_barang)) {
    $total_barang = $row['total'];
}

// Total transaksi penjualan (jumlah pesanan)
$total_transaksi_penjualan = 0;
$query_penjualan = mysqli_query($conn, "SELECT COUNT(*) as total FROM pesanan");
if ($query_penjualan && $row = mysqli_fetch_assoc($query_penjualan)) {
    $total_transaksi_penjualan = $row['total'];
}

// Total supplier
$total_supplier = 0;
$query_supplier = mysqli_query($conn, "SELECT COUNT(*) as total FROM supplier");
if ($query_supplier && $row = mysqli_fetch_assoc($query_supplier)) {
    $total_supplier = $row['total'];
}

// Penjualan per bulan
$penjualan_per_bulan = array_fill(1, 12, 0);
$query_per_bulan = mysqli_query($conn, "
    SELECT MONTH(tanggal_pesan) as bulan, COUNT(*) as total 
    FROM pesanan 
    GROUP BY MONTH(tanggal_pesan)
");
if ($query_per_bulan) {
    while ($row = mysqli_fetch_assoc($query_per_bulan)) {
        $penjualan_per_bulan[(int)$row['bulan']] = $row['total'];
    }
}
ksort($penjualan_per_bulan);

// Detail barang
$query_barang_detail = mysqli_query($conn, "SELECT nama_barang, harga, stok FROM barang");

// Total pelanggan
$totalPelanggan = 0;
$queryPelanggan = mysqli_query($conn, "SELECT COUNT(*) as total FROM pelanggan");
if ($queryPelanggan && $row = mysqli_fetch_assoc($queryPelanggan)) {
    $totalPelanggan = $row['total'];
}
?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WEB PENYIMPANAN BARANG</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter&family=Open+Sans&family=Poppins:wght@400;600&display=swap"rel="stylesheet">
    <link rel="stylesheet" href="../css/index-kepala-marketing.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    

</head>
<style>


</style>

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
                    <li><a href="index-kepala-marketing.php"><i class='bx bx-home-alt-2'></i><span>Dashboard</span></a></li>
                    <li><a href="../pages/pelanggan-kepala-marketing.php"><i class='bx bx-package'></i><span>Data Pelanggan</span></a></li>
                    <li><a href="../pages/penjualan-kepala-marketing.php"><i class='bx bx-cart'></i><span>Data Penjualan</span></a></li>
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
                <div class="container-card">
                <div class="card" style="background-color: #ebedff;">
                    <h3><?= number_format($totalPenjualan, 0, ',', '.') ?></h3>
                    <p>Total Penjualan</p>
                    <i class='bx bx-calendar fa-2xl'></i>
                </div>
                <div class="card" style="background-color: #ebedff;">
                    <h3><?= number_format($rataTransaksi, 2, ',', '.') ?></h3>
                    <p>Rata-Rata Transaksi</p>
                    <i class='bx bx-chart fa-2xl'></i>
                </div>
                <div class="card" style="background-color: #ebedff;">
                    <h3><?= $totalPelanggan ?></h3>
                    <p>Data Pelanggan</p>
                    <i class='bx bx-user fa-2xl'></i>
                </div>
            </div>

                <br><br>
            <div class="box">
                <div class="data-grafik-container card">
                    <h2>Grafik Penjualan per Bulan</h2>
                    <canvas id="grafikPenjualan"></canvas>
                </div>
                <div class="data-jual-container card">
                    <h2>Data Barang</h2>
                    <table class="data-table">
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
                </div>
            </div>
             <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $message_type; ?>" role="alert">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
                    <div style="margin-bottom: 20px;">
                         <button class="btn btn-tambah" onclick="document.getElementById('addModal').style.display='block'">Tambah Produk Baru</button>
                    </div>

                     <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nama Produk</th>
                        <th>Harga</th>
                        <th>Diskon (%)</th>
                        <th>Gambar</th>
                        <th>Promo</th> <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($data->num_rows > 0) {
                        while($row = $data->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($row["id"]) . "</td>";
                            echo "<td>" . htmlspecialchars($row["nama_produk"]) . "</td>";
                            echo "<td>" . htmlspecialchars(number_format($row["harga"], 0, ',', '.')) . "</td>";
                            echo "<td>" . htmlspecialchars($row["diskon"]) . "</td>";
                            echo "<td><img src='../uploads/" . htmlspecialchars($row["gambar"]) . "' alt='Gambar Produk' width='50'></td>";
                            // INI BARIS PENTING UNTUK CHECKBOX PROMO
                            echo "<td><input type='checkbox' " . ($row["is_promo"] == 1 ? "checked" : "") . " onchange='updatePromoStatus(" . $row["id"] . ", this.checked)'></td>";
                            echo "<td>
                                    <button class='btn btn-edit' onclick=\"showEditModal('" . $row["id"] . "', '" . htmlspecialchars($row["nama_produk"]) . "', '" . htmlspecialchars($row["harga"]) . "', '" . htmlspecialchars($row["diskon"]) . "', '" . htmlspecialchars($row["is_promo"]) . "')\">Edit</button>
                                    <button class='btn btn-hapus' onclick=\"deleteProduct(" . $row["id"] . ")\">Hapus</button>
                                  </td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='7'>Tidak ada data produk.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>

                <br><br>
        </div>
    </div>

<!-- Modal Tambah Produk -->
<div id="addModal" class="modal">
    <div class="modal-content">
        <span class="close-button" id="closeModal">&times;</span>
        <h2>Tambah Produk Baru</h2>
        <form action="tambah_produk_landing.php" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="gambar">Gambar Produk:</label>
                <input type="file" id="gambar" name="gambar" class="form-control" accept="image/*" required>
            </div>
            <div class="form-group">
                <label for="nama_produk">Nama Produk:</label>
                <input type="text" id="nama_produk" name="nama_produk" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="harga">Harga:</label>
                <input type="number" id="harga" name="harga" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="diskon">Diskon (%):</label>
                <input type="number" id="diskon" name="diskon" class="form-control" value="0">
            </div>
            <div class="modal-buttons">
                <button type="button" class="btn-batal" id="btnBatalTambah">Batal</button>
                <button type="submit" class="btn-simpan">Simpan Produk</button>
            </div>
        </form>
    </div>
</div>
<div id="editModal" class="modal">
    <div class="modal-content">
        <span class="close-button" id="closeEditModal">&times;</span>
        <h2>Edit Produk</h2>
        <form action="update_product.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" id="editProductId" name="id">

            <div class="form-grup">
                <label for="editNamaProduk">Nama Produk:</label>
                <input type="text" id="editNamaProduk" name="nama_produk" required>
            </div>

            <div class="form-grup">
                <label for="editHarga">Harga:</label>
                <input type="number" id="editHarga" name="harga" required>
            </div>

            <div class="form-grup">
                <label for="editDiskon">Diskon (%):</label>
                <input type="number" id="editDiskon" name="diskon" min="0" max="100">
            </div>

            <div class="form-grup">
                <label for="gambar_produk_baru">Gambar Baru:</label>
                <input type="file" id="gambar_produk_baru" name="gambar_produk_baru">
                <div id="currentImagePreview">
                    </div>
                <small>Biarkan kosong jika tidak ingin mengubah gambar.</small>
            </div>
            <br>

            <div class="modal-buttons">
                <button type="button" class="btn-batal" id="btnBatalEdit">Batal</button>
                <button type="submit" class="btn-simpan">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

    <script> src="../javascript/index.js"</script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('grafikPenjualan').getContext('2d');
    const grafikPenjualan = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'],
            datasets: [{
                label: 'Jumlah Penjualan',
                data: <?= json_encode(array_values($penjualan_per_bulan)) ?>,
                backgroundColor: 'rgba(54, 162, 235, 0.5)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1,
                borderRadius: 4
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true,
                    stepSize: 1
                }
            }
        }
    });
</script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('btnBatalEdit').onclick = function() {
        document.getElementById('editModal').style.display = 'none';
    }
        // Fungsi untuk memuat produk promo
        function loadPromoProducts() {
            fetch('get_promo_products.php') // Ensure this path is correct, relative to index.html
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok ' + response.statusText);
                    }
                    return response.json();
                })
                .then(data => {
                    const promoContainer = document.getElementById('promo-container');
                    if (!promoContainer) {
                        console.error('Elemen #promo-container tidak ditemukan.');
                        return;
                    }
                    promoContainer.innerHTML = ''; // Clear container before re-populating

                    if (data.success && data.data.length > 0) {
                        data.data.forEach(product => {
                            const productCard = document.createElement('div');
                            productCard.className = 'product-card'; // Re-use existing product-card style

                            let hargaDisplay = `Rp${Number(product.harga).toLocaleString('id-ID')}`;
                            let diskonHtml = '';
                            if (product.diskon > 0) {
                                const hargaDiskon = product.harga - (product.harga * product.diskon / 100);
                                hargaDisplay = `
                                    <span style="text-decoration: line-through; color: #888;">Rp${Number(product.harga).toLocaleString('id-ID')}</span>
                                    <span style="color: #e44d26; font-weight: bold;">Rp${Number(hargaDiskon).toLocaleString('id-ID')}</span>
                                `;
                                diskonHtml = `<p style="color: green; font-weight: bold;">Diskon: ${product.diskon}%</p>`;
                            }

                            productCard.innerHTML = `
                                <img src="../uploads/${product.gambar}" alt="${product.nama_produk}" class="product-image">
                                <div class="product-info">
                                    <h3>${product.nama_produk || 'Nama Produk Tidak Tersedia'}</h3>
                                    <p>Harga: ${hargaDisplay}</p>
                                    ${diskonHtml}
                                    <a class="btn" href="https://wa.me/6281218547384" target="_blank">
                                        Pesan Sekarang
                                    </a>
                                </div>
                            `;
                            promoContainer.appendChild(productCard);
                        });
                    } else if (data.success && data.data.length === 0) {
                        promoContainer.innerHTML = '<p>Tidak ada produk promo saat ini.</p>';
                    } else {
                        console.error('Gagal memuat produk promo:', data.error);
                        promoContainer.innerHTML = '<p>Maaf, terjadi kesalahan saat memuat produk promo.</p>';
                    }
                })
                .catch(error => {
                    console.error('Ada masalah dengan operasi fetch:', error);
                    document.getElementById('promo-container').innerHTML = '<p>Gagal terhubung ke server untuk memuat promo.</p>';
                });
        }
        // Dapatkan modal untuk menambah produk
        var addModal = document.getElementById('addModal');

        // Dapatkan elemen <span> yang menutup modal tambah produk
        var closeAddModalBtn = document.getElementById('closeModal');

        // Dapatkan tombol "Batal" di modal tambah produk
        var btnBatalTambah = document.getElementById('btnBatalTambah');

        // Ketika pengguna mengklik <span> (x), tutup modal tambah produk
        if (closeAddModalBtn) {
            closeAddModalBtn.onclick = function() {
                addModal.style.display = "none";
            }
        }

        // Ketika pengguna mengklik tombol "Batal", tutup modal tambah produk
        if (btnBatalTambah) {
            btnBatalTambah.onclick = function() {
                addModal.style.display = "none";
            }
        }

        // Ketika pengguna mengklik di mana saja di luar modal tambah produk, tutup modal
        window.onclick = function(event) {
            if (event.target == addModal) {
                addModal.style.display = "none";
            }
            // Pertahankan logika yang ada untuk modal edit jika ada di sini
            const editModal = document.getElementById('editModal');
            if (event.target == editModal) {
                editModal.style.display = 'none';
            }
        }
        // Optional: If you want to reload promos periodically or after an admin clicks something,
        // you can call loadPromoProducts() again.
        // Example: setInterval(loadPromoProducts, 10000); // Reload every 10 seconds for quick updates
    });
</script>
<script>
        // Pastikan ini ada dan tidak ada error sintaks di dalamnya
        function updatePromoStatus(id, isChecked) {
            const promoStatus = isChecked ? 1 : 0; // Konversi boolean ke 1 (true) atau 0 (false)

            // PASTIKAN PATH INI SESUAI DENGAN LOKASI update_promo_status.php
            // Contoh: fetch('/SistemPergudangan/update_promo_status.php', { ...
            fetch('update_promo_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `id=${id}&is_promo=${promoStatus}`
            })
            .then(response => {
                if (!response.ok) {
                    // Jika respons HTTP bukan 2xx, throw error
                    throw new Error('Network response was not ok ' + response.statusText);
                }
                return response.json(); // Harapkan respons JSON dari server
            })
            .then(data => {
                if (data.success) {
                    alert('Status promo berhasil diperbarui!');
                    // Anda bisa menambahkan logika di sini jika ingin memperbarui tampilan tabel admin secara langsung
                } else {
                    alert('Gagal memperbarui status promo: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan saat menghubungi server: ' + error.message);
            });
        }
    </script>
    <script>
    // ... (fungsi updatePromoStatus yang sudah ada) ...

    function showEditModal(id, nama, harga, stok, diskon, deskripsi, is_promo, gambar) {
        document.getElementById('editProductId').value = id;
        document.getElementById('editNamaProduk').value = nama;
        document.getElementById('editHarga').value = harga;
        document.getElementById('editDiskon').value = diskon;
        
        // Tampilkan gambar produk yang sudah ada
        const currentImagePreview = document.getElementById('currentImagePreview');
        if (gambar) {
            currentImagePreview.innerHTML = `<img src="../../uploads/${gambar}" alt="Current Product Image" style="max-width: 150px; margin-top: 10px; display: block;">`;
        } else {
            currentImagePreview.innerHTML = '';
        }

        document.getElementById('editModal').style.display = 'block'; // Tampilkan modal edit
    }

    // Fungsi untuk menutup modal edit
    document.getElementById('closeEditModal').onclick = function() {
        document.getElementById('editModal').style.display = 'none';
    }

    // Ketika pengguna mengklik di luar modal, tutup modal
    window.onclick = function(event) {
        const editModal = document.getElementById('editModal');
        if (event.target == editModal) {
            editModal.style.display = 'none';
        }
    }
</script>
<script>
    // ... (fungsi updatePromoStatus dan showEditModal yang sudah ada) ...

    function deleteProduct(id) {
        if (confirm("Apakah Anda yakin ingin menghapus produk ini?")) {
            fetch('hapus_promo.php', { // Anda perlu membuat file PHP ini
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `id=${id}`
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok ' + response.statusText);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    alert('Produk berhasil dihapus!');
                    location.reload(); // Muat ulang halaman untuk memperbarui tabel
                } else {
                    alert('Gagal menghapus produk: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan saat menghubungi server: ' + error.message);
            });
        }
    }

    // ... (event listener atau kode JavaScript lainnya) ...
</script>


</body>

</html>
