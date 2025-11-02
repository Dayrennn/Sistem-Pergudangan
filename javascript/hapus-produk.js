document.addEventListener("DOMContentLoaded", function () {
  // Dapatkan semua tombol hapus produk
  const deleteProdukButtons = document.querySelectorAll(".delete-produk-btn");

  deleteProdukButtons.forEach((button) => {
    button.addEventListener("click", function () {
      const id = this.getAttribute("data-id"); // Ambil ID dari atribut data-id
      console.log("ID yang diterima di JS (menggunakan event listener):", id); // Untuk debugging

      if (confirm("Apakah Anda yakin ingin menghapus produk ini?")) {
        window.location.href = "hapus-produk.php?id=" + id;
      }
    });
  });
});
