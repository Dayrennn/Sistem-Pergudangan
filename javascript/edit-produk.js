document.addEventListener("DOMContentLoaded", function () {
  // ---- Edit Produk ----
  const editProdukButtons = document.querySelectorAll(".edit-produk-btn");
  const editModalProduk = document.getElementById("editModalProduk");
  const closeEditProdukBtn = editModalProduk
    ? editModalProduk.querySelector(".close")
    : null;
  const editProdukIdInput = document.getElementById("editProdukId");
  const stokEditProdukInput = document.getElementById("stok_edit_produk");

  editProdukButtons.forEach((button) => {
    button.addEventListener("click", function () {
      const produkId = this.getAttribute("data-id");
      const stok = this.getAttribute("data-stok");

      console.log("Mengedit Produk ID:", produkId, "Stok:", stok); // Debugging: Pastikan ini muncul saat klik Edit Produk

      // Cek apakah elemen-elemen ditemukan sebelum diakses
      if (editProdukIdInput && stokEditProdukInput && editModalProduk) {
        editProdukIdInput.value = produkId;
        stokEditProdukInput.value = stok;
        editModalProduk.style.display = "block"; // Tampilkan modal
      } else {
        console.error(
          "ERROR: Elemen modal edit produk (editModalProduk, editProdukId, atau stok_edit_produk) tidak ditemukan di DOM!"
        );
      }
    });
  });

  if (closeEditProdukBtn) {
    closeEditProdukBtn.addEventListener("click", function () {
      if (editModalProduk) {
        editModalProduk.style.display = "none";
      }
    });
  }

  // Menutup modal jika klik di luar area modal
  window.addEventListener("click", function (event) {
    if (event.target === editModalProduk) {
      editModalProduk.style.display = "none";
    }
  });

  // PENTING: Tidak ada event listener untuk form submit di sini.
  // Karena form akan disubmit langsung ke update_stok_produk.php oleh browser.
});
