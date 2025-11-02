document.addEventListener("DOMContentLoaded", function () {
  // Get the modals
  const modalSupplier = document.getElementById("modalSupplier"); // Cite: 1
  const modalHapus = document.getElementById("modalHapus"); // Cite: 1

  // Get the buttons that open the modals
  const openModalBtn = document.getElementById("openModalBtn"); // Cite: 1
  const editSupplierBtns = document.querySelectorAll(".editSupplierBtn"); // Cite: 1
  const hapusSupplierBtns = document.querySelectorAll(".hapusSupplierBtn"); // Cite: 1

  // Get the close buttons for the modals
  const closeSupplierModalBtn = modalSupplier.querySelector(".close-btn"); // Cite: 1
  const closeHapusModalBtn = modalHapus.querySelector(".close-btn-hapus"); // Cite: 1
  const batalHapusBtn = document.getElementById("batalHapusBtn"); // Cite: 1

  // Get form elements for modalSupplier
  const supplierIdInput = document.getElementById("supplier_id"); // Cite: 1
  const namaSupplierInput = document.getElementById("nama_supplier"); // Cite: 1
  const kontakInput = document.getElementById("kontak"); // Cite: 1
  const alamatTextarea = document.getElementById("alamat"); // Cite: 1
  const emailInput = document.getElementById("email"); // Cite: 1
  const barangSupplierInput = document.getElementById("barang_supplier"); // Cite: 1
  const hargaInput = document.getElementById("harga"); // Cite: 1
  const formSupplier = modalSupplier.querySelector("form"); // Cite: 1
  const formSupplierSubmitBtn = formSupplier.querySelector(
    'button[type="submit"]'
  ); // Cite: 1

  // Get hidden input for delete modal
  const hapusSupplierIdInput = document.getElementById("hapus_supplier_id"); // Cite: 1

  let hargaSaatIni = 0; // Untuk fungsi hitungTotal, perlu inisialisasi

  // Function to open a modal with fade-in animation
  function openModal(modalElement) {
    modalElement.classList.remove("modal-closing"); // Pastikan tidak ada class closing yang mengganggu
    modalElement.style.display = "flex"; // Set display ke flex secara langsung
    setTimeout(() => {
      // Tambahkan class active setelah sedikit delay untuk animasi
      modalElement.classList.add("modal-active"); // Cite: 1
    }, 10); // Delay kecil untuk memastikan properti display terdaftar sebelum animasi dimulai
  }

  // Function to close a modal with fade-out animation
  function closeModal(modalElement) {
    modalElement.classList.remove("modal-active"); // Hapus class active terlebih dahulu
    modalElement.classList.add("modal-closing"); // Tambahkan class closing untuk memicu fade-out

    // Dengarkan akhir animasi sebelum mengatur display ke 'none'
    modalElement.addEventListener("animationend", function handler() {
      // Pastikan modal masih dalam proses penutupan (tidak dibuka kembali saat animasi)
      if (
        !modalElement.classList.contains("modal-active") &&
        modalElement.classList.contains("modal-closing")
      ) {
        modalElement.style.display = "none"; // Cite: 1
        modalElement.classList.remove("modal-closing"); // Bersihkan class closing
      }
      modalElement.removeEventListener("animationend", handler); // Hapus listener untuk mencegah panggilan berulang
    });
  }

  // Event listener for "Tambah Supplier" button
  if (openModalBtn) {
    openModalBtn.addEventListener("click", function () {
      // Clear form saat buka modal input baru
      formSupplier.reset(); // Menggunakan form.reset() lebih ringkas
      supplierIdInput.value = ""; // Kosongkan ID tersembunyi
      formSupplierSubmitBtn.textContent = "Simpan"; // Ubah teks tombol
      openModal(modalSupplier); // Cite: 1
    });
  }

  // Event listeners for "Edit" buttons
  editSupplierBtns.forEach((button) => {
    button.addEventListener("click", function () {
      const data = this.dataset; // Ambil semua atribut data-
      supplierIdInput.value = data.id; // Cite: 1
      namaSupplierInput.value = data.nama; // Cite: 1
      kontakInput.value = data.kontak; // Cite: 1
      alamatTextarea.value = data.alamat; // Cite: 1
      emailInput.value = data.email; // Cite: 1
      barangSupplierInput.value = data.barang; // Cite: 1
      hargaInput.value = data.harga || ""; // Cite: 1
      formSupplierSubmitBtn.textContent = "Update"; // Ubah teks tombol
      openModal(modalSupplier); // Cite: 1
    });
  });

  // Event listeners for "Hapus" buttons
  hapusSupplierBtns.forEach((button) => {
    button.addEventListener("click", function () {
      const supplierId = this.getAttribute("data-id"); // Cite: 1
      hapusSupplierIdInput.value = supplierId; // Set ID ke input tersembunyi di form hapus
      openModal(modalHapus); // Cite: 1
    });
  });

  // Event listeners for closing modals (by clicking close button)
  if (closeSupplierModalBtn) {
    closeSupplierModalBtn.addEventListener("click", () =>
      closeModal(modalSupplier)
    ); // Cite: 1
  }
  if (closeHapusModalBtn) {
    closeHapusModalBtn.addEventListener("click", () => closeModal(modalHapus)); // Cite: 1
  }
  if (batalHapusBtn) {
    batalHapusBtn.addEventListener("click", () => closeModal(modalHapus)); // Cite: 1
  }

  // Close modal when clicking outside the modal content
  window.addEventListener("click", function (event) {
    if (event.target == modalSupplier) {
      // Cite: 1
      closeModal(modalSupplier); // Cite: 1
    }
    if (event.target == modalHapus) {
      // Cite: 1
      closeModal(modalHapus); // Cite: 1
    }
    // Tambahan untuk modal lain yang mungkin ada di halaman ini
    const modalPembelian = document.getElementById("modalPembelian"); // Cite: 1
    const modalEditPermintaan = document.getElementById("modalEditPermintaan"); // Cite: 1
    const modalEditStatus = document.getElementById("modalEditStatus"); // Cite: 1

    if (event.target == modalPembelian) closeModal(modalPembelian); // Cite: 1
    if (event.target == modalEditPermintaan) closeModal(modalEditPermintaan); // Cite: 1
    if (event.target == modalEditStatus) closeModal(modalEditStatus); // Cite: 1
  });

  // Functions for "Pengajuan Pembelian" modal (assuming they are still relevant for this page)
  // Ideally, these functions should be in data-pembelian-kepala-keuangan.php's JS,
  // but included here based on your provided context.
  window.openModalPembelian = function (id, nama, barang, harga) {
    const modalPembelian = document.getElementById("modalPembelian"); // Define this if it's not global
    document.getElementById("modalSupplierId").value = id; // Cite: 1
    document.getElementById("modalNamaSupplier").value = nama; // Cite: 1
    document.getElementById("modalBarang").value = barang; // Cite: 1
    document.getElementById("modalHarga").value = harga; // Cite: 1
    hargaSaatIni = parseFloat(harga); // Cite: 1

    document.getElementById("modalJumlah").value = ""; // Cite: 1
    document.getElementById("modalTotalHarga").value = ""; // Cite: 1
    document.getElementById("inputTotalHarga").value = ""; // Cite: 1

    openModal(modalPembelian); // Use the new openModal function
  };

  window.closeModalPembelian = function () {
    // Rename to avoid conflict with generic closeModal
    const modalPembelian = document.getElementById("modalPembelian"); // Define this if it's not global
    closeModal(modalPembelian); // Use the new closeModal function
  };

  window.hitungTotal = function () {
    // Cite: 1
    const jumlah = parseInt(document.getElementById("modalJumlah").value); // Cite: 1
    if (!isNaN(jumlah) && jumlah > 0) {
      // Cite: 1
      const total = hargaSaatIni * jumlah; // Cite: 1
      document.getElementById("modalTotalHarga").value =
        "Rp " + total.toLocaleString("id-ID"); // Cite: 1
      document.getElementById("inputTotalHarga").value = total; // Cite: 1
    } else {
      document.getElementById("modalTotalHarga").value = ""; // Cite: 1
      document.getElementById("inputTotalHarga").value = ""; // Cite: 1
    }
  };

  // Functions for "Edit Permintaan" modal
  window.editPermintaan = function (id, jumlah) {
    const modalEditPermintaan = document.getElementById("modalEditPermintaan"); // Define this if it's not global
    document.getElementById("editId").value = id; // Cite: 1
    document.getElementById("editJumlah").value = jumlah; // Cite: 1
    openModal(modalEditPermintaan); // Use the new openModal function
  };

  window.closeEditModal = function () {
    // Cite: 1
    const modalEditPermintaan = document.getElementById("modalEditPermintaan"); // Define this if it's not global
    closeModal(modalEditPermintaan); // Use the new closeModal function
  };

  // NEW FUNCTIONS for "Edit Status" modal
  window.editStatus = function (id, currentStatus) {
    const modalEditStatus = document.getElementById("modalEditStatus"); // Define this if it's not global
    document.getElementById("editStatusId").value = id; // Cite: 1
    document.getElementById("selectStatus").value = currentStatus; // Set the current status
    openModal(modalEditStatus); // Use the new openModal function
  };

  window.closeStatusModal = function () {
    // Cite: 1
    const modalEditStatus = document.getElementById("modalEditStatus"); // Define this if it's not global
    closeModal(modalEditStatus); // Use the new closeModal function
  };
});
