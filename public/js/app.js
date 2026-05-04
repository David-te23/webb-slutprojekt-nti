document.addEventListener('DOMContentLoaded', () => {
    const searchBtn = document.getElementById('mobileSearchBtn');
    const closeSearchBtn = document.getElementById('closeSearchBtn');
    const header = document.getElementById('siteheader');
    const mobileInput = document.getElementById('mobileInput');

    searchBtn.addEventListener('click', () => {
        header.classList.add ('search-active')
        mobileInput.focus();
    });

    closeSearchBtn.addEventListener('click', () => {
        header.classList.remove('search-active');
    });
});

document.addEventListener('DOMContentLoaded', function() {
    const imageModal = document.getElementById('imageModal');
    if (imageModal) {
        const carousel = new bootstrap.Carousel(document.getElementById('quackCarousel'));
        
        // Lyssna på klick i galleriet för att byta bild i carouselen
        document.querySelectorAll('[data-bs-target="#imageModal"]').forEach(item => {
            item.addEventListener('click', function() {
                const slideTo = parseInt(this.getAttribute('data-bs-slide-to'));
                carousel.to(slideTo);
            });
        });
    }
});


// live uppdatering av like knapp
document.addEventListener('click', function(e) {
    // Hitta om klicket skedde på en like-knapp
    const button = e.target.closest('.like-btn');
    
    if (!button) return; // Om en like-knapp inte klickades, gör inget

    e.preventDefault();
    const quackId = button.getAttribute('data-quack-id');
    const countSpan = button.querySelector('.like-count');

    fetch('actions/like_handler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `quack_id=${quackId}`
    })
    .then(response => response.json())
    .then(data => {
        
        if (data.status === 'success' || data.success) {
            countSpan.innerText = data.new_count;
            button.classList.toggle('is-liked', data.is_liked || data.liked);
        }
    })
    .catch(err => console.error('Like error:', err));
    });

//Requacks
document.addEventListener('click', function(e) {
    const btn = e.target.closest('.requack-btn');
    if (!btn) return;

    e.preventDefault();
    const quackId = btn.dataset.quackId;
    const countEl = btn.querySelector('.requack-count');

    const formData = new FormData();
    formData.append('quack_id', quackId);

    fetch('actions/requack_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            countEl.textContent = data.newCount;
            // Toggla en klass för att färga ikonen grön (Twitter-style)
            btn.classList.toggle('is-requacked', data.status === 'added');
        }
    });
});
