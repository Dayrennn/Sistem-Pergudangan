document.addEventListener('DOMContentLoaded', function() {
    const stokModal = document.getElementById('stokModal');
    const closeButton = document.querySelector('.modal-close');
    const closeFooterButton = document.querySelector('.modal-footer button');

    // Fungsi untuk menutup modal
    function closeModal() {
        if (stokModal) {
            stokModal.style.display = 'none';
        }
    }

    // Tampilkan modal saat halaman dimuat jika ada notifikasi
    if (typeof adaNotifDariPHP !== 'undefined' && adaNotifDariPHP && stokModal) {
        stokModal.style.display = 'block';
    }

    // Tambahkan event listener untuk tombol silang (X)
    if (closeButton) {
        closeButton.addEventListener('click', closeModal);
    }

    // Tambahkan event listener untuk tombol tutup di footer
    if (closeFooterButton) {
        closeFooterButton.addEventListener('click', closeModal);
    }
});