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

// --- Ambil data Supplier untuk Dropdown (digunakan di modal Tambah & Edit Barang) ---
$supplierResult_barang = mysqli_query($conn, "SELECT supplier_id, nama_supplier FROM supplier ORDER BY nama_supplier ASC");
if (!$supplierResult_barang) {
    die("Query supplier gagal di barang.php: " . mysqli_error($conn));
}
// --- Akhir penambahan untuk supplier dropdown ---


// --- START: Penanganan POST Request (Untuk Add Barang Mentah, Add Produk Jadi, Add Barang Keluar, Add Produk Terjual) --
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 1. Penanganan input BARANG MENTAH baru (FORM DARI MODAL TAMBAH BARANG)
    if (isset($_POST['add_barang'])) {
        $nama_barang = $_POST['nama_barang'];
        $kategori = $_POST['kategori'] ?? null;
        $harga = floatval($_POST['harga'] ?? 0);
        $stok = intval($_POST['stok']);
        $supplier_id = intval($_POST['supplier_id'] ?? 0);

        if (empty($nama_barang) || $stok < 0 || $supplier_id <= 0 || empty($kategori) || $harga < 0) {
            echo '<script>alert("Semua field (Nama Barang, Kategori, Harga, Stok, Supplier) harus diisi dengan valid untuk Barang Mentah."); window.location.href="barang.php";</script>';
            exit();
        }

        $sql_add = "INSERT INTO barang (nama_barang, kategori, harga, stok, supplier_id) VALUES (?, ?, ?, ?, ?)";
        $stmt_add = $conn->prepare($sql_add);

        if ($stmt_add) {
            $stmt_add->bind_param("ssdis", $nama_barang, $kategori, $harga, $stok, $supplier_id);
            if ($stmt_add->execute()) {
                echo '<script>alert("Barang berhasil ditambahkan."); window.location.href="barang.php";</script>';
                exit();
            } else {
                echo '<script>alert("Gagal menambahkan barang: ' . $stmt_add->error . '"); window.location.href="barang.php";</script>';
                exit();
            }
            $stmt_add->close();
        } else {
            echo '<script>alert("Gagal menyiapkan statement tambah barang: ' . $conn->error . '"); window.location.href="barang.php";</script>';
            exit();
        }
    }

    // 2. Penanganan input PRODUK JADI
    if (isset($_POST['add_produk_jadi'])) {
        $nama_produk = $_POST['nama_produk'];
        $stok_produk_jadi = intval($_POST['stok']);
        $tanggal_produksi = $_POST['tanggal_produksi'] ?? date('Y-m-d');
        $harga_jual = floatval($_POST['harga_jual']);

        if (empty($nama_produk) || $stok_produk_jadi < 0 || $harga_jual < 0) {
            echo '<script>alert("Nama produk, stok, atau harga jual tidak valid untuk Produk Jadi."); window.location.href="barang.php";</script>';
            exit();
        }

        $stmt_produk_jadi = $conn->prepare("INSERT INTO produk_jadi (nama_produk, stok, tanggal_produksi, harga_jual) VALUES (?, ?, ?, ?)");
        if ($stmt_produk_jadi) {
            $stmt_produk_jadi->bind_param("sisd", $nama_produk, $stok_produk_jadi, $tanggal_produksi, $harga_jual);

            if ($stmt_produk_jadi->execute()) {
                echo '<script>alert("Produk Jadi berhasil ditambahkan."); window.location.href="barang.php";</script>';
                exit();
            } else {
                echo '<script>alert("Error menambahkan produk jadi: ' . $stmt_produk_jadi->error . '"); window.location.href="barang.php";</script>';
                exit();
            }
            $stmt_produk_jadi->close();
        } else {
            echo '<script>alert("Error preparing statement for produk_jadi: ' . $conn->error . '"); window.location.href="barang.php";</script>';
            exit();
        }
    }

    // 3. Mengelola data PENJUALAN PRODUK JADI
    if (isset($_POST['add_produk_terjual'])) {
        $id_produk_jadi_terjual = intval($_POST['id_produk_jadi']);
        $jumlah_terjual = intval($_POST['jumlah_terjual']);
        $tanggal_terjual = $_POST['tanggal_terjual'];
        $alamat_customer = $_POST['alamat'] ?? '';
        $pelanggan_id_input = intval($_POST['pelanggan_id'] ?? 0);
        $status_pembayaran_input = $_POST['status_pembayaran'] ?? 'Belum Lunas';

        if ($id_produk_jadi_terjual <= 0 || $jumlah_terjual <= 0 || empty($tanggal_terjual) || empty($alamat_customer)) {
            echo "<script>alert('ID Produk, Jumlah, Tanggal Terjual, atau Alamat tidak valid.'); window.location.href='barang.php';</script>";
            exit();
        }

        $stmt_get_stok = $conn->prepare("SELECT harga_jual, stok FROM produk_jadi WHERE id_produk_jadi = ?");
        if ($stmt_get_stok) {
            $stmt_get_stok->bind_param("i", $id_produk_jadi_terjual);
            $stmt_get_stok->execute();
            $result_stok = $stmt_get_stok->get_result();
            $data_produk = $result_stok->fetch_assoc();
            $stmt_get_stok->close();

            if (!$data_produk) {
                echo "<script>alert('Produk tidak ditemukan!'); window.location.href='barang.php';</script>";
                exit();
            }

            $harga_jual_produk = $data_produk['harga_jual'];
            $stok_saat_ini_produk = $data_produk['stok'];

            if ($jumlah_terjual > $stok_saat_ini_produk) {
                echo "<script>alert('Stok produk jadi tidak mencukupi!'); window.location.href='barang.php';</script>";
                exit();
            }

            $stok_baru_produk = $stok_saat_ini_produk - $jumlah_terjual;

            $stmt_update_produk_jadi = $conn->prepare("UPDATE produk_jadi SET stok = ? WHERE id_produk_jadi = ?");
            if ($stmt_update_produk_jadi) {
                $stmt_update_produk_jadi->bind_param("ii", $stok_baru_produk, $id_produk_jadi_terjual);
                if ($stmt_update_produk_jadi->execute()) {
                    $stmt_update_produk_jadi->close();

                    $stmt_insert_terjual = $conn->prepare("INSERT INTO produk_terjual (id_produk_jadi, jumlah_terjual, tanggal_terjual, alamat, pelanggan_id, harga, status_pembayaran) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    if ($stmt_insert_terjual) {
                        $stmt_insert_terjual->bind_param("iisdis", $id_produk_jadi_terjual, $jumlah_terjual, $tanggal_terjual, $alamat_customer, $pelanggan_id_input, $harga_jual_produk, $status_pembayaran_input);
                        if ($stmt_insert_terjual->execute()) {
                            echo '<script>alert("Penjualan produk berhasil dicatat."); window.location.href="barang.php";</script>';
                            exit();
                        } else {
                            echo '<script>alert("Error mencatat penjualan: ' . $stmt_insert_terjual->error . '"); window.location.href="barang.php";</script>';
                            exit();
                        }
                        $stmt_insert_terjual->close();
                    } else {
                        echo '<script>alert("Error preparing insert terjual statement: ' . $conn->error . '"); window.location.href="barang.php";</script>';
                        exit();
                    }
                } else {
                    echo '<script>alert("Error update stok produk jadi: ' . $stmt_update_produk_jadi->error . '"); window.location.href="barang.php";</script>';
                    exit();
                }
            } else {
                echo '<script>alert("Error preparing update produk jadi statement: ' . $conn->error . '"); window.location.href="barang.php";</script>';
                exit();
            }
        } else {
            echo '<script>alert("Error preparing get stok statement: ' . $conn->error . '"); window.location.href="barang.php";</script>';
            exit();
        }
    }


    // 4. Mengelola data BARANG KELUAR (FORM DARI MODAL 'barangKeluarModal')
    if (isset($_POST['add_barang_keluar'])) {
        $barang_id_keluar = intval($_POST['barang_id']);
        $jumlah_keluar = intval($_POST['jumlah']);
        $tanggal_keluar = $_POST['tanggal_keluar'];
        $keterangan_input = $_POST['keterangan']; // **PERBAIKAN DI SINI**

        // Jika Anda ingin 'Keterangan' bersifat opsional, ubah kondisi ini:
        // if ($barang_id_keluar <= 0 || $jumlah_keluar <= 0 || empty($tanggal_keluar)) {
        // Atau biarkan seperti sekarang jika harus diisi
        if ($barang_id_keluar <= 0 || $jumlah_keluar <= 0 || empty($tanggal_keluar) || empty($keterangan_input)) {
            echo "<script>alert('ID Barang, Jumlah, Tanggal Keluar, atau Keterangan tidak valid untuk Barang Keluar.'); window.location.href='barang.php';</script>";
            exit();
        }

        $stmt_get_stok_barang = $conn->prepare("SELECT stok FROM barang WHERE barang_id = ?");
        if ($stmt_get_stok_barang) {
            $stmt_get_stok_barang->bind_param("i", $barang_id_keluar);
            $stmt_get_stok_barang->execute();
            $result_stok_barang = $stmt_get_stok_barang->get_result();
            $data_stok_barang = $result_stok_barang->fetch_assoc();
            $stmt_get_stok_barang->close();

            if (!$data_stok_barang) {
                echo "<script>alert('Barang tidak ditemukan!'); window.location.href='barang.php';</script>";
                exit();
            }

            $stok_saat_ini_barang = $data_stok_barang['stok'];
            $stok_baru_barang = $stok_saat_ini_barang - $jumlah_keluar;

            if ($stok_baru_barang < 0) {
                echo "<script>alert('Stok barang tidak mencukupi!'); window.location.href='barang.php';</script>";
                exit();
            }

            $stmt_update_stok_barang = $conn->prepare("UPDATE barang SET stok = ? WHERE barang_id = ?");
            if ($stmt_update_stok_barang) {
                $stmt_update_stok_barang->bind_param("ii", $stok_baru_barang, $barang_id_keluar);
                if ($stmt_update_stok_barang->execute()) {
                    $stmt_update_stok_barang->close();

                    $stmt_insert_keluar = $conn->prepare("INSERT INTO barang_keluar (barang_id, jumlah, tanggal_keluar, keterangan) VALUES (?, ?, ?, ?)");
                    if ($stmt_insert_keluar) {
                        $stmt_insert_keluar->bind_param("iiss", $barang_id_keluar, $jumlah_keluar, $tanggal_keluar, $keterangan_input);
                        if ($stmt_insert_keluar->execute()) {
                            echo '<script>alert("Barang keluar berhasil dicatat."); window.location.href="barang.php";</script>';
                            exit();
                        } else {
                            echo '<script>alert("Error insert barang keluar: ' . $stmt_insert_keluar->error . '"); window.location.href="barang.php";</script>';
                            exit();
                        }
                        $stmt_insert_keluar->close();
                    } else {
                        echo '<script>alert("Error preparing insert barang keluar statement: ' . $conn->error . '"); window.location.href="barang.php";</script>';
                        exit();
                    }
                } else {
                    echo '<script>alert("Error update stok barang: ' . $stmt_update_stok_barang->error . '"); window.location.href="barang.php";</script>';
                    exit();
                }
            } else {
                echo '<script>alert("Error preparing update stok barang statement: ' . $conn->error . '"); window.location.href="barang.php";</script>';
                exit();
            }
        } else {
            echo '<script>alert("Error preparing get stok barang statement: ' . $conn->error . '"); window.location.href="barang.php";</script>';
            exit();
        }
    }

    // 5. Penanganan UPDATE barang (FORM DARI MODAL EDIT BARANG)
    if (isset($_POST['edit_barang'])) {
        $barang_id = intval($_POST['barang_id_edit']);
        $nama_barang = $_POST['nama_barang_edit'];
        $stok = intval($_POST['stok_edit_barang']);
        $supplier_id = intval($_POST['supplier_id_edit'] ?? 0);

        if ($barang_id <= 0 || empty($nama_barang) || $stok < 0 || $supplier_id <= 0) {
            echo '<script>alert("Data barang tidak valid untuk diperbarui."); window.location.href="barang.php";</script>';
            exit();
        }

        $sql_update = "UPDATE barang SET nama_barang = ?, stok = ?, supplier_id = ? WHERE barang_id = ?";
        $stmt_update = $conn->prepare($sql_update);

        if ($stmt_update) {
            $stmt_update->bind_param("siii", $nama_barang, $stok, $supplier_id, $barang_id);
            if ($stmt_update->execute()) {
                echo '<script>alert("Barang berhasil diupdate."); window.location.href="barang.php";</script>';
                exit();
            } else {
                echo '<script>alert("Gagal mengupdate barang: ' . $stmt_update->error . '"); window.location.href="barang.php";</script>';
                exit();
            }
            $stmt_update->close();
        } else {
            echo '<script>alert("Gagal menyiapkan statement update barang: ' . $conn->error . '"); window.location.href="barang.php";</script>';
            exit();
        }
    }

    // 6. Penanganan DELETE barang
    if (isset($_POST['delete_barang_id'])) {
        $barang_id = intval($_POST['delete_barang_id']);

        if ($barang_id <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'ID Barang tidak valid.']);
            exit();
        }

        $sql_delete = "DELETE FROM barang WHERE barang_id = ?";
        $stmt_delete = $conn->prepare($sql_delete);

        if ($stmt_delete) {
            $stmt_delete->bind_param("i", $barang_id);
            if ($stmt_delete->execute()) {
                echo json_encode(['status' => 'success', 'message' => 'Barang berhasil dihapus.']);
                exit();
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus barang: ' . $stmt_delete->error]);
                exit();
            }
            $stmt_delete->close();
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal menyiapkan statement hapus barang: ' . $conn->error]);
            exit();
        }
    }

    // 7. Penanganan UPDATE Produk Jadi (Hanya Stok)
    if (isset($_POST['update_produk_jadi'])) {
        $id_produk_jadi = intval($_POST['id_produk_jadi']);
        $stok_produk_jadi = intval($_POST['stok']);

        if ($id_produk_jadi <= 0 || $stok_produk_jadi < 0) {
            echo '<script>alert("ID Produk atau Stok tidak valid."); window.location.href="barang.php";</script>';
            exit();
        }

        $sql_update_produk = "UPDATE produk_jadi SET stok = ? WHERE id_produk_jadi = ?";
        $stmt_update_produk = $conn->prepare($sql_update_produk);

        if ($stmt_update_produk) {
            $stmt_update_produk->bind_param("ii", $stok_produk_jadi, $id_produk_jadi);
            if ($stmt_update_produk->execute()) {
                echo '<script>alert("Stok produk jadi berhasil diupdate."); window.location.href="barang.php";</script>';
                exit();
            } else {
                echo '<script>alert("Gagal mengupdate stok produk jadi: ' . $stmt_update_produk->error . '"); window.location.href="barang.php";</script>';
                exit();
            }
            $stmt_update_produk->close();
        } else {
            echo '<script>alert("Gagal menyiapkan statement update produk jadi: ' . $conn->error . '"); window.location.href="barang.php";</script>';
            exit();
        }
    }
}

