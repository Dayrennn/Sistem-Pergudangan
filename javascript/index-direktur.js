// Modal Ajukan Pembelian
const modalPembelian = document.getElementById("modalPembelian");
const modalSupplierIdInput = document.getElementById("modalSupplierId");
const modalNamaSupplierInput = document.getElementById("modalNamaSupplier");
const modalBarangInput = document.getElementById("modalBarang");
const modalHargaInput = document.getElementById("modalHarga");
const modalJumlahInput = document.getElementById("modalJumlah");
const modalTotalHargaInput = document.getElementById("modalTotalHarga");
const inputTotalHargaInput = document.getElementById("inputTotalHarga");

let hargaSaatIni = 0;

function openModal(id, nama, barang, harga) {
  modalSupplierIdInput.value = id;
  modalNamaSupplierInput.value = nama;
  modalBarangInput.value = barang;
  modalHargaInput.value = harga;
  hargaSaatIni = parseFloat(harga);

  modalJumlahInput.value = "";
  modalTotalHargaInput.value = "";
  inputTotalHargaInput.value = "";

  modalPembelian.style.display = "flex";
}

function closeModal() {
  modalPembelian.style.display = "none";
}

function hitungTotal(stokMaksimal = null) {
  const jumlah = parseInt(modalJumlahInput.value);
  if (!isNaN(jumlah) && jumlah > 0) {
    if (stokMaksimal !== null && jumlah > stokMaksimal) {
      alert("Jumlah melebihi stok yang tersedia.");
      modalJumlahInput.value = "";
      modalTotalHargaInput.value = "";
      inputTotalHargaInput.value = "";
      return;
    }
    const total = hargaSaatIni * jumlah;
    modalTotalHargaInput.value = "Rp " + total.toLocaleString("id-ID");
    inputTotalHargaInput.value = total;
  } else {
    modalTotalHargaInput.value = "";
    inputTotalHargaInput.value = "";
  }
}

// Modal Edit Permintaan (Jumlah)
const modalEditPermintaan = document.getElementById("modalEditPermintaan");
const editIdInput = document.getElementById("editId");
const editJumlahInput = document.getElementById("editJumlah");

function editPermintaan(id, jumlah) {
  editIdInput.value = id;
  editJumlahInput.value = jumlah;
  modalEditPermintaan.style.display = "flex";
}

function closeEditModal() {
  modalEditPermintaan.style.display = "none";
}

// Modal Ubah Status
const modalUbahStatus = document.getElementById("modalUbahStatus");
const modalIdUbahStatusInput = document.getElementById("modal-id");
const modalStatusSelect = document.getElementById("modal-status");

function openModalUbahStatus(id, status) {
  console.log(
    "Membuka modal ubah status dengan ID:",
    id,
    "dan Status:",
    status
  );
  modalIdUbahStatusInput.value = id;
  modalStatusSelect.value = status;
  modalUbahStatus.style.display = "block";
}

function closeModalUbahStatus() {
  modalUbahStatus.style.display = "none";
}

// Tutup modal jika klik di luar modal
window.onclick = function (event) {
  if (event.target == modalUbahStatus) closeModalUbahStatus();
  if (event.target == modalPembelian) closeModal();
  if (event.target == modalEditPermintaan) closeEditModal();
};

// Fungsi ambil data stok tertinggi dan terendah dari server
function loadStockStats() {
  $.ajax({
    url: "get_stock_stats.php", // PHP harus mengembalikan JSON stok_tertinggi dan stok_terendah
    method: "GET",
    dataType: "json",
    success: function (data) {
      $("#stok-tertinggi").text(data.stok_tertinggi);
      $("#stok-terendah").text(data.stok_terendah);
    },
    error: function () {
      console.error("Gagal mengambil data stok produk");
    },
  });
}

$(document).ready(function () {
  loadStockStats(); // load saat awal halaman

  setInterval(loadStockStats, 5000); // update setiap 5 detik
});

// Fungsi ambil data statistik lain (misal total barang)
function loadStats() {
  $.ajax({
    url: "get_stats.php", // ganti dengan file PHP yang mengembalikan JSON {total_barang, stok_tertinggi, stok_terendah}
    method: "GET",
    dataType: "json",
    success: function (data) {
      $("div.card").eq(0).find("h3").text(data.total_barang);
      $("div.card").eq(1).find("h3").text(data.stok_tertinggi);
      $("div.card").eq(2).find("h3").text(data.stok_terendah);
    },
    error: function () {
      console.error("Gagal mengambil data statistik");
    },
  });
}

// Jalankan semua saat halaman siap dan update berkala
$(document).ready(function () {
  loadStats();
  loadStockStats();

  setInterval(loadStats, 5000);
  setInterval(loadStockStats, 5000);
});
