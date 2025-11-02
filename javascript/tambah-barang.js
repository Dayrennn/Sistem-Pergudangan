document.addEventListener("DOMContentLoaded", function () {
  // Ambil elemen modal dan tombol
  const modal = document.getElementById("addItemModal");
  const openModalBtn = document.getElementById("openModalBtn");
  const closeModalBtn = document.getElementById("closeModalBtn");
  const closeIcon = modal ? modal.querySelector(".close") : null; // Ambil elemen close (X)

  // Fungsi untuk membuka modal dengan animasi
  const openAddItemModal = () => {
    modal.style.display = "block";
    modal.classList.remove("fade-out");
    modal.querySelector(".modal-content").classList.remove("slide-out");
  };

  // Fungsi untuk menutup modal dengan animasi
  const closeAddItemModal = () => {
    modal.classList.add("fade-out");
    const modalContent = modal.querySelector(".modal-content");
    if (modalContent) {
      modalContent.classList.add("slide-out");
    }
    setTimeout(function () {
      modal.style.display = "none";
    }, 300); // Tunggu durasi animasi fade-out
  };

  // Ketika tombol 'Tambah Barang' diklik, tampilkan modal
  if (openModalBtn) {
    openModalBtn.addEventListener("click", openAddItemModal);
  }

  // Ketika tombol close (x) diklik, sembunyikan modal
  if (closeModalBtn) {
    closeModalBtn.addEventListener("click", closeAddItemModal);
  }

  // Ketika ikon close (X) diklik, sembunyikan modal
  if (closeIcon) {
    closeIcon.addEventListener("click", closeAddItemModal);
  }

  // Ketika pengguna mengklik di luar modal, sembunyikan modal
  window.addEventListener("click", function (event) {
    if (event.target == modal) {
      closeAddItemModal();
    }
  });
  document.addEventListener("DOMContentLoaded", function () {
    // Fungsi generate ID barang
    function generateBarangId() {
      const prefix = "BRG"; // Prefix untuk ID barang
      const randomNum = Math.floor(1000 + Math.random() * 9000); // 4 digit random
      return prefix + randomNum;
    }

    // Set ID otomatis saat modal dibuka
    document
      .getElementById("openModalBtn")
      .addEventListener("click", function () {
        document.getElementById("barang_id").value = generateBarangId();
      });
  });
});