// --- Query untuk menampilkan data BARANG MENTAH (dengan JOIN ke supplier) ---
$query_barang_tampil = "
    SELECT
        b.barang_id,
        b.nama_barang,
        b.kategori,
        b.harga,
        b.stok,
        b.supplier_id,
        s.nama_supplier
    FROM
        barang b
    LEFT JOIN
        supplier s ON b.supplier_id = s.supplier_id
    ORDER BY
        b.nama_barang ASC
";
// Mengembalikan nama variabel menjadi $query agar sesuai dengan penggunaan di bagian HTML/tampilan Anda.
$query = mysqli_query($conn, $query_barang_tampil);
if (!$query) {
    die("Query menampilkan barang gagal: " . mysqli_error($conn));
}

// Query untuk data barang_keluar
$query_keluar_tampil = "
    SELECT
        bk.id_keluar,
        bk.barang_id,
        b.nama_barang AS nama_barang_keluar,
        bk.jumlah,
        bk.tanggal_keluar,
        bk.keterangan
    FROM
        barang_keluar bk
    JOIN
        barang b ON bk.barang_id = b.barang_id
    ORDER BY
        bk.tanggal_keluar DESC
";
$query_keluar = mysqli_query($conn, $query_keluar_tampil);
if (!$query_keluar) {
    die("Query barang keluar gagal: " . mysqli_error($conn));
}


