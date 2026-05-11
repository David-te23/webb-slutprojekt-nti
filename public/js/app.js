
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


// --- DYNAMISK BILD & VIDEO MODAL ---
document.addEventListener('DOMContentLoaded', function() {
    const imageModal = document.getElementById('imageModal');
    if (!imageModal) return;

    const carouselInner = imageModal.querySelector('.carousel-inner');
    const quackCarouselElement = document.getElementById('quackCarousel');

    imageModal.addEventListener('show.bs.modal', function (event) {
        const trigger = event.relatedTarget; // Elementet man klickade på
        const quackCard = trigger.closest('.quack-card');
        const allMedia = quackCard.querySelectorAll('.gallery-item img, .gallery-item video');
        const startSlide = parseInt(trigger.getAttribute('data-bs-slide-to'));

        // Töm karusellen och bygg upp den på nytt
        carouselInner.innerHTML = '';

        allMedia.forEach((media, index) => {
            const isActive = index === startSlide;
            const isVideo = media.tagName === 'VIDEO';
            
            const carouselItem = document.createElement('div');
            carouselItem.className = `carousel-item ${isActive ? 'active' : ''}`;
            
            if (isVideo) {
                // Skapa video för modalen (med kontroller)
                carouselItem.innerHTML = `
                    <video src="${media.src}" controls class="d-block w-100 rounded" style="max-height: 80vh;"></video>
                `;
            } else {
                // Skapa bild för modalen
                carouselItem.innerHTML = `
                    <img src="${media.src}" class="d-block w-100 rounded" style="max-height: 80vh; object-fit: contain;" alt="Quack media">
                `;
            }
            carouselInner.appendChild(carouselItem);
        });

        // Initiera karusellen på rätt slide
        const carousel = new bootstrap.Carousel(quackCarouselElement);
        carousel.to(startSlide);
    });
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


// --- KOMMENTAR-HANTERARE (AJAX) ---
document.addEventListener('submit', function(e) {
    // Kollar om formuläret som skickas är kommentarsformuläret
    const form = e.target.closest('#commentForm');
    if (!form) return;

    e.preventDefault(); // Stoppa sidladdning
    
    const formData = new FormData(form);
    const textarea = form.querySelector('textarea');
    const submitBtn = form.querySelector('button[type="submit"]');

    if (submitBtn) submitBtn.disabled = true;

    fetch('actions/process_comment.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (response.ok) {
            if (textarea) textarea.value = '';
            // Använd replace för att ladda om utan att förstöra webbläsarens historik
            window.location.replace(window.location.href);
        } else {
            alert("Something went wrong.");
            if (submitBtn) submitBtn.disabled = false;
        }
    })
    .catch(err => {
        console.error('Comment error:', err);
        if (submitBtn) submitBtn.disabled = false;
    });
});


// --- Ta bort kommentar AJAX ---
let commentIdToDelete = null;
let commentElementToRemove = null;

document.addEventListener('click', function(e) {
    const btn = e.target.closest('.delete-comment-btn');
    if (!btn) return;

    // Spara info
    commentIdToDelete = btn.dataset.commentId;
    commentElementToRemove = btn.closest('.comment-card');

    // Öppna modalen (Bootstrap 5 sätt)
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteCommentModal'));
    deleteModal.show();
});

// Hantera själva raderingen när man trycker på "Delete" i modalen
document.getElementById('confirmDeleteCommentBtn')?.addEventListener('click', function() {
    if (!commentIdToDelete || !commentElementToRemove) return;

    const formData = new FormData();
    formData.append('comment_id', commentIdToDelete);

    fetch('actions/delete_comment.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            // Stäng modalen
            bootstrap.Modal.getInstance(document.getElementById('deleteCommentModal')).hide();
            
            // Animera bort kommentaren
            commentElementToRemove.style.opacity = '0';
            commentElementToRemove.style.transform = 'scale(0.95)';
            commentElementToRemove.style.transition = 'all 0.3s ease';
            
            setTimeout(() => {
                commentElementToRemove.remove();
                commentIdToDelete = null;
                commentElementToRemove = null;
            }, 300);
        }
    });
});


// --- Ta bort quack AJAX ---
let quackIdToDelete = null;
let quackElementToRemove = null;

document.addEventListener('DOMContentLoaded', () => {
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    const deleteModalElement = document.getElementById('deleteQuackModal');
    
    const bsDeleteModal = deleteModalElement ? new bootstrap.Modal(deleteModalElement) : null;

    // Lyssna på klick på papperskorgen i flödet
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.delete-quack-btn');
        if (!btn) return;

        // Spara ID och själva HTML-elementet (kortet)
        quackIdToDelete = btn.dataset.quackId;
        quackElementToRemove = btn.closest('.quack-card');

        // Öppna modalen
        if (bsDeleteModal) {
            bsDeleteModal.show();
        }
    });

    // Lyssna på klick på själva "Delete"-knappen INUTI modalen
    if (confirmDeleteBtn) {
        confirmDeleteBtn.addEventListener('click', function() {
            if (!quackIdToDelete || !quackElementToRemove) return;

            const formData = new FormData();
            formData.append('quack_id', quackIdToDelete);

            fetch('actions/delete_quack.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    // Stäng modalen
                    if (bsDeleteModal) bsDeleteModal.hide();
                    
                    // KOLLA OM VI ÄR PÅ ENSKILD INLÄGGSSIDA
                    if (window.location.pathname.includes('quack.php')) {
                        // Skicka användaren till startsidan
                        window.location.href = 'index.php';
                    } else {
                        // Om användaren är i flödet/profilen - animera bort kortet
                        quackElementToRemove.style.opacity = '0';
                        quackElementToRemove.style.transform = 'scale(0.9)';
                        quackElementToRemove.style.transition = 'all 0.3s ease';
                        
                        setTimeout(() => {
                            quackElementToRemove.remove();
                            // Nollställ variablerna
                            quackIdToDelete = null;
                            quackElementToRemove = null;
                        }, 300);
                    }
                } else {
                    alert("Error: " + (data.error || "Could not delete"));
                }
            })
            .catch(err => {
                console.error('Delete error:', err);
                alert("An error occurred on the server.");
            });
        });
    }
});

// --- INFO-TABS HANTERARE (footer länkar) --- 
document.addEventListener('DOMContentLoaded', function() {
    // Hämta 'tab' från URL:en (t.ex. ?tab=privacy)
    const urlParams = new URLSearchParams(window.location.search);
    const tabName = urlParams.get('tab');

    if (tabName) {
        // Hitta knappen som hör till den fliken
        const targetTab = document.querySelector('#' + tabName + '-tab');
        
        if (targetTab) {
            // Använd Bootstraps inbyggda funktion för att visa fliken
            const tabTrigger = new bootstrap.Tab(targetTab);
            tabTrigger.show();
        }
    }
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
