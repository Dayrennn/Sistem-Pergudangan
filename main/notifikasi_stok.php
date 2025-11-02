<?php
include 'koneksi.php'; // sesuaikan path jika folder notifikasi di luar root

$notif_barang = "";
$notif_produk = "";

// Barang
$q1 = mysqli_query($conn, "SELECT nama_barang, stok FROM barang");
while ($row = mysqli_fetch_assoc($q1)) {
    if ($row['stok'] < 100) {
        $notif_barang .= "<li>{$row['nama_barang']} (Stok: {$row['stok']})</li>";
    }
}

// Produk Jadi
$q2 = mysqli_query($conn, "SELECT nama_produk, stok FROM produk_jadi");
while ($row = mysqli_fetch_assoc($q2)) {
    if ($row['stok'] < 100) {
        $notif_produk .= "<li>{$row['nama_produk']} (Stok: {$row['stok']})</li>";
    }
}

$ada_notif = !empty($notif_barang) || !empty($notif_produk);

if ($ada_notif): ?>
    <link rel="stylesheet" href="../css/notifikasi_stok.css">
    <div id="stokModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                ⚠️ Notifikasi Stok Menipis / Habis
                <span class="modal-close">&times;</span>
            </div>
            <div class="modal-body">
                <?php if (!empty($notif_barang)) : ?>
                    <strong>Barang:</strong>
                    <ul><?= $notif_barang ?></ul>
                <?php endif; ?>

                <?php if (!empty($notif_produk)) : ?>
                    <strong>Produk Jadi:</strong>
                    <ul><?= $notif_produk ?></ul>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button>Tutup</button>
            </div>
        </div>
    </div>
    <script>
        const adaNotifDariPHP = <?php echo json_encode($ada_notif); ?>;
    </script>
    <script src="../javascript/notifikasi_stok.js"></script>
<?php endif; ?>