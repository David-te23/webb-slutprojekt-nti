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
    const quackCard = btn.closest('.quack-card'); // Hitta hela inlägget

    const formData = new FormData();
    formData.append('quack_id', quackId);

    fetch('actions/requack_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            // 1. Om vi är på en profil och det var en requack som togs bort
            // Vi kollar om "X requacked"-texten finns i kortet
            const isRequackNote = quackCard.querySelector('.text-muted.small.fw-bold');
            
            if (data.status === 'removed' && isRequackNote) {
                // Ta bort hela kortet med en snygg animation
                quackCard.style.opacity = '0';
                quackCard.style.transform = 'translateX(20px)';
                quackCard.style.transition = 'all 0.3s ease';
                
                setTimeout(() => {
                    quackCard.remove();
                }, 300);
            } else {
                // Annars, bara uppdatera siffran och färgen (som på index)
                countEl.textContent = data.newCount;
                btn.classList.toggle('is-requacked', data.status === 'added');
            }
        }
    });
});

document.addEventListener('DOMContentLoaded', () => {
    function setupEmojiPicker(triggerId, containerId, textareaId, pickerId) {
        const trigger = document.getElementById(triggerId);
        const container = document.getElementById(containerId);
        const textarea = document.getElementById(textareaId);
        const picker = document.getElementById(pickerId);

        // Om något av elementen saknas
        if (!trigger || !container || !textarea || !picker) return;

        trigger.addEventListener('click', (e) => {
            e.preventDefault();
            const isVisible = container.style.display === 'block';
            container.style.display = isVisible ? 'none' : 'block';
        });

        picker.addEventListener('emoji-click', event => {
            textarea.value += event.detail.unicode;
            container.style.display = 'none';
            textarea.focus();
        });

        document.addEventListener('click', (e) => {
            if (!trigger.contains(e.target) && !container.contains(e.target)) {
                container.style.display = 'none';
            }
        });
    }

    // Körs på index.php
    setupEmojiPicker('emoji-trigger', 'picker-container', 'quack-textarea', 'quack-picker');

    // Körs på quack.php 
    setupEmojiPicker('reply-emoji-trigger', 'reply-picker-container', 'reply-textarea', 'reply-picker');
});
