// Modal untuk Produk Jadi
var produkModal = document.getElementById("produkModal");
var produkBtn = document.getElementById("openModalProdukBtn");
var produkSpan = document.getElementsByClassName("close")[0]; // pastikan ini untuk produk jadi

produkBtn.onclick = function() {
    produkModal.style.display = "block";
}

produkSpan.onclick = function() {
    produkModal.style.display = "none";
}

window.onclick = function(event) {
    if (event.target == produkModal) {
        produkModal.style.display = "none";
    }
}

// Modal untuk Tambah Barang
var addItemModal = document.getElementById("addItemModal");
var addItemBtn = document.getElementById("openModalBtn");
var addItemSpan = document.getElementsByClassName("close")[1]; // pastikan ini untuk tambah barang

addItemBtn.onclick = function() {
    addItemModal.style.display = "block";
}

addItemSpan.onclick = function() {
    addItemModal.style.display = "none";
}

window.onclick = function(event) {
    if (event.target == addItemModal) {
        addItemModal.style.display = "none";
    }
}
