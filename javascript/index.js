// javascript/index.js

// =====================================================================
// Fungsi Global untuk Modal Ubah Status Permintaan
// Ini didefinisikan di luar DOMContentLoaded agar langsung tersedia untuk onclick=""
// =====================================================================
function openModalUbahStatus(id, status) {
  console.log(
    "Membuka modal ubah status dengan ID:",
    id,
    "dan Status:",
    status
  );
  const modalUbahStatus = document.getElementById("modalUbahStatus");
  const modalIdUbahStatusInput = document.getElementById("modal-id");
  const modalStatusSelect = document.getElementById("modal-status");

  if (modalIdUbahStatusInput && modalStatusSelect && modalUbahStatus) {
    modalIdUbahStatusInput.value = id;
    modalStatusSelect.value = status;
    modalUbahStatus.classList.add("show");
    modalUbahStatus.style.display = "flex"; // Pastikan modal ditampilkan
  } else {
    console.error("Elemen modal atau input status tidak ditemukan.");
  }
}

function closeModalUbahStatus() {
  const modalUbahStatus = document.getElementById("modalUbahStatus");
  if (modalUbahStatus) {
    modalUbahStatus.classList.remove("show");
    setTimeout(() => {
      modalUbahStatus.style.display = "none"; // Sembunyikan setelah transisi
    }, 300); // Sesuaikan dengan durasi transisi di CSS Anda
  }
}

