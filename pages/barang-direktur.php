<?php


$conn = mysqli_connect("localhost", "trix2829_rafly", "kukubimaplus", "trix2829_dbpergudangan");

if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

session_start();

$username = isset($_SESSION['username']) ? $_SESSION['username'] : 'Guest';
$role = isset($_SESSION['role']) ? $_SESSION['role'] : 'Unknown';
// Asumsikan notifikasi_stok.php berfungsi dengan baik dan tidak menghasilkan output apapun
include '../main/notifikasi_stok.php';


// Query untuk menampilkan data. Ini harus dijalankan SETELAH semua kemungkinan POST request ditangani
// agar tabel menampilkan data terbaru setelah penambahan/modifikasi.
// Namun, jika Anda menggunakan AJAX untuk penambahan data dan tidak me-reload halaman penuh,
// query ini akan tetap mengambil data sebelum AJAX berhasil.
// Untuk saat ini, kita biarkan di sini, tapi perlu diingat.
$query = mysqli_query($conn, "SELECT * FROM barang");
$query_keluar = mysqli_query($conn, "SELECT * FROM barang_keluar");
$query_produk_jadi = mysqli_query($conn, "SELECT * FROM produk_jadi"); // Ini akan digunakan untuk menampilkan tabel produk jadi
$query_produk_terjual = mysqli_query($conn, "SELECT * FROM produk_terjual ORDER BY id_terjual DESC");

