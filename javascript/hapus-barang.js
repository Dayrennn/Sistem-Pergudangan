// State management
const deleteState = {
  itemId: null,
  itemName: null,
  isProcessing: false,
};

// DOM Elements cache
const domElements = {
  confirmModal: document.getElementById("confirmModal"),
  modalContent: document.querySelector("#confirmModal .modal-content"),
  itemDetails: document.getElementById("itemDetails"),
  confirmDeleteBtn: document.getElementById("confirmDeleteBtn"),
  cancelBtn: document.getElementById("cancelBtn"),
  closeBtn: document.querySelector("#confirmModal .close"),
};

// Animation classes
const animationClasses = {
  show: "show",
  hide: "hide",
};

document.addEventListener("DOMContentLoaded", function () {
  initializeModal();
  setupEventListeners();
});

function initializeModal() {
  // Ensure modal is hidden initially
  domElements.confirmModal.style.display = "none";
}

function setupEventListeners() {
  // Delete buttons
  document.querySelectorAll("[data-delete-item]").forEach((button) => {
    button.addEventListener("click", handleDeleteButtonClick);
  });

  // Modal controls
  domElements.confirmDeleteBtn.addEventListener("click", handleDeleteConfirm);
  domElements.cancelBtn.addEventListener("click", closeModal);
  domElements.closeBtn.addEventListener("click", closeModal);

  // Close modal when clicking outside
  window.addEventListener("click", (event) => {
    if (event.target === domElements.confirmModal) {
      closeModal();
    }
  });
}

function handleDeleteButtonClick(event) {
  const button = event.currentTarget;
  const itemId = button.getAttribute("data-delete-item");
  const itemName = button.getAttribute("data-item-name");

  openConfirmModal(itemId, itemName);
}

// Pasang event listener saat DOM siap
document.addEventListener("DOMContentLoaded", function () {
  document.querySelectorAll("[data-delete-item]").forEach((button) => {
    button.addEventListener("click", handleDeleteButtonClick);
  });
});

function openConfirmModal(id, name) {
  // Update state
  deleteState.itemId = id;
  deleteState.itemName = name;

  // Update modal content
  domElements.itemDetails.textContent = `Barang: ${name} (ID: ${id})`; // Tampilkan nama barang

  // Reset animation state
  domElements.confirmModal.classList.remove(animationClasses.hide);
  domElements.modalContent.classList.remove(animationClasses.hide);

  // Show modal
  domElements.confirmModal.style.display = "block";

  // Force reflow to enable animation
  void domElements.confirmModal.offsetWidth;

  // Start show animation
  domElements.confirmModal.classList.add(animationClasses.show);
  domElements.modalContent.classList.add(animationClasses.show);
}

function closeModal() {
  // Hide animation
  domElements.confirmModal.classList.remove(animationClasses.show);
  domElements.modalContent.classList.remove(animationClasses.show);

  // Add hide class for animation
  domElements.confirmModal.classList.add(animationClasses.hide);
  domElements.modalContent.classList.add(animationClasses.hide);

  // Wait for animation to complete before hiding
  setTimeout(() => {
    domElements.confirmModal.style.display = "none";
  }, 200);
}

async function handleDeleteConfirm() {
  if (!deleteState.itemId || deleteState.isProcessing) return;

  try {
    deleteState.isProcessing = true;
    setDeleteButtonState(true);

    const response = await sendDeleteRequest(deleteState.itemId);

    if (response.success) {
      showAlert("success", response.message || "Barang berhasil dihapus");
      setTimeout(() => window.location.reload(), 1000);
    } else {
      showAlert("error", response.message || "Gagal menghapus barang");
    }
  } catch (error) {
    console.error("Delete error:", error);
    showAlert("error", `Terjadi kesalahan: ${error.message}`);
  } finally {
    setDeleteButtonState(false);
    deleteState.isProcessing = false;
  }
}

function setDeleteButtonState(isLoading) {
  const btn = domElements.confirmDeleteBtn;
  if (isLoading) {
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menghapus...';
    btn.disabled = true;
  } else {
    btn.innerHTML = "Hapus";
    btn.disabled = false;
  }
}

async function sendDeleteRequest(itemId) {
  const response = await fetch(`hapus-barang.php?id=${itemId}`, {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
    },
    body: `id=${encodeURIComponent(itemId)}&confirm=true`,
  });

  if (!response.ok) {
    throw new Error(`HTTP error! status: ${response.status}`);
  }

  return await response.json();
}

function showAlert(type, message) {
  const alertDiv = document.createElement("div");
  alertDiv.className = `alert alert-${type}`;
  alertDiv.textContent = message;

  document.body.appendChild(alertDiv); // Atau targetkan elemen yang lebih spesifik

  setTimeout(() => {
    alertDiv.remove();
  }, 3000);
}