// =====================================================================
// Sisa kode di bawah tetap di dalam DOMContentLoaded
// untuk memastikan elemen DOM tersedia sebelum diakses
// =====================================================================
document.addEventListener("DOMContentLoaded", function () {
  // =====================================================================
  // Variabel Global untuk Modal Ajukan Pembelian
  // =====================================================================
  const modalPembelian = document.getElementById("modalPembelian");
  const modalSupplierIdInput = document.getElementById("modalSupplierId");
  const modalNamaSupplierInput = document.getElementById("modalNamaSupplier");
  const modalBarangInput = document.getElementById("modalBarang");
  const modalHargaInput = document.getElementById("modalHarga");
  const modalJumlahInput = document.getElementById("modalJumlah");
  const modalTotalHargaInput = document.getElementById("modalTotalHarga");
  const inputTotalHargaInput = document.getElementById("inputTotalHarga"); // Ini kemungkinan input hidden untuk total harga akhir

  // =====================================================================
  // Variabel Global untuk Modal Edit Permintaan (jika ada di index.php)
  // Asumsi ID modalnya adalah 'editModalPermintaan'
  // =====================================================================
  const modalEditPermintaan = document.getElementById("editModalPermintaan");
  const editPermintaanIdInput = document.getElementById("edit-permintaan-id"); // Sesuaikan ID input
  const editPermintaanBarangInput = document.getElementById(
    "edit-permintaan-barang"
  ); // Sesuaikan ID input
  // Tambahkan variabel lain sesuai input di modal edit permintaan Anda

  // =====================================================================
  // Fungsi untuk Modal Ajukan Pembelian
  // =====================================================================
  function openModal() {
    // Ini kemungkinan fungsi untuk modal Ajukan Pembelian
    if (modalPembelian) {
      modalPembelian.classList.add("show");
      modalPembelian.style.display = "block"; // Atau flex, sesuai CSS Anda
    }
  }

  function closeModal() {
    // Ini kemungkinan fungsi untuk modal Ajukan Pembelian
    if (modalPembelian) {
      modalPembelian.classList.remove("show");
      setTimeout(() => {
        modalPembelian.style.display = "none";
      }, 300); // Sesuaikan durasi transisi
    }
  }

  function hitungTotal(stokMaksimal = null) {
    const harga = parseFloat(modalHargaInput.value);
    const jumlah = parseInt(modalJumlahInput.value);

    if (isNaN(harga) || isNaN(jumlah) || jumlah <= 0) {
      modalTotalHargaInput.value = "";
      inputTotalHargaInput.value = "";
      return;
    }

    if (stokMaksimal !== null && jumlah > stokMaksimal) {
      alert("Jumlah melebihi stok yang tersedia.");
      modalJumlahInput.value = "";
      modalTotalHargaInput.value = "";
      inputTotalHargaInput.value = "";
      return;
    }

    const total = harga * jumlah;
    modalTotalHargaInput.value = total.toFixed(2); // Format 2 angka di belakang koma
    inputTotalHargaInput.value = total.toFixed(2);
  }

  // Event Listeners untuk modal pembelian
  if (modalJumlahInput) {
    modalJumlahInput.addEventListener("input", function () {
      hitungTotal();
    });
  }
  if (modalHargaInput) {
    modalHargaInput.addEventListener("input", function () {
      hitungTotal();
    });
  }

  // =====================================================================
  // Fungsi untuk Modal Edit Permintaan (jika ada di index.php)
  // =====================================================================
  function openEditModalPermintaan(id, barang_permintaan) {
    if (
      modalEditPermintaan &&
      editPermintaanIdInput &&
      editPermintaanBarangInput
    ) {
      editPermintaanIdInput.value = id;
      editPermintaanBarangInput.value = barang_permintaan;
      // ... isi input lainnya
      modalEditPermintaan.classList.add("show");
      modalEditPermintaan.style.display = "flex"; // Atau 'block' tergantung kebutuhan layout
    }
  }

  function closeEditModalPermintaan() {
    if (modalEditPermintaan) {
      modalEditPermintaan.classList.remove("show");
      setTimeout(() => {
        modalEditPermintaan.style.display = "none";
      }, 300);
    }
  }

  // =====================================================================
  // Event Listener untuk Menutup Modal Ketika Klik di Luar Area Modal
  // =====================================================================
  window.onclick = function (event) {
    // Karena openModalUbahStatus dipindahkan ke global, kita perlu mendapatkan
    // referensi modalUbahStatus lagi di sini atau menjadikannya global.
    // Lebih baik mendapatkan referensi elemen di dalam fungsi jika tidak global
    // atau jika ingin memisahkan fungsi dari DOMContentLoaded.
    const modalUbahStatusElement = document.getElementById("modalUbahStatus");
    const modalPembelianElement = document.getElementById("modalPembelian");
    const modalEditPermintaanElement = document.getElementById(
      "editModalPermintaan"
    );

    if (event.target == modalUbahStatusElement) {
      closeModalUbahStatus();
    }
    if (event.target == modalPembelianElement) {
      closeModal();
    }
    if (event.target == modalEditPermintaanElement) {
      closeEditModalPermintaan();
    }
  };

  // =====================================================================
  // Event Listener untuk tombol "Ajukan Pembelian"
  // =====================================================================
  const btnAjukanPembelian = document.querySelector(".btn-ajukan-pembelian");
  if (btnAjukanPembelian) {
    btnAjukanPembelian.addEventListener("click", openModal);
  }

  // =====================================================================
  // Event Listener untuk tombol "Batal" di dalam modal (Ajukan Pembelian)
  // =====================================================================
  const btnBatalPembelian = document.querySelector(
    "#modalPembelian .btn-batal"
  );
  if (btnBatalPembelian) {
    btnBatalPembelian.addEventListener("click", closeModal);
  }
  // Asumsi tombol "Batal" di modal Ubah Status sudah ada onclick="closeModalUbahStatus()"
  // Asumsi tombol "Batal" di modal Edit Permintaan sudah ada onclick="closeEditModalPermintaan()"

  // =====================================================================
  // Event Listener untuk tombol "Delete" (contoh dari index.php)
  // Ini adalah pola umum, sesuaikan dengan implementasi Anda
  // =====================================================================
  document.addEventListener("click", function (event) {
    if (event.target.classList.contains("btn-delete")) {
      const idPermintaan = event.target.dataset.id; // Asumsi Anda punya data-id di tombol
      if (confirm("Apakah Anda yakin ingin menghapus permintaan ini?")) {
        fetch("hapus_permintaan.php", {
          method: "POST",
          headers: {
            "Content-Type": "application/x-www-form-urlencoded",
          },
          body: `id_permintaan=${idPermintaan}`,
        })
          .then((response) => response.text())
          .then((data) => {
            alert("Permintaan berhasil dihapus.");
            location.reload();
          })
          .catch((error) => {
            console.error("Error:", error);
            alert("Terjadi kesalahan saat menghapus permintaan.");
          });
      }
    }
  });

  // =====================================================================
  // Event Listener untuk tombol "Print" (contoh dari index.php)
  // =====================================================================
  const printButton = document.getElementById("printButton");
  if (printButton) {
    printButton.addEventListener("click", function () {
      window.print();
    });
  }
});