// Query untuk produk_jadi
$query_produk_jadi_tampil = mysqli_query($conn, "SELECT * FROM produk_jadi ORDER BY nama_produk ASC");
if (!$query_produk_jadi_tampil) {
    die("Query produk jadi gagal: " . mysqli_error($conn));
}

// Query untuk produk_terjual
$query_produk_terjual_tampil = "
    SELECT
        pt.id_terjual,
        pt.id_produk_jadi,
        pj.nama_produk,
        pt.jumlah_terjual,
        pt.tanggal_terjual,
        pt.alamat,
        pt.pelanggan_id,
        pt.harga,
        pt.status,
        pt.status_pembayaran,
        pt.pesanan_id
    FROM
        produk_terjual pt
    JOIN
        produk_jadi pj ON pt.id_produk_jadi = pj.id_produk_jadi
    ORDER BY
        pt.tanggal_terjual DESC, pt.id_terjual DESC
";
$query_produk_terjual = mysqli_query($conn, $query_produk_terjual_tampil);
if (!$query_produk_terjual) {
    die("Query produk terjual gagal: " . mysqli_error($conn));
}

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
                    <li><a href="../main/index.php"><i class='bx bx-home-alt-2'></i><span>Dashboard</span></a></li>
                    <li><a href="barang.php" class="active"><i class='bx bx-package'></i><span>Data Barang</span></a></li>
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
            <h2>Data Barang</h2>
                <button id="openModalBtn" class="btn-simpan">Tambah Barang</button>
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
                                <th>Aksi</th>
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
                                        <td>
                                            <button class="btn-edit edit-barang-btn" data-id="<?php echo htmlspecialchars($row['barang_id']); ?>" data-stok="<?php echo htmlspecialchars($row['stok']); ?>">Edit</button>
                                            <button class="btn-hapus delete-barang-btn"
                                                data-delete-item="<?php echo htmlspecialchars($row['barang_id']); ?>"
                                                data-item-name="<?php echo htmlspecialchars($row['nama_barang']); ?>">Hapus</button>
                                        </td>
                                    </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div><br><br><br>

            <h2>Data Produk</h2>
            <button id="openModalProdukBtn" class="btn-simpan">Input Produk Jadi</button>
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
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Variabel yang dikoreksi dari $query_produk_jadi menjadi $query_produk_jadi_tampil
                        while ($row = mysqli_fetch_assoc($query_produk_jadi_tampil)) {
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
                                <td>
                                    <button class="btn-edit edit-produk-btn" data-id="<?php echo htmlspecialchars($row['id_produk_jadi']); ?>" data-stok="<?php echo htmlspecialchars($row['stok']); ?>">Edit</button>
                                    <button class="btn-hapus delete-produk-btn" data-id="<?php echo htmlspecialchars($row['id_produk_jadi']); ?>">Hapus</button>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div><br><br><br>
            <h2> Barang Keluar</h2>
            <button id="openModalKeluarBtn" class="btn-simpan">Input Barang Keluar</button>
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
    <div id="addItemModal" class="modal">
    <div class="modal-content">
        <span class="close" id="closeModalBtn">&times;</span>
        <h3>Tambah Data Barang</h3>
        <form class="form-tambah-barang" method="POST">
            <div class="form-group">
                <label for="barang_id">ID Barang:</label>
                <input type="text" id="barang_id" name="barang_id" class="form-control" readonly>
            </div>

            <div class="form-group">
                <label for="nama_barang">Nama Barang:</label>
                <input type="text" id="nama_barang" name="nama_barang" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="kategori">Kategori:</label>
                <input type="text" id="kategori" name="kategori" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="harga">Harga /pack:</label>
                <input type="number" id="harga" name="harga" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="stok">Stok (pack):</label>
                <input type="number" id="stok" name="stok" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="supplier_id">Supplier:</label>
                <select id="supplier_id" name="supplier_id" class="form-control" required>
                    <option value="">Pilih Supplier</option>
                    <?php
                    if ($supplierResult_barang) { // Memastikan query berhasil
                        // Mengatur ulang pointer hasil query ke awal jika $supplierResult_barang mungkin telah digunakan sebelumnya
                        mysqli_data_seek($supplierResult_barang, 0); //

                        while ($row_supplier = mysqli_fetch_assoc($supplierResult_barang)) { //
                            echo '<option value="' . htmlspecialchars($row_supplier['supplier_id']) . '">' . htmlspecialchars($row_supplier['nama_supplier']) . '</option>'; //
                        }
                    } else {
                        echo "<option value=''>Tidak ada supplier tersedia atau error query</option>"; //
                    }
                    ?>
                </select>
            </div>
            <div class="modal-buttons">
                <button type="submit" name="add_barang" class="btn-simpan">Simpan</button>
                <button type="button" class="btn-batal" onclick="document.getElementById('addItemModal').style.display='none'">Kembali</button>
            </div>
        </form>
    </div>
</div>
    <div id="confirmModal" class="modal fade">
        <div class="modal-content slide">
            <span class="close">&times;</span>
            <div class="modal-header">
                <h3>Konfirmasi Penghapusan</h3>
            </div>
            <div class="modal-body">
                <p>Apakah Anda yakin ingin menghapus barang ini?</p>
                <p class="item-details" id="itemDetails"></p>
            </div>
            <div class="modal-footer modal-buttons">
                <button type="button" class="btn-batal" id="cancelBtn">Batal</button>
                <button type="button" class="btn-hapus" id="confirmDeleteBtn">Hapus</button>
            </div>
        </div>
    </div>
    <div id="produkModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3>Input Produk Jadi</h3>
            <form class="form-tambah-produk-jadi" method="POST">
                <div class="form-group">
                    <label for="nama_produk">Nama Produk:</label>
                    <input type="text" id="nama_produk" name="nama_produk" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="stok_produk_jadi">Stok:</label>
                    <input type="number" id="stok_produk_jadi" name="stok" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="tanggal_produksi">Tanggal Produksi:</label>
                    <input type="date" id="tanggal_produksi" name="tanggal_produksi" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="harga_jual">Harga Jual:</label>
                    <input type="number" id="harga_jual" name="harga_jual" step="0.01" class="form-control" required>
                </div>

                <div class="modal-buttons">
                    <button type="submit" name="add_produk_jadi" class="btn-simpan">Simpan Produk Jadi</button>
                    <button type="button" class="btn-batal" onclick="document.getElementById('produkModal').style.display='none'">Kembali</button>
                </div>
            </form>
        </div>
    </div>
    <div id="produkTerjualModal" class="modal">
        <div class="modal-content">
            <span class="close" id="closeModalTerjualBtn">&times;</span>
            <h3>Input Produk Terjual</h3>
            <form method="POST">
                <div class="form-group">
                    <label for="id_produk_jadi_terjual">ID Produk Jadi:</label>
                    <input type="text" id="id_produk_jadi_terjual" name="id_produk_jadi" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="jumlah_terjual">Jumlah Terjual:</label>
                    <input type="number" id="jumlah_terjual" name="jumlah_terjual" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="tanggal_terjual">Tanggal Penjualan:</label>
                    <input type="date" id="tanggal_terjual" name="tanggal_terjual" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="catatan">Catatan:</label>
                    <textarea id="catatan" name="catatan" class="form-control"></textarea>
                </div>

                <div class="modal-buttons">
                    <button type="submit" name="add_produk_terjual" class="btn-simpan">Simpan</button>
                    <button type="button" class="btn-batal" onclick="document.getElementById('produkTerjualModal').style.display='none'">Kembali</button>
                </div>
            </form>
        </div>
    </div>
</div>
<div id="barangKeluarModal" class="modal">
        <div class="modal-content">
            <span class="close" id="closeModalKeluarBtn">&times;</span>
            <h3>Input Barang Keluar</h3>
            <form class="form-tambah-barang" method="POST">
                <div class="form-group">
                    <label for="barang_id_keluar">ID Barang:</label>
                    <select id="barang_id_keluar" name="barang_id" class="form-control" required>
                        <option value="">Pilih Barang</option>
                        <?php
                        // Pastikan query_barang dijalankan ulang jika ada update data
                        $query_barang = mysqli_query($conn, "SELECT barang_id, nama_barang FROM barang");
                        while ($row_barang = mysqli_fetch_assoc($query_barang)) {
                            echo "<option value='" . htmlspecialchars($row_barang['barang_id']) . "'>" . htmlspecialchars($row_barang['nama_barang']) . " (" . htmlspecialchars($row_barang['barang_id']) . ")</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="jumlah_keluar">Jumlah:</label>
                    <input type="number" id="jumlah_keluar" name="jumlah" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="tanggal_keluar_form">Tanggal Keluar:</label>
                    <input type="date" id="tanggal_keluar_form" name="tanggal_keluar" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="keterangan_keluar">Keterangan:</label>
                    <textarea id="keterangan_keluar" name="keterangan" class="form-control"></textarea>
                </div>

                <div class="modal-buttons">
                    <button type="submit" name="add_barang_keluar" class="btn-simpan">Simpan Barang Keluar</button>
                    <button type="button" class="btn-batal" onclick="document.getElementById('barangKeluarModal').style.display='none'">Kembali</button>
                </div>
            </form>
        </div>
    </div>
    <div id="editModalBarang" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeEditModalBarang()">&times;</span>
            <h3>Edit Stok Barang</h3>
            <form method="POST" id="formEditBarang" action="../main/update_stok_barang.php">
                <input type="hidden" name="barang_id" id="editBarangId">
                <div class="form-group">
                    <label for="stok_edit_barang">Stok (pack):</label>
                    <input type="number" name="stok" id="stok_edit_barang" class="form-control" required>
                </div>
                <div class="modal-buttons">
                    <button type="submit" class="btn-simpan">Simpan</button>
                </div>
            </form>
        </div>
    </div>
    <div id="editModalProduk" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeEditModalProduk()">&times;</span>
            <h3>Edit Stok Produk</h3>
            <form method="POST" id="formEditProduk" action="../main/update_stok_produk.php">
                <input type="hidden" name="id_produk_jadi" id="editProdukId">
                <div class="form-group">
                    <label for="stok_edit_produk">Stok (pack):</label>
                    <input type="number" name="stok" id="stok_edit_produk" class="form-control" required>
                </div>
                <div class="modal-buttons">
                    <button type="submit" class="btn-simpan">Simpan</button>
                </div>
            </form>
        </div>
    </div>

<script src="../javascript/hapus-produk.js"></script>
<script src="../javascript/barang-keluar.js"></script>
<script src="../javascript/hapus-barang.js"></script>
<script src="../javascript/tambah-barang.js"></script>
<script src="../javascript/produk.js"></script>
<script src="../javascript/edit-barang.js"></script>
<script src="../javascript/edit-produk.js"></script>
</body>
</html>