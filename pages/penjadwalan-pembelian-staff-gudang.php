<?php
// Pastikan tidak ada spasi atau karakter lain sebelum tag pembuka <?php

// Pastikan file koneksi.php berada di direktori yang benar relatif terhadap file ini.
// Jika penjadwalan-pembelian-staff-gudang.php ada di 'pages/', dan koneksi.php ada di root,
// maka 'include '../koneksi.php';' sudah benar.
include '../main/koneksi.php';
session_start();

$username = isset($_SESSION['username']) ? $_SESSION['username'] : 'Guest';
$role = isset($_SESSION['role']) ? $_SESSION['role'] : 'Unknown';   

// --- PENTING: Header Content-Type untuk AJAX Responses ---
// Set header JSON hanya jika ini adalah AJAX request (POST atau GET untuk barang)
if ($_SERVER['REQUEST_METHOD'] === 'POST' || (isset($_GET['ajax']) && $_GET['ajax'] == 'get_barang')) {
    header('Content-Type: application/json');
}

// --- Bagian Penanganan POST Request (Untuk Menambah Jadwal) ---
// Ini akan dipicu ketika form tambah jadwal disubmit via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $supplier_id = intval($_POST['supplier_id'] ?? 0);
    $barang_supplier = $_POST['barang_id'] ?? ''; // Ini adalah string nama barang
    $jumlah = intval($_POST['jumlah'] ?? 0);
    $tanggal = $_POST['tanggal'] ?? '';
    $status = $_POST['status'] ?? '';

    // Validasi input dasar
    if ($supplier_id <= 0 || empty($barang_supplier) || $jumlah <= 0 || empty($tanggal) || empty($status)) {
        echo json_encode(['success' => false, 'message' => 'Semua field harus diisi.']);
        exit; // Hentikan eksekusi skrip dan kirim respons JSON
    }

    // Validasi: Cek apakah barang_supplier sesuai dengan supplier_id yang dipilih
    // Asumsi: kolom `barang_supplier` di tabel `supplier` menyimpan nama barang yang dipisahkan koma.
    $check_barang_stmt = $conn->prepare("SELECT COUNT(*) FROM supplier WHERE supplier_id = ? AND FIND_IN_SET(?, barang_supplier) > 0");
    if ($check_barang_stmt) {
        $check_barang_stmt->bind_param("is", $supplier_id, $barang_supplier);
        $check_barang_stmt->execute();
        $check_barang_stmt->bind_result($count);
        $check_barang_stmt->fetch();
        $check_barang_stmt->close();

        if ($count === 0) {
            echo json_encode(['success' => false, 'message' => 'Barang "' . htmlspecialchars($barang_supplier) . '" tidak tersedia untuk supplier ini.']);
            exit;
        }
    } else {
        // Handle error jika prepared statement gagal
        echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan internal saat memvalidasi barang.']);
        exit;
    }

    // Masukkan data penjadwalan ke database
    $insert_stmt = $conn->prepare("INSERT INTO penjadwalan_pembelian (supplier_id, barang_supplier, jumlah, tanggal, status) VALUES (?, ?, ?, ?, ?)");
    if ($insert_stmt) {
        $insert_stmt->bind_param("isiss", $supplier_id, $barang_supplier, $jumlah, $tanggal, $status);

        if ($insert_stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Data jadwal pembelian berhasil disimpan.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal menyimpan data: ' . $insert_stmt->error]);
        }
        $insert_stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan internal saat menyiapkan penyimpanan data.']);
    }

    $conn->close(); // Tutup koneksi database setelah selesai memproses request AJAX
    exit; // Sangat penting: Hentikan eksekusi skrip setelah mengirim respons JSON
}