// --- START: Penanganan POST Request ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 1. Penanganan input barang baru (FORM DARI MODAL 'addItemModal')
    // Periksa apakah tombol submit untuk barang baru diklik (misal ada nama_barang)
    if (isset($_POST['nama_barang']) && !isset($_POST['submit_produk_jadi']) && !isset($_POST['submit_terjual']) && !isset($_POST['jumlah'])) {
        // Generate ID Barang (gunakan salah satu metode, yang sequential disarankan)
        $query_last_id = "SELECT barang_id FROM barang ORDER BY barang_id DESC LIMIT 1";
        $result_last_id = mysqli_query($conn, $query_last_id);
        $barang_id = 'BRG001'; // Default jika tabel kosong

        if (mysqli_num_rows($result_last_id) > 0) {
            $last_id_row = mysqli_fetch_assoc($result_last_id);
            $last_id = $last_id_row['barang_id'];
            if (preg_match('/^BRG(\d+)$/', $last_id, $matches)) {
                $num = (int)$matches[1] + 1;
                $barang_id = 'BRG' . str_pad($num, 3, '0', STR_PAD_LEFT);
            }
        }

        // Ambil data dari form dan sanitasi
        $nama_barang = mysqli_real_escape_string($conn, $_POST['nama_barang']);
        $kategori = mysqli_real_escape_string($conn, $_POST['kategori']);
        $harga = floatval($_POST['harga']);
        $stok = intval($_POST['stok']);

        // Query INSERT menggunakan Prepared Statement
        $stmt = $conn->prepare("INSERT INTO barang (barang_id, nama_barang, kategori, harga, stok) VALUES (?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("sssdi", $barang_id, $nama_barang, $kategori, $harga, $stok);
            if ($stmt->execute()) {
                header("Location: barang-direktur.php?status=success_add_barang");
                exit();
            } else {
                echo "Error: " . $stmt->error;
            }
            $stmt->close();
        } else {
            echo "Error preparing statement: " . $conn->error;
        }
    }

    // 2. Penanganan input produk jadi (FORM DARI MODAL 'produkModal')
    // Periksa apakah tombol submit untuk produk jadi diklik (misal ada nama_produk)
    if (isset($_POST['nama_produk']) && !isset($_POST['nama_barang']) && !isset($_POST['submit_terjual']) && !isset($_POST['jumlah'])) {
        // Ambil data dari form produk jadi dan sanitasi
        $nama_produk = mysqli_real_escape_string($conn, $_POST['nama_produk']);
        $stok_produk_jadi = intval($_POST['stok']); // Menggunakan nama variabel berbeda agar tidak bentrok
        $tanggal_produksi = mysqli_real_escape_string($conn, $_POST['tanggal_produksi']);
        $harga_jual = floatval($_POST['harga_jual']);

        // Untuk id_produk_jadi, kita asumsikan AUTO_INCREMENT di DB
        // Jika tidak AUTO_INCREMENT, Anda perlu membuat logika ID unik seperti barang_id
        // Untuk contoh ini, kita asumsikan id_produk_jadi adalah AUTO_INCREMENT

        // Query INSERT produk jadi menggunakan Prepared Statement
        $stmt_produk_jadi = $conn->prepare("INSERT INTO produk_jadi (nama_produk, stok, tanggal_produksi, harga_jual) VALUES (?, ?, ?, ?)");
        if ($stmt_produk_jadi) {
            // Perbaiki: harga_jual adalah float, jadi pakai 'd'
            $stmt_produk_jadi->bind_param("sisd", $nama_produk, $stok_produk_jadi, $tanggal_produksi, $harga_jual);

            if ($stmt_produk_jadi->execute()) {
                header("Location: barang-direktur.php?status=success_add_produk_jadi");
                exit();
            } else {
                echo "Error: " . $stmt_produk_jadi->error;
            }
            $stmt_produk_jadi->close();
        } else {
            echo "Error preparing statement for produk_jadi: " . $conn->error;
        }
    }

    // 3. Mengelola data penjualan produk jadi (FORM DARI MODAL 'produkTerjualModal')
    // Perhatikan: ini akan tumpang tindih dengan 'nama_barang' jika tidak hati-hati,
    // karena form penjualan juga punya input 'nama_barang'.
    // Solusi: gunakan nama input submit yang unik untuk setiap form.
    if (isset($_POST['submit_terjual'])) {
        $id_produk_jadi = intval($_POST['id_produk_jadi']);
        $jumlah_terjual = intval($_POST['jumlah_terjual']);
        $tanggal_terjual = mysqli_real_escape_string($conn, $_POST['tanggal_terjual']);
        $catatan = mysqli_real_escape_string($conn, $_POST['catatan']);

        $query_stok = mysqli_query($conn, "SELECT harga_jual, stok FROM produk_jadi WHERE id_produk_jadi = '$id_produk_jadi'");
        $data = mysqli_fetch_assoc($query_stok);
        $harga_jual_produk = $data['harga_jual']; // Ambil harga jual dari produk_jadi
        $stok_saat_ini_produk = $data['stok'];

        if ($jumlah_terjual > $stok_saat_ini_produk) {
            echo "<script>alert('Stok produk jadi tidak mencukupi!'); window.location.href='barang-direktur.php';</script>";
            exit();
        }

        $stok_baru_produk = $stok_saat_ini_produk - $jumlah_terjual;

        // Update stok produk_jadi
        $stmt_update_produk_jadi = $conn->prepare("UPDATE produk_jadi SET stok = ? WHERE id_produk_jadi = ?");
        if ($stmt_update_produk_jadi) {
            $stmt_update_produk_jadi->bind_param("ii", $stok_baru_produk, $id_produk_jadi);
            if ($stmt_update_produk_jadi->execute()) {
                // Masukkan data produk terjual
                $stmt_insert_terjual = $conn->prepare("INSERT INTO produk_terjual (id_produk_jadi, jumlah_terjual, tanggal_terjual, catatan, harga_satuan) VALUES (?, ?, ?, ?, ?)");
                if ($stmt_insert_terjual) {
                    // Perbaiki: harga_jual_produk tipe float, jadi 'd'
                    $stmt_insert_terjual->bind_param("iisds", $id_produk_jadi, $jumlah_terjual, $tanggal_terjual, $harga_jual_produk, $catatan);
                    if ($stmt_insert_terjual->execute()) {
                        header("Location: barang-direktur.php?status=success_terjual");
                        exit();
                    } else {
                        echo "Error insert terjual: " . $stmt_insert_terjual->error;
                    }
                    $stmt_insert_terjual->close();
                } else {
                    echo "Error preparing insert terjual statement: " . $conn->error;
                }
            } else {
                echo "Error update produk jadi stok: " . $stmt_update_produk_jadi->error;
            }
            $stmt_update_produk_jadi->close();
        } else {
            echo "Error preparing update produk jadi statement: " . $conn->error;
        }
    }


    // 4. Mengelola data barang keluar (FORM DARI MODAL 'barangKeluarModal')
    // Periksa apakah tombol submit untuk barang keluar diklik (misal ada id_barang dan jumlah)
    if (isset($_POST['id_barang']) && isset($_POST['jumlah']) && !isset($_POST['nama_barang']) && !isset($_POST['nama_produk']) && !isset($_POST['submit_terjual'])) {
        $id_barang = mysqli_real_escape_string($conn, $_POST['id_barang']);
        $jumlah_keluar = intval($_POST['jumlah']);
        $tanggal_keluar = mysqli_real_escape_string($conn, $_POST['tanggal_keluar']);
        $keterangan = mysqli_real_escape_string($conn, $_POST['keterangan']);

        // 1. Ambil stok barang saat ini
        $query_stok_barang = mysqli_query($conn, "SELECT stok FROM barang WHERE barang_id = '$id_barang'");
        $data_stok_barang = mysqli_fetch_assoc($query_stok_barang);
        $stok_saat_ini_barang = $data_stok_barang['stok'];

        // 2. Hitung stok baru
        $stok_baru_barang = $stok_saat_ini_barang - $jumlah_keluar;

        // Validasi stok
        if ($stok_baru_barang < 0) {
            echo "<script>alert('Stok barang tidak mencukupi!'); window.location.href='barang-direktur.php';</script>";
            exit();
        }

        // 3. Update stok barang
        $stmt_update_stok_barang = $conn->prepare("UPDATE barang SET stok = ? WHERE barang_id = ?");
        if ($stmt_update_stok_barang) {
            $stmt_update_stok_barang->bind_param("is", $stok_baru_barang, $id_barang);
            if ($stmt_update_stok_barang->execute()) {
                // 4. Masukkan data barang keluar
                $stmt_insert_keluar = $conn->prepare("INSERT INTO barang_keluar (barang_id, jumlah, tanggal_keluar, keterangan) VALUES (?, ?, ?, ?)");
                if ($stmt_insert_keluar) {
                    $stmt_insert_keluar->bind_param("siss", $id_barang, $jumlah_keluar, $tanggal_keluar, $keterangan);
                    if ($stmt_insert_keluar->execute()) {
                        header("Location: barang-direktur.php?status=success_barang_keluar");
                        exit();
                    } else {
                        echo "Error insert barang keluar: " . $stmt_insert_keluar->error;
                    }
                    $stmt_insert_keluar->close();
                } else {
                    echo "Error preparing insert barang keluar statement: " . $conn->error;
                }
            } else {
                echo "Error update stok barang: " . $stmt_update_stok_barang->error;
            }
            $stmt_update_stok_barang->close();
        } else {
            echo "Error preparing update stok barang statement: " . $conn->error;
        }
    }
}
// --- END: Penanganan POST Request ---

