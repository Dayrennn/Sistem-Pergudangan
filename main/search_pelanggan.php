<?php
include 'koneksi.php';

$search = isset($_GET['search']) ? $_GET['search'] : '';

$query_pelanggan = mysqli_query($conn, "SELECT * FROM pelanggan WHERE nama_pelanggan LIKE '%$search%'");

$no = 1;
while ($row = mysqli_fetch_assoc($query_pelanggan)) {
    echo "<tr>
            <td>{$no}</td>
            <td>{$row['nama_pelanggan']}</td>
            <td>{$row['kontak']}</td>
            <td>{$row['email']}</td>
            <td>{$row['alamat']}</td>
            <td>
                <button class='editPelangganBtn' 
                data-id='{$row['pelanggan_id']}'
                data-nama='{$row['nama_pelanggan']}'
                data-kontak='{$row['kontak']}'
                data-email='{$row['email']}'
                data-alamat='{$row['alamat']}'>Edit</button>
                
                <button class='hapusPelangganBtn' data-id='{$row['pelanggan_id']}'>Hapus</button>
            </td>
        </tr>";
    $no++;
}
?>