// --- Bagian Penanganan GET Request untuk mengambil daftar barang berdasarkan supplier ---
// Ini akan dipicu oleh Fetch API dari JavaScript ketika supplier_id berubah
if (isset($_GET['ajax']) && $_GET['ajax'] == 'get_barang') {
    $supplier_id = intval($_GET['supplier_id'] ?? 0);

    if ($supplier_id <= 0) {
        echo json_encode([]); // Kirim array kosong jika supplier_id tidak valid
        exit;
    }

    $query = mysqli_query($conn, "SELECT barang_supplier FROM supplier WHERE supplier_id = $supplier_id");

    if ($query && mysqli_num_rows($query) > 0) {
        $row = mysqli_fetch_assoc($query);
        $barangListString = $row['barang_supplier']; // Ambil string barang_supplier
        $barangArray = explode(',', $barangListString); // Pecah menjadi array
        $cleanedBarangArray = array_values(array_filter(array_map('trim', $barangArray))); // Bersihkan dan hapus spasi/kosong

        echo json_encode($cleanedBarangArray); // Kirim array nama barang
    } else {
        echo json_encode([]); // Kirim array kosong jika tidak ditemukan barang
    }

    $conn->close(); // Tutup koneksi database setelah selesai memproses request AJAX
    exit; // Sangat penting: Hentikan eksekusi skrip setelah mengirim respons JSON
}


// --- Bagian untuk Pemuatan Halaman Normal (GET Request Default) ---
// Kode di bawah ini hanya akan dieksekusi jika request BUKAN POST dan BUKAN AJAX GET
// Ini adalah kode yang membangun halaman HTML Anda.

// Query untuk menampilkan data penjadwalan pada tabel
$jadwalQuery = "
    SELECT
        p.id_pembelian,
        p.supplier_id,
        s.nama_supplier,
        p.barang_supplier,
        p.jumlah,
        p.tanggal,
        p.status
    FROM penjadwalan_pembelian p
    LEFT JOIN supplier s ON p.supplier_id = s.supplier_id
    ORDER BY p.tanggal DESC
";

$jadwal = mysqli_query($conn, $jadwalQuery);

if (!$jadwal) {
    die("Query penjadwalan gagal: " . mysqli_error($conn));
}

// Ambil data supplier untuk dropdown di modal tambah
$supplierResult = mysqli_query($conn, "SELECT supplier_id, nama_supplier FROM supplier ORDER BY nama_supplier ASC");
// Periksa apakah query berhasil dan ada data
if (!$supplierResult) {
    die("Query supplier gagal: " . mysqli_error($conn));
}


// Hitung jumlah status untuk kartu ringkasan
$statusCountQuery = "
    SELECT status, COUNT(*) as total
    FROM penjadwalan_pembelian
    GROUP BY status
";

$statusResult = mysqli_query($conn, $statusCountQuery);

$statusCounts = [
    'Terjadwal' => 0,
    'Dalam Proses' => 0,
    'Selesai' => 0,
    'Dibatalkan' => 0
];

if ($statusResult) {
    while ($row = mysqli_fetch_assoc($statusResult)) {
        $statusCounts[$row['status']] = $row['total'];
    }
} else {
    // Handle error jika query status gagal
    error_log("Query status count gagal: " . mysqli_error($conn));
}

