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
    const quackCarouselElement = document.getElementById('quackCarousel'); // Spara i variabel

    // kontrollera att modalen och karusellen finns
    if (imageModal && quackCarouselElement) {
        const carousel = new bootstrap.Carousel(quackCarouselElement);
        
        // lyssna på klick i galleriet för att byta bild i carouselen
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
    const button = e.target.closest('.like-btn');
    if (!button) return;

    e.preventDefault();
    const quackId = button.getAttribute('data-quack-id');

    fetch('actions/like_handler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `quack_id=${quackId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success' || data.success) {
            // HITTA ALLA LIKES PÅ SIDAN MED SAMMA ID
            const allLikeButtons = document.querySelectorAll(`.like-btn[data-quack-id="${quackId}"]`);
            
            allLikeButtons.forEach(btn => {
                const countSpan = btn.querySelector('.like-count');
                countSpan.innerText = data.new_count;
                btn.classList.toggle('is-liked', data.is_liked || data.liked);
            });
        }
    })
    .catch(err => console.error('Like error:', err));
});

// Requacks 
document.addEventListener('click', function(e) {
    const btn = e.target.closest('.requack-btn');
    if (!btn) return;

    e.preventDefault();
    const quackId = btn.dataset.quackId;
    const quackCard = btn.closest('.quack-card');

    const formData = new FormData();
    formData.append('quack_id', quackId);

    fetch('actions/requack_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const isRequackNote = quackCard.querySelector('.text-muted.small.fw-bold');
            
            if (data.status === 'removed' && isRequackNote) {
                quackCard.style.opacity = '0';
                quackCard.style.transform = 'translateX(20px)';
                quackCard.style.transition = 'all 0.3s ease';
                setTimeout(() => { quackCard.remove(); }, 300);
            } else {
                // UPPDATERA ALLA REQUACK-KNAPPAR PÅ SIDAN MED SAMMA ID
                const allRequackButtons = document.querySelectorAll(`.requack-btn[data-quack-id="${quackId}"]`);
                
                allRequackButtons.forEach(rBtn => {
                    const countEl = rBtn.querySelector('.requack-count');
                    countEl.textContent = data.newCount;
                    rBtn.classList.toggle('is-requacked', data.status === 'added');
                });
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

    // Körs på messages.php
    setupEmojiPicker('chat-emoji-trigger', 'chat-picker-container', 'chat-input-field', 'chat-emoji-picker');
});
