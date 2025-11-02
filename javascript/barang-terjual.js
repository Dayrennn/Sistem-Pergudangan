document.addEventListener("DOMContentLoaded", function () {
  console.log("Penjualan System JS initialized");

  // ====================== MODAL EDIT STATUS ======================
  const modalEdit = document.getElementById("modalEdit");
  const closeModalEdit = document.getElementById("closeModalEdit");
  const formEditPesanan = document.getElementById("formEditPesanan");
  const editPesananId = document.getElementById("editPesananId");
  const editStatusSelect = document.getElementById("editStatus");
  const overlayEdit = document.getElementById("overlayEdit");

  // Debug: Check if elements exist
  if (!modalEdit) console.error("Modal Edit element not found");
  if (!formEditPesanan) console.error("Edit form element not found");

  // Show edit modal function
  function showEditModal(pesananId, currentStatus, idTerjual = "") {
    console.log(
      `Showing edit modal for pesananId: ${pesananId}, status: ${currentStatus}`
    );

    if (modalEdit) {
      modalEdit.style.display = "block";
      if (overlayEdit) overlayEdit.style.display = "block";

      // Set form values
      if (editPesananId) editPesananId.value = pesananId;
      if (editStatusSelect) editStatusSelect.value = currentStatus;

      // Set id_terjual if the field exists
      const editIdTerjual = document.getElementById("editIdTerjual");
      if (editIdTerjual) editIdTerjual.value = idTerjual;
    }
  }

  // Hide edit modal function
  function hideEditModal() {
    if (modalEdit) modalEdit.style.display = "none";
    if (overlayEdit) overlayEdit.style.display = "none";
  }

  // Edit button click handler
  document
    .querySelectorAll(".btn-edit-status, .editTerjualBtn")
    .forEach((button) => {
      button.addEventListener("click", function () {
        const row = this.closest("tr");
        const pesananId = row.dataset.pesananId;
        const currentStatus =
          row.dataset.status || row.querySelector(".status").textContent.trim();
        const idTerjual = row.dataset.idTerjual || "";

        showEditModal(pesananId, currentStatus, idTerjual);
      });
    });

  // Close modal handlers
  if (closeModalEdit) {
    closeModalEdit.addEventListener("click", hideEditModal);
  }

  if (overlayEdit) {
    overlayEdit.addEventListener("click", hideEditModal);
  }

  // Form submission handler
  if (formEditPesanan) {
    formEditPesanan.addEventListener("submit", function (e) {
      e.preventDefault();

      const formData = new FormData(this);
      console.log(
        "Submitting form with data:",
        Object.fromEntries(formData.entries())
      );

      fetch("penjualan.php", {
        method: "POST",
        body: formData,
      })
        .then((response) => {
          if (!response.ok) throw new Error("Network response was not ok");
          return response.json();
        })
        .then((data) => {
          if (data.success) {
            alert(data.message);
            hideEditModal();
            location.reload(); // Refresh to see changes
          } else {
            throw new Error(data.message || "Update failed");
          }
        })
        .catch((error) => {
          console.error("Error:", error);
          alert("Error updating status: " + error.message);
        });
    });
  }

  // ====================== DELETE FUNCTIONALITY ======================
  document
    .querySelectorAll(".btn-delete, .hapusTerjualBtn")
    .forEach((button) => {
      button.addEventListener("click", function (e) {
        e.preventDefault();

        const row = this.closest("tr");
        const pesananId = row.dataset.pesananId;
        const idTerjual = row.dataset.idTerjual || "";

        if (!confirm(`Yakin ingin menghapus pesanan ID ${pesananId}?`)) return;

        const formData = new FormData();
        formData.append("action", "delete");
        formData.append("pesanan_id", pesananId);
        if (idTerjual) formData.append("id_terjual", idTerjual);

        fetch("penjualan.php", {
          method: "POST",
          body: formData,
        })
          .then((response) => {
            if (!response.ok) throw new Error("Network response was not ok");
            return response.json();
          })
          .then((data) => {
            if (data.success) {
              alert(data.message);
              row.remove();

              // Renumber table rows
              document
                .querySelectorAll("table tbody tr")
                .forEach((row, index) => {
                  const noCell = row.querySelector("td:first-child");
                  if (noCell) noCell.textContent = index + 1;
                });
            } else {
              throw new Error(data.message || "Delete failed");
            }
          })
          .catch((error) => {
            console.error("Error:", error);
            alert("Error deleting order: " + error.message);
          });
      });
    });

  // ====================== SEARCH FUNCTIONALITY ======================
  const searchInput = document.getElementById("searchInput");
  if (searchInput) {
    searchInput.addEventListener("keyup", function () {
      const value = this.value.toLowerCase();
      document.querySelectorAll("table tbody tr").forEach((row) => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(value) ? "" : "none";
      });
    });
  }

  // ====================== ADD NEW ORDER MODAL ======================
  const modalPelanggan = document.getElementById("modalPelanggan");
  const closeModalPelanggan = modalPelanggan?.querySelector(".close-btn");

  // Show/hide add order modal
  if (modalPelanggan && closeModalPelanggan) {
    document
      .getElementById("openAddOrderModal")
      ?.addEventListener("click", () => {
        modalPelanggan.style.display = "block";
      });

    closeModalPelanggan.addEventListener("click", () => {
      modalPelanggan.style.display = "none";
    });
  }

  // Product selection handler
  const barangSelect = document.getElementById("barang_id");
  const hargaHidden = document.getElementById("harga_produk_hidden");
  const stokMsg = document.getElementById("stok_msg");

  if (barangSelect && hargaHidden && stokMsg) {
    barangSelect.addEventListener("change", function () {
      const selectedOption = this.options[this.selectedIndex];
      const harga = selectedOption.dataset.harga;
      const stok = selectedOption.dataset.stok;

      if (harga) hargaHidden.value = harga;
      stokMsg.textContent = `Stok tersedia: ${stok}`;
    });
  }

  // Quantity validation
  const jumlahPesan = document.getElementById("jumlah_pesan");
  if (jumlahPesan) {
    jumlahPesan.addEventListener("input", function () {
      const selectedBarang = barangSelect?.options[barangSelect.selectedIndex];
      if (!selectedBarang) return;

      const stok = parseInt(selectedBarang.dataset.stok);
      const requested = parseInt(this.value) || 0;

      if (requested <= 0) {
        stokMsg.textContent = "Jumlah harus lebih dari 0";
        this.value = 1;
      } else if (requested > stok) {
        stokMsg.textContent = `Stok tidak cukup! Tersedia: ${stok}`;
      } else {
        stokMsg.textContent = `Stok tersedia: ${stok}`;
      }
    });
  }

  // ====================== ERROR HANDLING ======================
  window.addEventListener("error", function (e) {
    console.error("Uncaught error:", e.error);
    alert("Terjadi kesalahan: " + e.message);
  });
});