// Tidak menutup koneksi di sini karena akan digunakan di HTML (misal untuk loop supplier)
// Koneksi akan ditutup secara otomatis di akhir skrip atau saat AJAX request selesai
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WEB PENYIMPANAN BARANG</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter&family=Open+Sans&family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/penjadwalan-pembelian.css">
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
            <li><a href="../main/index-staff-gudang.php"><i class='bx bx-home-alt-2'></i><span>Dashboard</span></a></li>
            <li><a href="barang-staff-gudang.php"><i class='bx bx-package'></i><span>Data Barang</span></a></li>
            <li><a href="penjualan-staff-gudang.php"><i class='bx bx-cart'></i><span>Data Penjualan</span></a></li>
            <li><a href="supplier-staff-gudang.php"><i class='bx bx-store-alt'></i><span>Data Supplier</span></a></li>
            <li><a href="penjadwalan-pembelian-staff-gudang.php"><i class='bx bx-user' ></i></i><span>Penjadwalan Pembelian</span></a></li>
            <li><a href="data-pembelian-staff-gudang.php"><i class='bx bx-dollar'></i><span>Data Pembelian</span></a></li>
            <li>
                <form action="../main/logout.php" method="POST">
                    <button type="submit" class="btn-logout-sidebar"><span>Logout</span></button>
                </form>
            </li>
        </ul>
    </aside>

    <div class="beranda">
        <br>
        <h2>Penjadwalan Pembelian</h2>

        <div class="container-card">
            <div class="card" style="background-color: #ebedff;">
                <h3><?= $statusCounts['Terjadwal'] ?></h3>
                <p>Pembelian Terjadwal</p>
                <i class='bx bx-calendar fa-2xl'></i>
            </div>
            <div class="card" style="background-color: #ebedff;">
                <h3><?= $statusCounts['Dalam Proses'] ?></h3>
                <p>Dalam Proses</p>
                <i class='bx bx-loader-circle fa-2xl' ></i>
            </div>
            <div class="card" style="background-color: #ebedff;">
                <h3><?= $statusCounts['Selesai'] ?></h3>
                <p>Selesai</p>
                <i class='bx bx-check-circle fa-2xl'></i>
            </div>
            <div class="card" style="background-color: #ebedff;">
                <h3><?= $statusCounts['Dibatalkan'] ?></h3>
                <p>Dibatalkan</p>
                <i class='bx bx-x-circle fa-2xl'></i>
            </div>
        </div>


        <br><br>

        <div class="data-table-container">
            <div class="table-header">
                <h2>Daftar Penjadwalan Pembelian</h2>
                <div class="table-controls">
                    <button id="btnTambahJadwal" class="btn success">+ Jadwal Baru</button>

                </div>
            </div>
        <div class="data-table">
            <table>
                <thead>
                    <tr>
                        <th>ID Pembelian</th>
                        <th>Supplier</th>
                        <th>Produk</th>
                        <th>Jumlah</th>
                        <th>Tanggal</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody id="jadwalTableBody">
                    <?php while ($row = mysqli_fetch_assoc($jadwal)) { ?>
                        <tr>
                            <td><?= $row['id_pembelian'] ?></td>
                            <td><?= $row['nama_supplier'] ?></td>
                            <td><?= $row['barang_supplier'] ?></td>
                            <td><?= $row['jumlah'] ?></td>
                            <td><?= date('d-m-Y', strtotime($row['tanggal'])) ?></td>
                            <td><span data-status="<?= htmlspecialchars($row['status']) ?>"><?= $row['status'] ?></span></td>
                            <td>
                                <button class="btn danger" onclick="hapusJadwal(<?= $row['id_pembelian'] ?>)">Hapus</button> 
                                <button class="btn warning"
                                        data-id="<?= $row['id_pembelian'] ?>"
                                        data-supplier="<?= $row['supplier_id'] ?>"
                                        data-barang="<?= htmlspecialchars($row['barang_supplier']) ?>"
                                        data-jumlah="<?= $row['jumlah'] ?>"
                                        data-tanggal="<?= $row['tanggal'] ?>"
                                        data-status="<?= $row['status'] ?>"
                                        onclick="bukaModalEdit(this)">Edit</button>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
    </div>

    <!-- Modal Tambah Jadwal -->
    <div id="modalJadwal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal()">&times;</span>
            <h3>Tambah Jadwal Pembelian</h3>
            <form id="formTambahJadwal" >
                <label for="supplier_id">Supplier:</label>
                <select id="supplier_id" name="supplier_id" required>
                    <option value="">Pilih Supplier</option>
                    <?php
                    $supplierResult = mysqli_query($conn, "SELECT supplier_id, nama_supplier FROM supplier ORDER BY nama_supplier ASC");
                    while ($row = mysqli_fetch_assoc($supplierResult)) : ?>
                        <option value="<?= $row['supplier_id'] ?>"><?= htmlspecialchars($row['nama_supplier']) ?></option>
                    <?php endwhile; ?>
                </select>

                <label for="barang_id">Barang:</label>
                <select id="barang_id" name="barang_id" required disabled>
                    <option value="">Pilih Supplier terlebih dahulu</option>
                </select>

                <label for="jumlah">Jumlah:</label>
                <input type="number" id="jumlah" name="jumlah" min="1" required>

                <label for="tanggal">Tanggal:</label>
                <input type="date" id="tanggal" name="tanggal" required>

                <label for="status">Status:</label>
                <select id="status" name="status" required>
                    <option value="Terjadwal">Terjadwal</option>
                    <option value="Dalam Proses">Dalam Proses</option>
                    <option value="Selesai">Selesai</option>
                    <option value="Dibatalkan">Dibatalkan</option>
                </select>

                <div class="modal-buttons">
                    <button type="submit" class="btn success">
                        <span id="loadingIndicator" style="display:none;">
                            <i class="fas fa-spinner fa-spin"></i> Menyimpan...
                        </span>
                        <span id="submitText">Simpan</span>
                    </button>
                    <button type="button" class="btn danger" onclick="closeModal()">Batal</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Jadwal -->
<!-- Modal Edit Jadwal -->
<div id="modalEditJadwal" class="modal">
    <div class="modal-backdrop"></div>
    <div class="modal-content">
        <span class="close-modal" onclick="closeModal()">&times;</span>
        <h3>Edit Jadwal Pembelian</h3>
        <form id="formEditJadwal">
            <input type="hidden" id="edit_id" name="id_pembelian">
            
            <label for="edit_supplier_id">Supplier:</label>
            <select id="edit_supplier_id" name="supplier_id" required>
                <option value="">Pilih Supplier</option>
                <?php
                $supplierResult = mysqli_query($conn, "SELECT supplier_id, nama_supplier FROM supplier ORDER BY nama_supplier ASC");
                while ($row = mysqli_fetch_assoc($supplierResult)) : ?>
                    <option value="<?= $row['supplier_id'] ?>"><?= htmlspecialchars($row['nama_supplier']) ?></option>
                <?php endwhile; ?>
            </select>

            <label for="edit_barang_id">Barang:</label>
            <select id="edit_barang_id" name="barang_id" required>
                <option value="">Pilih Barang</option>
            </select>

            <label for="edit_jumlah">Jumlah:</label>
            <input type="number" id="edit_jumlah" name="jumlah" min="1" required>

            <label for="edit_tanggal">Tanggal:</label>
            <input type="date" id="edit_tanggal" name="tanggal" required>

            <label for="edit_status">Status:</label>
            <select id="edit_status" name="status" required>
                <option value="Terjadwal">Terjadwal</option>
                <option value="Dalam Proses">Dalam Proses</option>
                <option value="Selesai">Selesai</option>
                <option value="Dibatalkan">Dibatalkan</option>
            </select>

            <div class="modal-buttons">
                <button type="submit" class="btn success">Update</button>
                <button type="button" class="btn danger" onclick="closeModal()">Batal</button>
            </div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="../javascript/penjadwalan-pembelian.js"></script>
<script>
// Fungsi untuk mengambil barang berdasarkan supplier
function getBarangBySupplier(supplierId, targetSelect, selectedBarang = '') {
    if (!supplierId) {
        targetSelect.innerHTML = '<option value="">Pilih Supplier terlebih dahulu</option>';
        targetSelect.disabled = true;
        return;
    }

    fetch(`penjadwalan-pembelian-staff-gudang.php?ajax=get_barang&supplier_id=${supplierId}`)
        .then(response => response.json())
        .then(data => {
            let options = '<option value="">Pilih Barang</option>';
            data.forEach(barang => {
                const selected = barang === selectedBarang ? 'selected' : '';
                options += `<option value="${barang}" ${selected}>${barang}</option>`;
            });
            targetSelect.innerHTML = options;
            targetSelect.disabled = false;
        });
}

// Fungsi untuk hapus data
function hapusJadwal(id) {
    if (confirm('Apakah Anda yakin ingin menghapus jadwal ini?')) {
        fetch('hapus_penjadwalan.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `id_pembelian=${id}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                window.location.reload();
            } else {
                alert(data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Terjadi kesalahan saat menghapus data');
        });
    }
}

// Event listener untuk modal tambah
document.getElementById('supplier_id').addEventListener('change', function() {
    const barangSelect = document.getElementById('barang_id');
    getBarangBySupplier(this.value, barangSelect);
});

// Event listener untuk modal edit
document.getElementById('edit_supplier_id').addEventListener('change', function() {
    const barangSelect = document.getElementById('edit_barang_id');
    getBarangBySupplier(this.value, barangSelect);
});

// Fungsi buka modal edit
function bukaModalEdit(button) {
    const modal = document.getElementById('modalEditJadwal');
    const form = document.getElementById('formEditJadwal');
    
    form.elements['id_pembelian'].value = button.dataset.id;
    form.elements['supplier_id'].value = button.dataset.supplier;
    form.elements['jumlah'].value = button.dataset.jumlah;
    form.elements['tanggal'].value = button.dataset.tanggal;
    form.elements['status'].value = button.dataset.status;
    
    // Load barang berdasarkan supplier
    const barangSelect = document.getElementById('edit_barang_id');
    getBarangBySupplier(button.dataset.supplier, barangSelect, button.dataset.barang);
    
    modal.style.display = 'block';
}
</script>

</body>
</html>
