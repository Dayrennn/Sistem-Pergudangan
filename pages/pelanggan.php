<?php
include '../main/koneksi.php';
session_start();

$username = isset($_SESSION['username']) ? $_SESSION['username'] : 'Guest';
$role = isset($_SESSION['role']) ? $_SESSION['role'] : 'Unknown';

// Check if it's an AJAX request (optional, but good practice)
$is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// Existing showError function (keep as is for non-AJAX fallback if needed)
if (!function_exists('showError')) {
    function showError($conn, $stmt = null, $message = "Terjadi kesalahan.")
    {
        error_log($message . " - " . ($stmt ? $stmt->error : mysqli_error($conn)));
        $_SESSION['error'] = $message . " - " . ($stmt ? $stmt->error : mysqli_error($conn));
        // Only redirect if not an AJAX request
        if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
            header("Location: pelanggan.php");
            exit;
        }
        // For AJAX, return JSON error
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $message . " - " . ($stmt ? $stmt->error : mysqli_error($conn))]);
        exit;
    }
}

// Penanganan POST request untuk simpan/update pelanggan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_pelanggan'])) {
    $conn->begin_transaction();
    try {
        // Ambil data dari form
        $pelanggan_id = $_POST['pelanggan_id'] ?? null;
        $nama_pelanggan = trim($_POST['nama_pelanggan']);
        $kontak = trim($_POST['kontak']);
        $email = trim($_POST['email']);
        $alamat = trim($_POST['alamat']);
        $barang_id = $_POST['barang_id'] ?? null;
        $jumlah_pesan = (int)($_POST['jumlah_pesan'] ?? 0);

        // Validasi data pelanggan
        if (empty($nama_pelanggan) || empty($kontak) || empty($email) || empty($alamat)) {
            throw new Exception("Semua field pelanggan wajib diisi");
        }

        // Simpan/update data pelanggan
        if ($pelanggan_id) {
            // Mode Edit
            $stmt = $conn->prepare("UPDATE pelanggan SET nama_pelanggan=?, kontak=?, email=?, alamat=? WHERE pelanggan_id=?");
            $stmt->bind_param("ssssi", $nama_pelanggan, $kontak, $email, $alamat, $pelanggan_id);
        } else {
            // Mode Tambah
            $stmt = $conn->prepare("INSERT INTO pelanggan (nama_pelanggan, kontak, email, alamat) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $nama_pelanggan, $kontak, $email, $alamat);
        }

        if (!$stmt->execute()) {
            throw new Exception("Gagal menyimpan data pelanggan");
        }

        if (!$pelanggan_id) {
            $pelanggan_id = $conn->insert_id;
        }
        $stmt->close();

        // Proses pesanan jika ada barang yang dipilih
        if ($barang_id && $jumlah_pesan > 0) {
            // Ambil data produk
            $stmt = $conn->prepare("SELECT harga_jual, stok FROM produk_jadi WHERE id_produk_jadi=?");
            $stmt->bind_param("i", $barang_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $produk = $result->fetch_assoc();
            $stmt->close();

            if (!$produk) {
                throw new Exception("Produk tidak ditemukan");
            }

            // Validasi stok
            if ($jumlah_pesan > $produk['stok']) {
                throw new Exception("Stok tidak cukup. Stok tersedia: " . $produk['stok']);
            }

            $total_harga = $produk['harga_jual'] * $jumlah_pesan;

            // Cek apakah sudah ada pesanan untuk pelanggan ini
            // **IMPORTANT**: The original code updates an existing order or inserts a new one.
            // If editing a customer, and they already have an order, you might need to handle
            // the stock update carefully: decrement original stock, then increment for new order.
            // For simplicity here, we assume if editing a customer, the old order details
            // are overridden and stock is adjusted based on the *new* quantity.
            // A more robust solution might involve retrieving the *old* order quantity
            // and adjusting stock difference.
            $stmt = $conn->prepare("SELECT pesanan_id, jumlah_pesan FROM pesanan WHERE pelanggan_id=?");
            $stmt->bind_param("i", $pelanggan_id);
            $stmt->execute();
            $existing_order = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($existing_order) {
                // Update pesanan yang sudah ada
                $old_jumlah_pesan = (int)$existing_order['jumlah_pesan'];

                // Update stok: return old quantity, subtract new quantity
                $stmt_stock_revert = $conn->prepare("UPDATE produk_jadi SET stok = stok + ? WHERE id_produk_jadi = ?");
                $stmt_stock_revert->bind_param("ii", $old_jumlah_pesan, $barang_id); // Assuming barang_id from the form is the same as the old one, this needs careful consideration if barang_id can change.
                $stmt_stock_revert->execute();
                $stmt_stock_revert->close();

                $stmt = $conn->prepare("UPDATE pesanan SET 
                                      barang_id=?, 
                                      jumlah_pesan=?, 
                                      total_harga=?, 
                                      tanggal_pesan=NOW(), 
                                      status='pending' 
                                      WHERE pelanggan_id=?");
                $stmt->bind_param("iidi", $barang_id, $jumlah_pesan, $total_harga, $pelanggan_id);
            } else {
                // Insert pesanan baru
                $stmt = $conn->prepare("INSERT INTO pesanan 
                                      (pelanggan_id, barang_id, jumlah_pesan, total_harga, tanggal_pesan, status) 
                                      VALUES (?, ?, ?, ?, NOW(), 'pending')");
                $stmt->bind_param("iids", $pelanggan_id, $barang_id, $jumlah_pesan, $total_harga);
            }

            if (!$stmt->execute()) {
                throw new Exception("Gagal menyimpan data pesanan");
            }
            $stmt->close();

            // Update stok produk (subtract new quantity)
            $stmt = $conn->prepare("UPDATE produk_jadi SET stok = stok - ? WHERE id_produk_jadi = ?");
            $stmt->bind_param("ii", $jumlah_pesan, $barang_id);

            if (!$stmt->execute()) {
                throw new Exception("Gagal update stok produk");
            }
            $stmt->close();
        } else if ($pelanggan_id) {
            // If it's an edit and no product is selected (or quantity is 0),
            // you might want to remove existing orders or revert stock.
            // For now, let's just make sure existing orders are handled.
            // If the user *removed* the product from an existing order,
            // you'd need to delete the order and revert stock.
            // This is a simplified example; a real-world app needs more robust logic.
            $stmt = $conn->prepare("SELECT pesanan_id, jumlah_pesan, barang_id FROM pesanan WHERE pelanggan_id=?");
            $stmt->bind_param("i", $pelanggan_id);
            $stmt->execute();
            $existing_order_to_remove = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($existing_order_to_remove) {
                // Revert stock for the old order
                $stmt_stock_revert = $conn->prepare("UPDATE produk_jadi SET stok = stok + ? WHERE id_produk_jadi = ?");
                $stmt_stock_revert->bind_param("ii", $existing_order_to_remove['jumlah_pesan'], $existing_order_to_remove['barang_id']);
                $stmt_stock_revert->execute();
                $stmt_stock_revert->close();

                // Delete the old order
                $stmt_delete_order = $conn->prepare("DELETE FROM pesanan WHERE pesanan_id = ?");
                $stmt_delete_order->bind_param("i", $existing_order_to_remove['pesanan_id']);
                $stmt_delete_order->execute();
                $stmt_delete_order->close();
            }
        }


        $conn->commit();
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Data pelanggan berhasil disimpan']);
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Gagal menyimpan data: ' . $e->getMessage()]);
        exit;
    }
}

// ... rest of your PHP code for displaying the page ...

// Query untuk menampilkan data pelanggan beserta pesanan
$query_pelanggan = mysqli_query($conn, "
    SELECT 
        p.*,
        GROUP_CONCAT(DISTINCT pj.nama_produk SEPARATOR ', ') AS produk,
        GROUP_CONCAT(DISTINCT ps.jumlah_pesan SEPARATOR ', ') AS jumlah,
        GROUP_CONCAT(DISTINCT ps.barang_id SEPARATOR ', ') AS barang_ids,
        GROUP_CONCAT(DISTINCT pj.harga_jual SEPARATOR ', ') AS harga_satuan,
        GROUP_CONCAT(DISTINCT ps.total_harga SEPARATOR ', ') AS total_hargas,
        GROUP_CONCAT(DISTINCT ps.tanggal_pesan SEPARATOR ', ') AS tanggal_pesanan,
        GROUP_CONCAT(DISTINCT ps.status SEPARATOR ', ') AS status_pesanan
    FROM pelanggan p
    LEFT JOIN pesanan ps ON p.pelanggan_id = ps.pelanggan_id
    LEFT JOIN produk_jadi pj ON ps.barang_id = pj.id_produk_jadi
    GROUP BY p.pelanggan_id
    ORDER BY p.nama_pelanggan ASC
") or die('Query failed: ' . mysqli_error($conn));

// Query untuk dropdown produk
$query_produk = mysqli_query($conn, "SELECT * FROM produk_jadi WHERE stok > 0 ORDER BY nama_produk")
    or die('Query failed: ' . mysqli_error($conn));
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Data Pelanggan</title>
    <link rel="stylesheet" href="../css/pelanggan.css">
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
                <li><a href="../main/index.php"><i class='bx bx-home-alt-2'></i><span>Dashboard</span></a></li>
                <li><a href="barang.php"><i class='bx bx-package'></i><span>Data Barang</span></a></li>
                <li><a href="penjualan.php"><i class='bx bx-cart'></i><span>Data Penjualan</span></a></li>
                <li><a href="supplier.php"><i class='bx bx-store-alt'></i><span>Data Supplier</span></a></li>
                <li><a href="pelanggan.php"><i class='bx bx-user'></i><span>Data Pelanggan</span></a></li>
                <li><a href="penjadwalan-pembelian.php"><i class='bx bx-user'></i></i><span>Penjadwalan Pembelian</span></a></li>
                <li><a href="data-keuangan.php"><i class='bx bx-dollar'></i><span>Data Keuangan</span></a></li>
                <li><a href="data-pembelian.php"><i class='bx bx-dollar'></i><span>Data Pembelian</span></a></li>
                <li><a href="../main/register.php"><i class='bx bx-user'></i></i><span>Daftarkan Pegawai</span></a></li>
                <li>
                    <form action="../main/logout.php" method="POST">
                        <button type="submit" class="btn-logout-sidebar"><span>Logout</span></button>
                    </form>
                </li>
            </ul>
        </aside>
        <div class="beranda">
            <br><br>
            <h2>Data Pelanggan</h2>
            <div class="search-container">
                <button id="openModalBtn">Tambah Pelanggan</button>
                <form method="GET" action="pelanggan.php">
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
                    $no = 1;
                    while ($pelanggan = mysqli_fetch_assoc($query_pelanggan)) {
                        $pelanggan_id = $pelanggan['pelanggan_id'];
                        $query_pesanan = $conn->query("SELECT p.pesanan_id,
                                                p.barang_id, 
                                                pj.nama_produk,
                                                pj.harga_jual,
                                                p.jumlah_pesan,
                                                p.total_harga,
                                                p.tanggal_pesan,
                                                p.status
                                            FROM pesanan p
                                            JOIN produk_jadi pj ON p.barang_id = pj.id_produk_jadi
                                            WHERE p.pelanggan_id = '$pelanggan_id'") or die('Query failed: ' . mysqli_error($conn));

                        // Check if there are any orders for this customer
                        if (mysqli_num_rows($query_pesanan) > 0) {
                            while ($pesanan = mysqli_fetch_assoc($query_pesanan)) {
                                $pesanan_id = $pesanan['pesanan_id'];
                                $harga_satuan = (float)$pesanan['harga_jual'];
                                $jumlah_pesan = (int)$pesanan['jumlah_pesan'];
                                $total_harga = $harga_satuan * $jumlah_pesan;
                                $status = strtolower($pesanan['status']);

                                echo "<tr>
                                <td>{$no}</td>
                                <td>{$pelanggan['nama_pelanggan']}</td>
                                <td>{$pelanggan['kontak']}</td>
                                <td>{$pelanggan['email']}</td>
                                <td>{$pelanggan['alamat']}</td>
                                <td>{$pesanan['nama_produk']}</td>
                                <td>{$jumlah_pesan}</td>
                                <td>" . number_format($harga_satuan, 2, ',', '.') . "</td>
                                <td>" . number_format($total_harga, 2, ',', '.') . "</td>
                                <td>{$pesanan['tanggal_pesan']}</td>
                                <td>{$pesanan['status']}</td>
                                <td class='action-buttons'>
                                    <button class='editPelangganBtn'
                                        data-id='{$pelanggan['pelanggan_id']}'
                                        data-nama='{$pelanggan['nama_pelanggan']}'
                                        data-kontak='{$pelanggan['kontak']}'
                                        data-email='{$pelanggan['email']}'
                                        data-alamat='{$pelanggan['alamat']}'
                                        data-barang-id='{$pesanan['barang_id']}'
                                        data-jumlah-pesan='{$pesanan['jumlah_pesan']}'>
                                        <i class='bx bx-edit'></i> Edit
                                    </button>
                                    <button class='hapusPelangganBtn' data-id='{$pelanggan['pelanggan_id']}'>
                                        <i class='bx bx-trash'></i> Hapus
                                    </button>
                                </td>
                            </tr>";
                                $no++;
                            }
                        } else {
                            // Display customer even if no order
                            echo "<tr>
                            <td>{$no}</td>
                            <td>{$pelanggan['nama_pelanggan']}</td>
                            <td>{$pelanggan['kontak']}</td>
                            <td>{$pelanggan['email']}</td>
                            <td>{$pelanggan['alamat']}</td>
                            <td>-</td>
                            <td>-</td>
                            <td>-</td>
                            <td>-</td>
                            <td>-</td>
                            <td>-</td>
                            <td class='action-buttons'>
                                <button class='editPelangganBtn'
                                    data-id='{$pelanggan['pelanggan_id']}'
                                    data-nama='{$pelanggan['nama_pelanggan']}'
                                    data-kontak='{$pelanggan['kontak']}'
                                    data-email='{$pelanggan['email']}'
                                    data-alamat='{$pelanggan['alamat']}'
                                    data-barang-id=''
                                    data-jumlah-pesan=''>
                                    <i class='bx bx-edit'></i> Edit
                                </button>
                                <button class='hapusPelangganBtn' data-id='{$pelanggan['pelanggan_id']}'>
                                    <i class='bx bx-trash'></i> Hapus
                                </button>
                            </td>
                        </tr>";
                            $no++;
                        }
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="modalPelanggan" class="modal">
        <div class="modal-content">
            <span class="close-btn">&times;</span>
            <h2 id="modalTitle">Tambah Pelanggan Baru</h2>
            <form id="formPelanggan" method="POST" action="pelanggan.php">
                <input type="hidden" name="pelanggan_id" id="pelanggan_id">

                <div class="form-group">
                    <label for="nama_pelanggan">Nama Pelanggan: <span class="required">*</span></label>
                    <input type="text" id="nama_pelanggan" name="nama_pelanggan" required>
                </div>

                <div class="form-group">
                    <label for="kontak">Kontak: <span class="required">*</span></label>
                    <input type="text" id="kontak" name="kontak" required>
                </div>

                <div class="form-group">
                    <label for="email">Email: <span class="required">*</span></label>
                    <input type="email" id="email" name="email" required>
                </div>

                <div class="form-group">
                    <label for="alamat">Alamat: <span class="required">*</span></label>
                    <textarea id="alamat" name="alamat" rows="3" required></textarea>
                </div>

                <div class="form-group">
                    <label for="barang_id">Pilih Produk:</label>
                    <select id="barang_id" name="barang_id" class="form-control">
                        <option value="">-- Pilih Produk --</option>
                        <?php
                        // Re-fetch products for the modal to ensure up-to-date stok
                        $query_produk_modal = mysqli_query($conn, "SELECT * FROM produk_jadi WHERE stok > 0");
                        while ($produk = mysqli_fetch_assoc($query_produk_modal)):
                        ?>
                            <option value="<?= $produk['id_produk_jadi'] ?>"
                                data-harga="<?= $produk['harga_jual'] ?>"
                                data-stok="<?= $produk['stok'] ?>">
                                <?= $produk['nama_produk'] ?>
                                (Rp <?= number_format($produk['harga_jual'], 0, ',', '.') ?>)
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="jumlah_pesan">Jumlah:</label>
                    <input type="number" id="jumlah_pesan" name="jumlah_pesan" min="0" value="0" class="form-control"> <small id="stokHelp" class="form-text text-muted"></small>
                </div>

                <div class="form-group">
                    <label>Total Harga:</label>
                    <p id="total_harga_display">Rp 0</p>
                    <input type="hidden" id="total_harga" name="total_harga" value="0">
                </div>

                <div class="form-actions">
                    <button type="button" class="btn-cancel" id="batalBtn">Batal</button>
                    <button type="submit" name="simpan_pelanggan" class="btn-submit">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <div id="modalHapus" class="modal">
        <div class="modal-content">
            <span class="close-btn">&times;</span>
            <h2>Konfirmasi Hapus</h2>
            <p>Apakah Anda yakin ingin menghapus pelanggan ini? Semua data pesanan terkait juga akan dihapus.</p>
            <form id="formHapusPelanggan" method="POST" action="hapus_pelanggan.php">
                <input type="hidden" name="hapus_pelanggan" id="hapus_pelanggan_id">
                <div class="button-group">
                    <button type="button" class="btn-cancel" id="batalHapusBtn">Batal</button>
                    <button type="submit" class="btn-danger" id="confirmHapusBtn">Ya, Hapus</button>
                </div>
            </form>
        </div>
    </div>
    <script src="../javascript/pelanggan.js"></script>

</body>

</html>