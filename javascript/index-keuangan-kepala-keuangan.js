document.addEventListener("DOMContentLoaded", function () {
  // --- Variabel untuk semua Modal ---
  const modalPendapatan = document.getElementById("modalPendapatan");
  const modalHutang = document.getElementById("modalHutang"); // Modal Tambah Hutang
  const pengeluaranModal = document.getElementById("pengeluaranModal"); // Modal Tambah Pengeluaran
  const editModal = document.getElementById("editModal"); // Modal Edit Pengeluaran
  const deleteModal = document.getElementById("deleteModal"); // Modal Hapus Pengeluaran
  const editHutangModal = document.getElementById("editHutangModal"); // Modal Edit Hutang
  const deleteHutangModal = document.getElementById("deleteHutangModal"); // Modal Hapus Hutang
  const hutangReminderModal = document.getElementById("hutangReminderModal"); // Modal Notifikasi Hutang
  const modalEditStatus = document.getElementById("modalEditStatus"); // NEW: Modal Edit Status (jika digunakan di ../main/index-kepala-keuangan.php)

  // --- Tombol Pemicu Modal Pembuka ---
  const btnPendapatan = document.getElementById("btnTambahPendapatan");
  const btnHutang = document.getElementById("btnTambahHutang");
  const btnTambahPengeluaran = document.getElementById("btnTambahPengeluaran");

  // --- Fungsi Bantuan untuk Membuka dan Menutup Modal ---
  function openModal(modalElement) {
    if (modalElement) {
      modalElement.style.display = "block";
      document.body.classList.add("modal-open"); // Tambahkan kelas untuk mencegah scroll body
    }
  }

  function closeModal(modalElement) {
    if (modalElement) {
      modalElement.style.display = "none";
      document.body.classList.remove("modal-open"); // Hapus kelas
    }
  }

  // --- Event Listeners untuk Tombol Pemicu Modal Pembuka ---
  if (btnPendapatan) {
    btnPendapatan.onclick = function () {
      openModal(modalPendapatan);
    };
  }
  if (btnHutang) {
    btnHutang.onclick = function () {
      openModal(modalHutang);
    };
  }
  if (btnTambahPengeluaran) {
    btnTambahPengeluaran.onclick = function () {
      openModal(pengeluaranModal);
    };
  }

  // --- Event Listeners untuk Tombol Penutup Modal (semua tombol dengan class 'close' atau 'close-modal') ---
  const allCloseButtons = document.querySelectorAll(
    ".modal .close, .modal .close-modal"
  );
  allCloseButtons.forEach((button) => {
    button.onclick = function () {
      let parentModal = button.closest(".modal");
      closeModal(parentModal);
    };
  });

  // --- Event Listener untuk Menutup Modal ketika mengklik di luar area konten modal ---
  window.onclick = function (event) {
    if (event.target === modalPendapatan) closeModal(modalPendapatan);
    if (event.target === modalHutang) closeModal(modalHutang);
    if (event.target === pengeluaranModal) closeModal(pengeluaranModal);
    if (event.target === editModal) closeModal(editModal);
    if (event.target === deleteModal) closeModal(deleteModal);
    if (event.target === editHutangModal) closeModal(editHutangModal);
    if (event.target === deleteHutangModal) closeModal(deleteHutangModal);
    if (event.target === hutangReminderModal) closeModal(hutangReminderModal);
    if (event.target === modalEditStatus) closeModal(modalEditStatus); // NEW: Close Edit Status Modal
  };

  // --- Logika Khusus Modal Pendapatan: Show/hide debt payment fields ---
  // Menggunakan jQuery karena selektor dan event change lebih ringkas
  if (typeof jQuery !== "undefined") {
    // Cek apakah jQuery tersedia
    $("#kategoriPendapatan").change(function () {
      if ($(this).val() == "Pembayaran Hutang") {
        $("#hutangFields").show();
      } else {
        $("#hutangFields").hide();
      }
    });
  }

  // --- Logika Edit Pengeluaran ---
  // Menggunakan jQuery delegasi event untuk tombol yang mungkin ditambahkan secara dinamis
  $(document).on("click", ".edit-btn", function () {
    console.log("Tombol edit pengeluaran diklik!"); // Log untuk debugging
    const id = $(this).data("id");
    console.log("ID Pengeluaran yang akan diedit:", id); // Log ID

    $.ajax({
      url: "../main/index-kepala-keuangan.php",
      method: "GET",
      data: { id: id, action: "fetch_pengeluaran" },
      dataType: "json", // Harapkan respons JSON
      success: function (response) {
        console.log("Respons fetch_pengeluaran (SUCCESS):", response); // Log respons lengkap
        if (response && response.found) {
          const data = response.data;
          $("#edit_id").val(data.id_pengeluaran);
          $("#edit_tanggal").val(data.tanggal);
          $("#edit_kategori").val(data.kategori);
          $("#edit_jumlah").val(data.jumlah);
          $("#edit_keterangan").val(data.keterangan);
          $("#edit_metode_pembayaran").val(data.metode_pembayaran);
          openModal(editModal);
        } else {
          // Ini akan terjadi jika PHP mengembalikan JSON valid tapi 'found' false
          alert(
            "Data pengeluaran tidak ditemukan: " +
              (response ? response.message : "Respons kosong atau tidak valid.")
          );
          console.error(
            "Data fetched (not found or invalid response):",
            response
          );
        }
      },
      error: function (xhr, status, error) {
        console.error(
          "Error fetching data (AJAX ERROR):",
          error,
          "Response Text:",
          xhr.responseText
        );
        try {
          // Coba parse responseText secara manual jika ada error parsing otomatis
          const response = JSON.parse(xhr.responseText);
          console.log(
            "Respons fetch_pengeluaran (parsed from ERROR):",
            response
          );

          if (response && response.found) {
            const data = response.data;
            $("#edit_id").val(data.id_pengeluaran);
            $("#edit_tanggal").val(data.tanggal);
            $("#edit_kategori").val(data.kategori);
            $("#edit_jumlah").val(data.jumlah);
            $("#edit_keterangan").val(data.keterangan);
            $("#edit_metode_pembayaran").val(data.metode_pembayaran);
            openModal(editModal);
            // Tidak perlu alert error jika data berhasil diisi
          } else {
            alert(
              "Data pengeluaran tidak ditemukan: " +
                (response
                  ? response.message
                  : "Respons kosong atau tidak valid.")
            );
          }
        } catch (e) {
          // Jika responseText bukan JSON valid sama sekali
          alert(
            "Terjadi kesalahan saat mengambil data pengeluaran. Cek konsol browser untuk detail."
          );
          console.error("Failed to parse JSON from error response:", e);
        }
      },
    });
  });

  // --- Logika Hapus Pengeluaran ---
  let pengeluaranToDeleteId;
  $(document).on("click", ".delete-btn", function () {
    pengeluaranToDeleteId = $(this).data("id");
    openModal(deleteModal);
  });
  $("#confirmDelete").click(function () {
    if (pengeluaranToDeleteId) {
      // Ini akan memicu pengalihan halaman, jadi tidak ada AJAX di sini
      window.location.href =
        "../main/index-kepala-keuangan.php?hapus_pengeluaran=" +
        pengeluaranToDeleteId;
    }
  });
  $("#cancelDelete").click(function () {
    closeModal(deleteModal);
    pengeluaranToDeleteId = null;
  });

  // --- Logika Edit Hutang ---
  $(document).on("click", ".edit-hutang-btn", function () {
    const hutangId = $(this).data("id");
    $.ajax({
      url: "../main/index-kepala-keuangan.php", // Pastikan ini mengarah ke file yang benar
      method: "GET",
      data: { id: hutangId, action: "fetch_hutang" },
      dataType: "json",
      success: function (response) {
        console.log("Respons fetch_hutang (SUCCESS):", response);
        if (response && response.found) {
          const data = response.data;
          $("#edit_hutang_id").val(data.id);
          $("#edit_hutang_pegawai_id").val(data.pegawai_id);
          $("#edit_hutang_jumlah").val(data.jumlah);
          $("#edit_hutang_keterangan").val(data.keterangan);
          $("#edit_hutang_tanggal_hutang").val(data.tanggal_hutang);
          $("#edit_hutang_status").val(data.status);

          if (data.status === "lunas") {
            $("#edit_hutang_tanggal_lunas_group").show();
            $("#edit_hutang_tanggal_lunas").val(data.tanggal_lunas);
          } else {
            $("#edit_hutang_tanggal_lunas_group").hide();
            $("#edit_hutang_tanggal_lunas").val("");
          }
          openModal(editHutangModal);
        } else {
          alert(
            "Data hutang tidak ditemukan atau ada error dari server: " +
              (response ? response.message : "Respons kosong atau tidak valid.")
          );
          console.error(
            "Data fetched (not found or invalid response):",
            response
          );
        }
      },
      error: function (xhr, status, error) {
        console.error(
          "Error fetching hutang data (AJAX ERROR):",
          error,
          "Response Text:",
          xhr.responseText
        );
        try {
          const response = JSON.parse(xhr.responseText);
          console.log("Respons fetch_hutang (parsed from ERROR):", response);
          if (response && response.found) {
            const data = response.data;
            $("#edit_hutang_id").val(data.id);
            $("#edit_hutang_pegawai_id").val(data.pegawai_id);
            $("#edit_hutang_jumlah").val(data.jumlah);
            $("#edit_hutang_keterangan").val(data.keterangan);
            $("#edit_hutang_tanggal_hutang").val(data.tanggal_hutang);
            $("#edit_hutang_status").val(data.status);

            if (data.status === "lunas") {
              $("#edit_hutang_tanggal_lunas_group").show();
              $("#edit_hutang_tanggal_lunas").val(data.tanggal_lunas);
            } else {
              $("#edit_hutang_tanggal_lunas_group").hide();
              $("#edit_hutang_tanggal_lunas").val("");
            }
            openModal(editHutangModal);
          } else {
            alert(
              "Data hutang tidak ditemukan atau ada error dari server: " +
                (response
                  ? response.message
                  : "Respons kosong atau tidak valid.")
            );
          }
        } catch (e) {
          alert(
            "Terjadi kesalahan saat mengambil data hutang. Cek konsol browser untuk detail."
          );
          console.error(
            "Failed to parse JSON from error response (hutang):",
            e
          );
        }
      },
    });
  });

  // Logika menampilkan/menyembunyikan tanggal lunas saat status hutang berubah
  $("#edit_hutang_status").change(function () {
    if ($(this).val() === "lunas") {
      $("#edit_hutang_tanggal_lunas_group").show();
      // Isi dengan tanggal hari ini jika kosong
      if (!$("#edit_hutang_tanggal_lunas").val()) {
        const today = new Date().toISOString().split("T")[0];
        $("#edit_hutang_tanggal_lunas").val(today);
      }
    } else {
      $("#edit_hutang_tanggal_lunas_group").hide();
      $("#edit_hutang_tanggal_lunas").val("");
    }
  });

  // --- Logika Hapus Hutang ---
  let hutangToDeleteId;
  $(document).on("click", ".delete-hutang-btn", function () {
    hutangToDeleteId = $(this).data("id");
    openModal(deleteHutangModal);
  });
  $("#confirmDeleteHutang").click(function () {
    if (hutangToDeleteId) {
      window.location.href =
        "../main/index-kepala-keuangan.php?hapus_hutang=" + hutangToDeleteId;
    }
  });
  $("#cancelDeleteHutang").click(function () {
    closeModal(deleteHutangModal);
    hutangToDeleteId = null;
  });

  // --- Logika Notifikasi Hutang (Pop-up) ---
  const closeHutangReminderModalBtn = document.getElementById(
    "closeHutangReminderModal"
  );
  const understoodHutangReminderBtn = document.getElementById(
    "understoodHutangReminder"
  );

  // `hasReminders` diharapkan di-inject oleh PHP di HTML (seperti yang ditunjukkan di ../main/index-kepala-keuangan.php di atas)
  // Misalnya: <script>var hasReminders = <?php echo !empty($reminders_hutang) ? 'true' : 'false'; ?>;</script>

  // Tampilkan modal secara otomatis jika ada reminder
  // Pastikan `hasReminders` didefinisikan secara global sebelum data-keuangan.js dimuat
  if (typeof hasReminders !== "undefined" && hasReminders) {
    setTimeout(function () {
      if (hutangReminderModal) {
        openModal(hutangReminderModal);
      }
    }, 500);
  }

  // Tutup modal ketika tombol 'x' diklik
  if (closeHutangReminderModalBtn) {
    closeHutangReminderModalBtn.onclick = function () {
      closeModal(hutangReminderModal);
    };
  }

  // Tutup modal ketika tombol 'Mengerti' diklik
  if (understoodHutangReminderBtn) {
    understoodHutangReminderBtn.onclick = function () {
      closeModal(hutangReminderModal);
    };
  }

  // === Logika untuk tombol "Kelola Hutang" di dalam modal notifikasi ===
  $(document).on("click", "#hutangReminderModal .edit-hutang-btn", function () {
    const hutangId = $(this).data("id");
    closeModal(hutangReminderModal); // Tutup modal reminder dulu

    // Panggil fungsi editHutang untuk membuka modal edit hutang
    // Gunakan setTimeout untuk memastikan modal reminder benar-benar tertutup
    setTimeout(() => {
      // Karena kita memanggil editHutang dari sini, kita perlu memastikan
      // bahwa fungsi ini bisa diakses, jadi membuatnya global (window.editHutang)
      // atau memanggil langsung logika AJAX-nya seperti di atas.
      // Untuk kesederhanaan, saya akan langsung panggil logic AJAX-nya
      $.ajax({
        url: "../main/index-kepala-keuangan.php",
        method: "GET",
        data: { id: hutangId, action: "fetch_hutang" },
        dataType: "json",
        success: function (response) {
          if (response && response.found) {
            const data = response.data;
            $("#edit_hutang_id").val(data.id);
            $("#edit_hutang_pegawai_id").val(data.pegawai_id);
            $("#edit_hutang_jumlah").val(data.jumlah);
            $("#edit_hutang_keterangan").val(data.keterangan);
            $("#edit_hutang_tanggal_hutang").val(data.tanggal_hutang);
            $("#edit_hutang_status").val(data.status);

            if (data.status === "lunas") {
              $("#edit_hutang_tanggal_lunas_group").show();
              $("#edit_hutang_tanggal_lunas").val(data.tanggal_lunas);
            } else {
              $("#edit_hutang_tanggal_lunas_group").hide();
              $("#edit_hutang_tanggal_lunas").val("");
            }
            openModal(editHutangModal);
          } else {
            alert(
              "Data hutang tidak ditemukan atau ada error dari server: " +
                (response ? response.message : "")
            );
          }
        },
        error: function (xhr, status, error) {
          console.error(
            "Error fetching hutang data from reminder:",
            error,
            xhr.responseText
          );
          alert("Terjadi kesalahan saat mengambil data hutang dari pengingat.");
        },
      });
    }, 100); // Penundaan singkat
  });

  // NEW FUNCTIONS for "Edit Status" modal (Jika digunakan di ../main/index-kepala-keuangan.php)
  // Pastikan modalEditStatus HTML ada di ../main/index-kepala-keuangan.php jika Anda menggunakan ini
  window.editStatus = function (id, currentStatus) {
    if (modalEditStatus) {
      document.getElementById("editStatusId").value = id;
      document.getElementById("selectStatus").value = currentStatus;
      openModal(modalEditStatus); // Gunakan fungsi openModal generik
    } else {
      console.warn(
        "Modal 'modalEditStatus' not found. Ensure it's in ../main/index-kepala-keuangan.php HTML."
      );
    }
  };

  window.closeStatusModal = function () {
    closeModal(modalEditStatus); // Gunakan fungsi closeModal generik
  };

  // --- Fungsionalitas Paginasi, Pencarian, Filter ---
  function setupTable(
    tableId,
    searchInputId,
    filterSelectId,
    filterColIndex,
    prevBtnId,
    nextBtnId,
    pageInfoId
  ) {
    const table = document.getElementById(tableId);
    if (!table) return;

    // Kloning baris untuk menghindari modifikasi DOM langsung saat filtering/pagination
    const rows = Array.from(table.tBodies[0].rows);

    const rowsPerPage = 10;
    let currentPage = 1;

    const searchInput = document.getElementById(searchInputId);
    const filterSelect = document.getElementById(filterSelectId);
    const prevBtn = document.getElementById(prevBtnId);
    const nextBtn = document.getElementById(nextBtnId);
    const pageInfo = document.getElementById(pageInfoId);

    function displayRows() {
      let filteredRows = rows.filter((row) => {
        // Pastikan baris bukan pesan "Tidak ada data"
        return !row.classList.contains("no-results-row");
      });

      if (searchInput) {
        const searchTerm = searchInput.value.toLowerCase();
        filteredRows = filteredRows.filter((row) =>
          row.textContent.toLowerCase().includes(searchTerm)
        );
      }

      if (filterSelect && filterColIndex !== undefined) {
        const filterValue = filterSelect.value.toLowerCase();
        if (filterValue !== "all") {
          filteredRows = filteredRows.filter((row) => {
            const cell = row.cells[filterColIndex];
            return cell && cell.textContent.toLowerCase().includes(filterValue);
          });
        }
      }

      const totalPages = Math.ceil(filteredRows.length / rowsPerPage);

      // Sesuaikan halaman saat ini jika di luar batas setelah filtering
      if (currentPage > totalPages && totalPages > 0) {
        currentPage = totalPages;
      } else if (totalPages === 0) {
        currentPage = 0; // Tidak ada halaman jika tidak ada baris
      } else if (currentPage === 0 && totalPages > 0) {
        currentPage = 1; // Jika entah bagaimana 0, atur ke 1 jika ada halaman
      }

      const startIndex = (currentPage - 1) * rowsPerPage;
      const endIndex = startIndex + rowsPerPage;
      const paginatedRows = filteredRows.slice(startIndex, endIndex);

      table.tBodies[0].innerHTML = ""; // Hapus isi tabel saat ini
      if (paginatedRows.length > 0) {
        paginatedRows.forEach((row) => table.tBodies[0].appendChild(row));
      } else {
        // Tambahkan pesan "Tidak ada hasil" jika tidak ada baris yang ditampilkan
        const noResultsRow = document.createElement("tr");
        noResultsRow.classList.add("no-results-row"); // Tambahkan kelas untuk identifikasi
        const noResultsCell = document.createElement("td");
        noResultsCell.colSpan = table.tHead.rows[0].cells.length; // Merentangkan semua kolom
        noResultsCell.textContent = "Tidak ada data yang ditemukan.";
        noResultsCell.style.textAlign = "center";
        noResultsRow.appendChild(noResultsCell);
        table.tBodies[0].appendChild(noResultsRow);
      }

      if (pageInfo) {
        pageInfo.textContent = `Page ${currentPage || 0} of ${totalPages || 0}`;
      }

      if (prevBtn) prevBtn.disabled = currentPage <= 1;
      if (nextBtn)
        nextBtn.disabled = currentPage >= totalPages || totalPages === 0;
    }

    if (prevBtn) {
      prevBtn.onclick = () => {
        if (currentPage > 1) {
          currentPage--;
          displayRows();
        }
      };
    }

    if (nextBtn) {
      nextBtn.onclick = () => {
        // Total halaman dihitung ulang di displayRows(), jadi cukup tingkatkan currentPage
        currentPage++;
        displayRows();
      };
    }

    if (searchInput) {
      searchInput.onkeyup = () => {
        currentPage = 1; // Reset ke halaman pertama saat mencari
        displayRows();
      };
    }

    if (filterSelect) {
      filterSelect.onchange = () => {
        currentPage = 1; // Reset ke halaman pertama saat filter berubah
        displayRows();
      };
    }

    // Tampilan awal dan tampilan ulang saat data baru tersedia
    displayRows();
  }

  // Inisialisasi untuk tabel Pengeluaran
  // Pastikan `filterColIndex` sesuai dengan indeks kolom Kategori di tabel Pengeluaran
  // ID: 0, Tanggal: 1, Kategori: 2, Jumlah: 3, Keterangan: 4, Metode Pembayaran: 5, Aksi: 6
  setupTable(
    "tablePengeluaran",
    "searchInputPengeluaran",
    "filterKategoriPengeluaran",
    2,
    "prevPagePengeluaran",
    "nextPagePengeluaran",
    "pageInfoPengeluaran"
  );

  // Inisialisasi untuk tabel Hutang Pegawai
  // Pastikan `filterColIndex` sesuai dengan indeks kolom Status di tabel Hutang Pegawai
  // ID: 0, Pegawai: 1, Jumlah: 2, Keterangan: 3, Tanggal Hutang: 4, Tanggal Lunas: 5, Status: 6, Aksi: 7
  setupTable(
    "tableHutang",
    "searchInputHutang",
    "filterStatusHutang",
    6,
    "prevPageHutang",
    "nextPageHutang",
    "pageInfoHutang"
  );
}); // Akhir DOMContentLoaded