// Setelah semua kemungkinan POST request ditangani, jalankan ulang query untuk menampilkan data terbaru.
// Ini penting jika Anda tidak menggunakan AJAX untuk memperbarui tabel secara dinamis.
$query = mysqli_query($conn, "SELECT * FROM barang");
$query_keluar = mysqli_query($conn, "SELECT * FROM barang_keluar");
$query_produk_jadi = mysqli_query($conn, "SELECT * FROM produk_jadi");
$query_produk_terjual = mysqli_query($conn, "SELECT * FROM produk_terjual ORDER BY id_terjual DESC");

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Barang</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter&family=Open+Sans&family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/barang.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <style>
        /* Add specific styles for status spans if not already in index.css */
        .status-habis {
            color: var(--danger-color);
            font-weight: 600;
        }
        .status-menipis {
            color: var(--accent-color);
            font-weight: 600;
        }
        .status-tersedia {
            color: var(--success-color);
            font-weight: 600;
        }
    </style>
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
                    <li>
                        <div class="logo">
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
                    <li><a href="barang-direktur.php" class="active"><i class='bx bx-package'></i><span>Data Barang</span></a></li>
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
            <h2>Data Barang</h2>
                <div class="data-table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Barang ID</th>
                                <th>Nama Barang</th>
                                <th>Kategori</th>
                                <th>Harga /pack</th>
                                <th>Stok (pack)</th>
                                <th>Status</th>
                                </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Pastikan query 'barang' dijalankan ulang untuk menampilkan data terbaru
                            // if ($_SERVER["REQUEST_METHOD"] == "POST") { $query = mysqli_query($conn, "SELECT * FROM barang"); }
                            while ($row = mysqli_fetch_assoc($query)) {
                                $stok = $row['stok'];
                                if ($stok == 0) {
                                    $status = '<span class="status-habis">Habis</span>';
                                } elseif ($stok < 100) {
                                    $status = '<span class="status-menipis">Stok Menipis</span>';
                                } else {
                                    $status = '<span class="status-tersedia">Tersedia</span>';
                                }

                                // Menghitung harga jual berdasarkan stok dan harga
                                // Formula: (harga / stok) * jumlah terjual, jika ada data penjualan
                                $harga = (float)$row['harga']; // Pastikan harga diubah menjadi float untuk perhitungan
                                $jumlah_terjual = 0; // Gantilah dengan query untuk jumlah terjual dari database yang sesuai
                                // Harga jual di sini sepertinya salah perhitungan. Ini harusnya harga_beli per pack.
                                // Harga jual biasanya disimpan di tabel produk_jadi atau tabel penjualan itu sendiri.
                                $harga_jual_display = 0; // Inisialisasi harga jual untuk display di tabel barang
                            ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['barang_id']); ?></td>
                                        <td><?php echo htmlspecialchars($row['nama_barang']); ?></td>
                                        <td><?php echo htmlspecialchars($row['kategori']); ?></td>
                                        <td><?php echo number_format($harga, 0, ',', '.'); ?></td>
                                        <td><?php echo $stok; ?></td>
                                        <td><?php echo $status; ?></td>
                                        </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div><br><br><br>

            <h2>Data Produk</h2>
            <div class="data-table-container">
                <table class="data-table">
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
                        // Pastikan query 'produk_jadi' dijalankan ulang untuk menampilkan data terbaru
                        // if ($_SERVER["REQUEST_METHOD"] == "POST") { $query_produk_jadi = mysqli_query($conn, "SELECT * FROM produk_jadi"); }
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
                </table>
            </div><br><br><br>
            <h2> Barang Keluar</h2>
            <div class="data-table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID Keluar</th>
                            <th>Barang ID</th>
                            <th>Jumlah</th>
                            <th>Tanggal</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    // Pastikan query 'barang_keluar' dijalankan ulang
                    // if ($_SERVER["REQUEST_METHOD"] == "POST") { $query_keluar = mysqli_query($conn, "SELECT * FROM barang_keluar"); }
                    while ($row = mysqli_fetch_assoc($query_keluar)) { ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['id_keluar']); ?></td>
                            <td><?php echo htmlspecialchars($row['barang_id']); ?></td>
                            <td><?php echo $row['jumlah']; ?></td>
                            <td><?php echo htmlspecialchars($row['tanggal_keluar']); ?></td>
                            <td><?php echo htmlspecialchars($row['keterangan']); ?></td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
            </div><br><br><br>

        </div>
    </div>
    </body>
</html>