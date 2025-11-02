let currentImageElement = null;

function uploadImage(element) {
  currentImageElement = element;
  document.getElementById("imageModal").style.display = "block";
}

function closeModal() {
  document.getElementById("imageModal").style.display = "none";
  currentImageElement = null;
}

function previewImage(input) {
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = function (e) {
      document.getElementById(
        "imagePreview"
      ).innerHTML = `<img src="${e.target.result}" style="max-width: 100%; max-height: 200px; border-radius: 8px;">`;
    };
    reader.readAsDataURL(input.files[0]);
  }
}

function saveImage() {
  const input = document.querySelector(".file-input");
  if (input.files && input.files[0] && currentImageElement) {
    const reader = new FileReader();
    reader.onload = function (e) {
      currentImageElement.innerHTML = `<img src="${e.target.result}">`;
      currentImageElement.classList.remove("placeholder");
    };
    reader.readAsDataURL(input.files[0]);
    closeModal();
  }
}

function addNewEvent() {
  const eventsGrid = document.getElementById("eventsGrid");
  const newEventCard = document.createElement("div");
  newEventCard.className = "event-card";

  newEventCard.innerHTML = `
                <div class="event-image placeholder" onclick="uploadImage(this)">
                    📝
                </div>
                <div class="event-content">
                    <div class="event-date">Tanggal Baru</div>
                    <h3 class="event-title" contenteditable="true">Klik untuk edit judul event...</h3>
                    
                    <div class="speakers-mini">
                        <div class="speaker-avatar">?</div>
                    </div>
                    
                    <div class="event-details">
                        <p contenteditable="true"><strong>Mulai:</strong> Klik untuk edit</p>
                        <p contenteditable="true"><strong>Selesai:</strong> Klik untuk edit</p>
                    </div>
                    
                    <button class="detail-btn">
                        👁️ DETAIL
                    </button>
                </div>
            `;

  eventsGrid.appendChild(newEventCard);

  // Add animation
  newEventCard.style.opacity = "0";
  newEventCard.style.transform = "translateY(20px)";
  setTimeout(() => {
    newEventCard.style.transition = "all 0.5s ease";
    newEventCard.style.opacity = "1";
    newEventCard.style.transform = "translateY(0)";
  }, 100);
}

// Add click handlers for detail buttons
document.addEventListener("click", function (e) {
  if (e.target.classList.contains("detail-btn")) {
    const button = e.target;
    const originalText = button.innerHTML;
    button.innerHTML = '<div class="loading"></div> Loading...';

    setTimeout(() => {
      button.innerHTML = originalText;
      alert("Detail event akan ditampilkan di sini!");
    }, 1000);
  }
});

// Close modal when clicking outside
window.addEventListener("click", function (e) {
  const modal = document.getElementById("imageModal");
  if (e.target === modal) {
    closeModal();
  }
});

// Add some interactive effects
document.querySelectorAll(".event-card").forEach((card) => {
  card.addEventListener("mouseenter", function () {
    this.style.transform = "translateY(-8px) scale(1.02)";
  });

  card.addEventListener("mouseleave", function () {
    this.style.transform = "translateY(0) scale(1)";
  });
});
