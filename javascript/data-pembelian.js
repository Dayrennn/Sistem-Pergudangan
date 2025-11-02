// Ambil elemen
const modal = document.getElementById("modalPermintaan");
const userSelect = document.getElementById("userSelect");
const jabatanInput = document.getElementById("jabatan");

// Fungsi buka dan tutup modal
function openModal() {
  document.getElementById("modalPermintaan").style.display = "block";
}
function closeModal() {
  document.getElementById("modalPermintaan").style.display = "none";
}

// Update jabatan saat user dipilih
userSelect.addEventListener("change", function () {
  const role = this.options[this.selectedIndex].getAttribute("data-role");
  jabatanInput.value = role || "";
});

// Tutup modal saat klik di luar modal-content
window.onclick = function (event) {
  if (event.target == modal) {
    closeModal();
  }
};
