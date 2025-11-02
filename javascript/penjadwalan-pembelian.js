document.addEventListener("DOMContentLoaded", function () {
  // Modal elements
  const modalJadwal = document.getElementById("modalJadwal");
  const modalEditJadwal = document.getElementById("modalEditJadwal");
  const btnTambahJadwal = document.getElementById("btnTambahJadwal");
  const closeButtons = document.querySelectorAll(".close-modal");

  // Form elements
  const formTambahJadwal = document.getElementById("formTambahJadwal");
  const formEditJadwal = document.getElementById("formEditJadwal");
  const supplierSelect = document.getElementById("supplier_id");
  const editSupplierSelect = document.getElementById("edit_supplier_id");

  // Open Add Modal
  if (btnTambahJadwal) {
    btnTambahJadwal.addEventListener("click", openAddModal);
  }

  // Close Modals
  closeButtons.forEach((button) => {
    button.addEventListener("click", closeModal);
  });

  // Close when clicking outside modal content
  window.addEventListener("click", function (event) {
    if (event.target.classList.contains("modal")) {
      closeModal();
    }
  });

  // Supplier change handlers
  if (supplierSelect) {
    supplierSelect.addEventListener("change", function () {
      const barangSelect = document.getElementById("barang_id");
      getBarangBySupplier(this.value, barangSelect);
    });
  }

  if (editSupplierSelect) {
    editSupplierSelect.addEventListener("change", function () {
      const barangSelect = document.getElementById("edit_barang_id");
      getBarangBySupplier(this.value, barangSelect);
    });
  }

  // Form submissions
  if (formTambahJadwal) {
    formTambahJadwal.addEventListener("submit", handleAddSubmit);
  }

  if (formEditJadwal) {
    formEditJadwal.addEventListener("submit", handleEditSubmit);
  }

  // Function to open add modal
  function openAddModal() {
    if (modalJadwal) {
      modalJadwal.classList.add("show");
      document.body.classList.add("body-modal-open");
    }
  }

  // Function to open edit modal
  // Fungsi bukaModalEdit yang sudah diperbaiki
  window.bukaModalEdit = function (button) {
    const modal = document.getElementById("modalEditJadwal");
    if (modal) {
      // Isi data form edit
      document.getElementById("edit_id").value = button.dataset.id;
      document.getElementById("edit_supplier_id").value =
        button.dataset.supplier;
      document.getElementById("edit_jumlah").value = button.dataset.jumlah;
      document.getElementById("edit_tanggal").value = button.dataset.tanggal;
      document.getElementById("edit_status").value = button.dataset.status;

      // Load barang berdasarkan supplier
      const barangSelect = document.getElementById("edit_barang_id");
      const selectedBarang = button.dataset.barang; // Ambil nilai barang yang dipilih

      // Pertama, aktifkan select barang
      barangSelect.disabled = false;

      // Kemudian isi opsi barang
      fetch(
        `penjadwalan-pembelian.php?ajax=get_barang&supplier_id=${button.dataset.supplier}`
      )
        .then((response) => response.json())
        .then((data) => {
          let options = '<option value="">Pilih Barang</option>';
          data.forEach((barang) => {
            const selected = barang === selectedBarang ? "selected" : "";
            options += `<option value="${barang}" ${selected}>${barang}</option>`;
          });
          barangSelect.innerHTML = options;

          // Setelah opsi diisi, tampilkan modal
          modal.classList.add("show");
          document.body.classList.add("body-modal-open");
        })
        .catch((error) => {
          console.error("Error:", error);
          barangSelect.innerHTML =
            '<option value="">Gagal memuat barang</option>';
          // Tetap tampilkan modal meskipun gagal load barang
          modal.classList.add("show");
          document.body.classList.add("body-modal-open");
        });
    }
  };

  // Function to close all modals
  function closeModal() {
    document.querySelectorAll(".modal").forEach((modal) => {
      modal.classList.remove("show");
    });
    document.body.classList.remove("body-modal-open");
  }

  // Function to get barang by supplier
  function getBarangBySupplier(supplierId, targetSelect, selectedBarang = "") {
    if (!supplierId || supplierId <= 0) {
      targetSelect.innerHTML =
        '<option value="">Pilih Supplier terlebih dahulu</option>';
      targetSelect.disabled = true;
      return;
    }

    fetch(`penjadwalan-pembelian.php?ajax=get_barang&supplier_id=${supplierId}`)
      .then((response) => response.json())
      .then((data) => {
        let options = '<option value="">Pilih Barang</option>';
        if (Array.isArray(data)) {
          data.forEach((barang) => {
            const selected = barang === selectedBarang ? "selected" : "";
            options += `<option value="${barang}" ${selected}>${barang}</option>`;
          });
        }
        targetSelect.innerHTML = options;
        targetSelect.disabled = false;
      })
      .catch((error) => {
        console.error("Error:", error);
        targetSelect.innerHTML =
          '<option value="">Gagal memuat barang</option>';
      });
  }

  // Form submit handlers
  function handleAddSubmit(e) {
    e.preventDefault();

    const formData = new FormData(this);
    const loadingIndicator = document.getElementById("loadingIndicator");
    const submitText = document.getElementById("submitText");

    loadingIndicator.style.display = "flex";
    submitText.style.display = "none";

    fetch("penjadwalan-pembelian.php", {
      method: "POST",
      body: formData,
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          alert(data.message);
          closeModal();
          window.location.reload();
        } else {
          alert(data.message);
        }
      })
      .catch((error) => {
        console.error("Error:", error);
        alert("Terjadi kesalahan saat menyimpan data");
      })
      .finally(() => {
        loadingIndicator.style.display = "none";
        submitText.style.display = "inline";
      });
  }

  function handleEditSubmit(e) {
    e.preventDefault();

    const formData = new FormData(this);

    fetch("update_penjadwalan.php", {
      method: "POST",
      body: formData,
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          alert(data.message);
          closeModal();
          window.location.reload();
        } else {
          alert(data.message);
        }
      })
      .catch((error) => {
        console.error("Error:", error);
        alert("Terjadi kesalahan saat mengupdate data");
      });
  }

  // Delete function
  window.hapusJadwal = function (id) {
    if (confirm("Apakah Anda yakin ingin menghapus jadwal ini?")) {
      fetch("hapus_penjadwalan.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded",
        },
        body: `id_pembelian=${id}`,
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            alert(data.message);
            window.location.reload();
          } else {
            alert(data.message);
          }
        })
        .catch((error) => {
          console.error("Error:", error);
          alert("Terjadi kesalahan saat menghapus data");
        });
    }
  };
});
