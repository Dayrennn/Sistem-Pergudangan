document.addEventListener("DOMContentLoaded", function () {
  // ---- Edit Barang ----
  const editBarangButtons = document.querySelectorAll(".edit-barang-btn");
  const editModalBarang = document.getElementById("editModalBarang");
  const closeEditBarangBtn = editModalBarang
    ? editModalBarang.querySelector(".close")
    : null;
  const editBarangIdInput = document.getElementById("editBarangId");
  const stokEditBarangInput = document.getElementById("stok_edit_barang");

  editBarangButtons.forEach((button) => {
    button.addEventListener("click", function () {
      const barangId = this.getAttribute("data-id");
      const stok = this.getAttribute("data-stok");

      console.log("Mengedit Barang ID:", barangId, "Stok:", stok); // Debugging: Pastikan ini muncul saat klik Edit

      // Cek apakah elemen-elemen ditemukan sebelum diakses
      if (editBarangIdInput && stokEditBarangInput && editModalBarang) {
        editBarangIdInput.value = barangId;
        stokEditBarangInput.value = stok;
        editModalBarang.style.display = "block"; // Tampilkan modal
      } else {
        console.error(
          "ERROR: Elemen modal edit barang (editModalBarang, editBarangId, atau stok_edit_barang) tidak ditemukan di DOM!"
        );
      }
    });
  });

  if (closeEditBarangBtn) {
    closeEditBarangBtn.addEventListener("click", function () {
      if (editModalBarang) {
        editModalBarang.style.display = "none";
      }
    });
  }

  // Menutup modal jika klik di luar area modal
  window.addEventListener("click", function (event) {
    if (event.target === editModalBarang) {
      editModalBarang.style.display = "none";
    }
  });

  // PENTING: Tidak ada event listener untuk form submit di sini.
  // Karena form akan disubmit langsung ke update_stok_barang.php oleh browser.
});
