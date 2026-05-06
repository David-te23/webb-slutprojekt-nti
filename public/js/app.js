
 // --- NOTIS-SYSTEMET ---
 function updateNotificationBadge() {
    const notifBadge = document.getElementById('notif-badge');
    const msgBadge = document.getElementById('msg-badge');
    
    if (!notifBadge && !msgBadge) return;

    fetch('actions/get_unread_count.php?t=' + Date.now())
        .then(response => response.json())
        .then(data => {
            // Hantera Notiser
            if (notifBadge) {
                if (data.unread_notifications > 0) {
                    notifBadge.innerText = data.unread_notifications;
                    notifBadge.classList.remove('d-none');
                } else {
                    notifBadge.classList.add('d-none');
                }
            }

            // Hantera Meddelanden
            if (msgBadge) {
                if (data.unread_messages > 0) {
                    msgBadge.innerText = data.unread_messages;
                    msgBadge.classList.remove('d-none');
                } else {
                    msgBadge.classList.add('d-none');
                }
            }
        })
        .catch(err => console.error('Badge-fel:', err));
}


// Starta notis-loopen med 10s interval
updateNotificationBadge();
setInterval(updateNotificationBadge, 10000);



 // --- NAVBAR & SÖK (Mobil) ---
document.addEventListener('DOMContentLoaded', () => {
    const searchBtn = document.getElementById('mobileSearchBtn');
    const closeSearchBtn = document.getElementById('closeSearchBtn');
    const header = document.getElementById('siteheader');
    const mobileInput = document.getElementById('mobileInput');

    if (searchBtn && header && mobileInput) {
        searchBtn.addEventListener('click', () => {
            header.classList.add('search-active');
            mobileInput.focus();
        });
        closeSearchBtn.addEventListener('click', () => {
            header.classList.remove('search-active');
        });
    }
});


 // --- BILD-MODAL & KARUSELL ---
document.addEventListener('DOMContentLoaded', function() {
    const imageModal = document.getElementById('imageModal');
    const quackCarouselElement = document.getElementById('quackCarousel');

    if (imageModal && quackCarouselElement) {
        const carousel = new bootstrap.Carousel(quackCarouselElement);
        document.querySelectorAll('[data-bs-target="#imageModal"]').forEach(item => {
            item.addEventListener('click', function() {
                const slideTo = parseInt(this.getAttribute('data-bs-slide-to'));
                carousel.to(slideTo);
            });
        });
    }
});


// --- LIKE-HANTERARE --- 
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

 
 // --- REQUACK-HANTERARE ---
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
                quackCard.style.transition = 'all 0.3s ease';
                setTimeout(() => { quackCard.remove(); }, 300);
            } else {
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


 // --- EMOJI-PICKERS ---
document.addEventListener('DOMContentLoaded', () => {
    function setupEmojiPicker(triggerId, containerId, textareaId, pickerId) {
        const trigger = document.getElementById(triggerId);
        const container = document.getElementById(containerId);
        const textarea = document.getElementById(textareaId);
        const picker = document.getElementById(pickerId);

        if (!trigger || !container || !textarea || !picker) return;

        trigger.addEventListener('click', (e) => {
            e.preventDefault();
            container.style.display = container.style.display === 'block' ? 'none' : 'block';
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

    setupEmojiPicker('emoji-trigger', 'picker-container', 'quack-textarea', 'quack-picker');
    setupEmojiPicker('reply-emoji-trigger', 'reply-picker-container', 'reply-textarea', 'reply-picker');
    setupEmojiPicker('chat-emoji-trigger', 'chat-picker-container', 'chat-input-field', 'chat-emoji-picker');
});
