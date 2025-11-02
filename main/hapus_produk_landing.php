<?php
require_once 'koneksi.php';

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $query = mysqli_query($conn, "DELETE FROM landing_produk WHERE id = $id");

    if ($query) {
        header("Location: index-kepala-marketing.php?msg=deleted");
    } else {
        echo "Gagal menghapus data";
    }
}
?>
