document.addEventListener('DOMContentLoaded', function() {
    const barangKeluarModal = document.getElementById('barangKeluarModal');
    const openModalKeluarBtn = document.getElementById('openModalKeluarBtn');
    const closeModalKeluarBtn = document.getElementById('closeModalKeluarBtn');
    const closeIconKeluar = barangKeluarModal ? barangKeluarModal.querySelector('.close') : null;

    const openBarangKeluarModal = () => {
        barangKeluarModal.style.display = "block";
        barangKeluarModal.classList.remove('fade-out');
        barangKeluarModal.querySelector('.modal-content').classList.remove('slide-out');
    };

    const closeBarangKeluarModal = () => {
        barangKeluarModal.classList.add('fade-out');
        const modalContent = barangKeluarModal.querySelector('.modal-content');
        if (modalContent) {
            modalContent.classList.add('slide-out');
        }
        setTimeout(function() {
            barangKeluarModal.style.display = "none";
        }, 300);
    };

    if (openModalKeluarBtn) {
        openModalKeluarBtn.addEventListener('click', openBarangKeluarModal);
    }

    if (closeModalKeluarBtn) {
        closeModalKeluarBtn.addEventListener('click', closeBarangKeluarModal);
    }

    if (closeIconKeluar) {
        closeIconKeluar.addEventListener('click', closeBarangKeluarModal);
    }

    window.addEventListener('click', function(event) {
        if (event.target == barangKeluarModal) {
            closeBarangKeluarModal();
        }
    });
});