document.addEventListener("DOMContentLoaded", function () {
  // Fungsi untuk menampilkan modal alert kustom
  function showCustomAlert(message, title = "Pesan") {
    const customAlertModal = document.getElementById("customAlertModal");
    const customAlertTitle = document.getElementById("customAlertTitle");
    const customAlertMessage = document.getElementById("customAlertMessage");
    const customAlertOkBtn = document.getElementById("customAlertOkBtn");

    // Pastikan elemen customAlertModal ada sebelum mencoba mengakses propertinya
    if (customAlertModal) {
      customAlertTitle.textContent = title;
      customAlertMessage.textContent = message;
      customAlertModal.classList.add("show"); // Tampilkan modal

      customAlertOkBtn.onclick = function () {
        customAlertModal.classList.remove("show"); // Sembunyikan modal
      };

      // Tutup saat mengklik di luar modal
      // Penting: tambahkan event listener ini hanya sekali
      // Hapus event listener lama jika ada untuk mencegah duplikasi
      // atau gunakan delegasi event jika customAlertModal tidak berubah
      window.removeEventListener("click", closeCustomAlertOnOutsideClick); // Hapus yang sebelumnya
      window.addEventListener("click", closeCustomAlertOnOutsideClick); // Tambahkan yang baru
    } else {
      // Fallback jika modal kustom tidak ditemukan (misal: elemen HTML belum ada)
      alert(title + ": " + message);
    }
  }

  // Helper function untuk menutup custom alert saat klik di luar
  function closeCustomAlertOnOutsideClick(event) {
    const customAlertModal = document.getElementById("customAlertModal");
    if (event.target == customAlertModal) {
      customAlertModal.classList.remove("show");
      window.removeEventListener("click", closeCustomAlertOnOutsideClick); // Hapus event listener setelah ditutup
    }
  }

  // Modal Pelanggan
  const modalPelanggan = document.getElementById("modalPelanggan");
  const closeBtn = document.querySelector("#modalPelanggan .close-btn");
  const batalBtn = document.getElementById("batalBtn");
  const openModalBtn = document.getElementById("openModalBtn");
  const formPelanggan = document.getElementById("formPelanggan");
  const modalTitle = document.getElementById("modalTitle");

  // Elemen form
  const pelangganIdInput = document.getElementById("pelanggan_id");
  const namaPelangganInput = document.getElementById("nama_pelanggan");
  const kontakInput = document.getElementById("kontak");
  const emailInput = document.getElementById("email");
  const alamatInput = document.getElementById("alamat");
  const barangIdSelect = document.getElementById("barang_id");
  const jumlahPesanInput = document.getElementById("jumlah_pesan");
  const totalHargaDisplay = document.getElementById("total_harga_display");
  const totalHargaHidden = document.getElementById("total_harga");
  const stokHelp = document.getElementById("stokHelp");

  // Reset form
  function resetForm() {
    formPelanggan.reset();
    pelangganIdInput.value = "";
    modalTitle.textContent = "Tambah Pelanggan Baru";
    totalHargaDisplay.textContent = "Rp 0";
    totalHargaHidden.value = "0";
    stokHelp.textContent = "";
    barangIdSelect.value = "";
    jumlahPesanInput.value = "0"; // Mengubah default menjadi 0
    stokHelp.style.color = "inherit"; // Mengatur ulang warna bantuan stok
    // Pastikan tombol submit kembali normal setelah reset
    const submitBtn = formPelanggan.querySelector('button[type="submit"]');
    if (submitBtn) {
      submitBtn.disabled = false;
      submitBtn.textContent = "Simpan"; // Atau teks asli Anda
    }
  }

  // Buka modal untuk menambahkan pelanggan baru
  if (openModalBtn) {
    openModalBtn.addEventListener("click", function () {
      resetForm();
      modalPelanggan.classList.add("show"); // Gunakan kelas untuk menampilkan
    });
  }

  // Tutup modal
  if (closeBtn) {
    closeBtn.addEventListener("click", function () {
      modalPelanggan.classList.remove("show"); // Gunakan kelas untuk menyembunyikan
      resetForm();
    });
  }

  if (batalBtn) {
    batalBtn.addEventListener("click", function () {
      modalPelanggan.classList.remove("show"); // Gunakan kelas untuk menyembunyikan
      resetForm();
    });
  }

  // Tutup modal saat mengklik di luar
  window.addEventListener("click", function (event) {
    if (event.target == modalPelanggan) {
      modalPelanggan.classList.remove("show"); // Gunakan kelas untuk menyembunyikan
      resetForm();
    }
  });

  // Tombol edit pelanggan
  document.querySelectorAll(".editPelangganBtn").forEach((button) => {
    button.addEventListener("click", function () {
      resetForm(); // Reset form terlebih dahulu untuk menghapus nilai sebelumnya
      modalTitle.textContent = "Edit Data Pelanggan";
      modalPelanggan.classList.add("show"); // Gunakan kelas untuk menampilkan

      // Isi form dengan data pelanggan
      pelangganIdInput.value = this.dataset.id;
      namaPelangganInput.value = this.dataset.nama;
      kontakInput.value = this.dataset.kontak;
      emailInput.value = this.dataset.email;
      alamatInput.value = this.dataset.alamat;

      // Isi data produk jika tersedia
      const barangId = this.dataset.barangId;
      const jumlahPesan = this.dataset.jumlahPesan;

      if (barangId && jumlahPesan) {
        barangIdSelect.value = barangId;
        // Picu event change untuk memperbarui info stok dan total harga
        // Gunakan setTimeout untuk memastikan pembaruan DOM selesai sebelum dispatch
        setTimeout(() => {
          const event = new Event("change");
          barangIdSelect.dispatchEvent(event);
          jumlahPesanInput.value = jumlahPesan;
          calculateTotalPrice(); // Hitung ulang setelah mengatur kuantitas
        }, 0);
      } else {
        // Jika tidak ada produk yang terkait, pastikan bidang produk direset
        barangIdSelect.value = "";
        jumlahPesanInput.value = "0";
        calculateTotalPrice();
      }
    });
  });

  // Hitung total harga
  function calculateTotalPrice() {
    const jumlah = parseInt(jumlahPesanInput.value) || 0;
    const selectedOption = barangIdSelect.options[barangIdSelect.selectedIndex];

    if (selectedOption && selectedOption.value) {
      const harga = parseFloat(selectedOption.dataset.harga) || 0;
      const stok = parseInt(selectedOption.dataset.stok) || 0;
      const total = harga * jumlah;

      totalHargaDisplay.textContent = `Rp ${total.toLocaleString("id-ID")}`;
      totalHargaHidden.value = total;
      stokHelp.textContent = `Stok tersedia: ${stok}`;

      if (jumlah > stok) {
        stokHelp.textContent = `Jumlah melebihi stok! Stok tersedia: ${stok}`;
        stokHelp.style.color = "red";
      } else {
        stokHelp.style.color = "inherit";
      }
    } else {
      totalHargaDisplay.textContent = "Rp 0";
      totalHargaHidden.value = "0";
      stokHelp.textContent = "";
      stokHelp.style.color = "inherit"; // Mengatur ulang warna bantuan stok
    }
  }

  // Event listener untuk perhitungan
  if (barangIdSelect) {
    barangIdSelect.addEventListener("change", calculateTotalPrice);
  }
  if (jumlahPesanInput) {
    jumlahPesanInput.addEventListener("input", calculateTotalPrice);
  }

  // Pengiriman form dengan AJAX
  if (formPelanggan) {
    formPelanggan.addEventListener("submit", function (e) {
      e.preventDefault();

      // Validasi bidang yang wajib diisi
      if (
        !namaPelangganInput.value ||
        !kontakInput.value ||
        !emailInput.value ||
        !alamatInput.value
      ) {
        showCustomAlert("Semua field pelanggan wajib diisi");
        return;
      }

      // Validasi pemilihan produk jika kuantitas > 0
      // Pastikan barang_id harus dipilih jika jumlah_pesan lebih dari 0
      if (parseInt(jumlahPesanInput.value) > 0 && !barangIdSelect.value) {
        showCustomAlert("Silakan pilih produk jika mengisi jumlah pesanan");
        return;
      }

      const submitBtn = formPelanggan.querySelector('button[type="submit"]');
      const originalText = submitBtn.textContent;
      submitBtn.disabled = true;
      submitBtn.textContent = "Menyimpan...";

      const formData = new FormData(formPelanggan);
      formData.append("simpan_pelanggan", "1");

      fetch("../pages/pelanggan.php", {
        method: "POST",
        body: formData,
        headers: {
          "X-Requested-With": "XMLHttpRequest", // Identifikasi sebagai permintaan AJAX
        },
      })
        .then((response) => response.json()) // Harapkan respons JSON
        .then((data) => {
          if (data.success) {
            showCustomAlert(data.message); // Tampilkan pesan sukses
            modalPelanggan.classList.remove("show"); // Sembunyikan modal
            window.location.reload(); // <--- BARIS INI AKAN MEMUAT ULANG HALAMAN
          } else {
            // Tampilkan pesan kesalahan dari PHP
            showCustomAlert("Terjadi kesalahan: " + data.message);
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
          }
        })
        .catch((error) => {
          console.error("Error:", error);
          showCustomAlert("Terjadi kesalahan saat menyimpan data");
          submitBtn.disabled = false;
          submitBtn.textContent = originalText;
        });
    });
  }

  // Modal konfirmasi hapus
  const modalHapus = document.getElementById("modalHapus");
  const closeHapusBtn = document.querySelector("#modalHapus .close-btn");
  const batalHapusBtn = document.getElementById("batalHapusBtn");
  const hapusPelangganIdInput = document.getElementById("hapus_pelanggan_id");
  const formHapusPelanggan = document.getElementById("formHapusPelanggan");

  // Klik tombol hapus
  document.querySelectorAll(".hapusPelangganBtn").forEach((button) => {
    button.addEventListener("click", function () {
      hapusPelangganIdInput.value = this.dataset.id;
      modalHapus.classList.add("show"); // Gunakan kelas untuk menampilkan
    });
  });

  // Tutup modal hapus
  if (closeHapusBtn) {
    closeHapusBtn.addEventListener("click", function () {
      modalHapus.classList.remove("show"); // Gunakan kelas untuk menyembunyikan
    });
  }

  if (batalHapusBtn) {
    batalHapusBtn.addEventListener("click", function () {
      modalHapus.classList.remove("show"); // Gunakan kelas untuk menyembunyikan
    });
  }

  window.addEventListener("click", function (event) {
    if (event.target == modalHapus) {
      modalHapus.classList.remove("show"); // Gunakan kelas untuk menyembunyikan
    }
  });

  // Pengiriman form hapus
  if (formHapusPelanggan) {
    formHapusPelanggan.addEventListener("submit", function (e) {
      e.preventDefault();

      const formData = new FormData(this);
      const deleteBtn = this.querySelector('button[type="submit"]');
      const originalText = deleteBtn.textContent;
      deleteBtn.disabled = true;
      deleteBtn.textContent = "Menghapus...";

      fetch("../pages/hapus_pelanggan.php", { // Pastikan ini mengarah ke file hapus yang benar
        method: "POST",
        body: formData,
      })
        .then((response) => {
          const contentType = response.headers.get("content-type");
          if (contentType && contentType.includes("application/json")) {
            return response.json(); // Respons diharapkan JSON
          } else {
            // Jika bukan JSON, baca sebagai teks untuk debugging
            return response.text().then(text => {
                throw new Error("Respons bukan JSON yang valid: " + text);
            });
          }
        })
        .then((data) => {
          if (data.status === "success") { // Periksa properti 'status' dari JSON
            showCustomAlert(data.message);
            modalHapus.classList.remove("show");
            location.reload(); // Muat ulang halaman setelah hapus berhasil
          } else {
            showCustomAlert("Gagal menghapus: " + data.message); // Tampilkan pesan error dari server
            deleteBtn.disabled = false;
            deleteBtn.textContent = originalText;
          }
        })
        .catch((error) => {
          console.error("Error:", error);
          showCustomAlert("Terjadi kesalahan saat menghapus data: " + error.message);
          deleteBtn.disabled = false;
          deleteBtn.textContent = originalText;
        });
    });
  }
});

// Jika Anda ingin membuat showCustomAlert dapat diakses secara global (misalnya, dari skrip lain yang dimuat setelah ini)
window.showCustomAlert = showCustomAlert;