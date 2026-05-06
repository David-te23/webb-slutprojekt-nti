document.addEventListener('DOMContentLoaded', function() {
    const chatHistory = document.getElementById('chatHistory');
    const conversationList = document.getElementById('conversationList');
    const messageForm = document.querySelector('.chat-input-container form');
    const messageInput = document.getElementById('chat-input-field');
    const fileInput = document.getElementById('chat-image-input');
    const previewContainer = document.getElementById('chat-img-preview');
    let lastHTML = ""; 
    let lastConvHTML = "";

    // --- INITIALISERING ---
    if (typeof setupEmojiPicker === "function") {
        setupEmojiPicker('chat-emoji-trigger', 'chat-picker-container', 'chat-input-field', 'chat-emoji-picker');
    }

    const scrollToBottom = (behavior = 'auto') => {
        if (chatHistory) {
            chatHistory.scrollTo({ top: chatHistory.scrollHeight, behavior: behavior });
        }
    };
    scrollToBottom();

    // --- FILHANTERING & PREVIEW ---
    if (fileInput && previewContainer) {
        fileInput.addEventListener('change', function() {
            previewContainer.innerHTML = ''; 
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    const div = document.createElement('div');
                    div.className = 'position-relative d-inline-block mt-2 mb-2';
                    div.innerHTML = `
                        <img src="${event.target.result}" class="rounded border shadow-sm chat-preview-img">
                        <button type="button" class="btn-close preview-remove-btn position-absolute top-0 end-0 bg-white rounded-circle p-1 m-1" 
                                aria-label="Remove"></button>
                    `;
                    div.querySelector('button').onclick = () => {
                        fileInput.value = ''; 
                        div.remove();
                    };
                    previewContainer.appendChild(div);
                }
                reader.readAsDataURL(this.files[0]);
            }
        });
    }

    // --- AJAX (SKICKA MEDDELANDE) ---
    if (messageForm) {
        messageForm.addEventListener('submit', function(e) {
            e.preventDefault();
            if (!messageInput.value.trim() && (!fileInput.files || !fileInput.files[0])) return;

            const formData = new FormData(messageForm);
            fetch(messageForm.getAttribute('action'), {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    messageInput.value = '';
                    fileInput.value = '';
                    previewContainer.innerHTML = '';
                    refreshMessages(true); 
                    refreshConversations(); // Uppdatera listan direkt vid skickat meddelande
                }
            })
            .catch(err => console.error("Skicka-fel:", err));
        });
    }

    // --- POLLING KONVERSATIONER (VÄNSTER) ---
    const refreshConversations = () => {
        const params = new URLSearchParams(window.location.search);
        const userId = params.get('user_id') || '';

        if (conversationList) {
            fetch(`actions/get_conversations.php?user_id=${userId}`)
            .then(response => response.text())
            .then(html => {
                if (html !== lastConvHTML) {
                    conversationList.innerHTML = html;
                    lastConvHTML = html;
                }
            })
            .catch(err => console.error("Konversations-polling-fel:", err));
        }
    };

    // --- POLLING MEDDELANDEN (HÖGER) ---
    const refreshMessages = (forceScroll = false) => {
        const params = new URLSearchParams(window.location.search);
        const userId = params.get('user_id');

        if (userId && chatHistory) {
            fetch(`actions/get_messages.php?user_id=${userId}`)
            .then(response => response.text())
            .then(html => {
                if (html !== lastHTML) {
                    const isAtBottom = chatHistory.scrollTop + chatHistory.clientHeight >= chatHistory.scrollHeight - 50;
                    chatHistory.innerHTML = html;
                    lastHTML = html;
                    if (isAtBottom || forceScroll) {
                        requestAnimationFrame(() => scrollToBottom('smooth'));
                    }
                    refreshConversations(); // Om nya meddelanden kommit, uppdatera listan också
                }
            })
            .catch(err => console.error("Polling-fel:", err));
        }
    };

    // Intervaller för uppdatering
    if (window.location.search.includes('user_id=')) {
        setInterval(() => refreshMessages(false), 3000);
    }
    
    // Uppdatera listan till vänster oavsett om man har en chatt öppen eller ej
    setInterval(refreshConversations, 5000);

    // --- MODAL SÖK ---
    const searchInput = document.getElementById('userSearchInput');
    const userList = document.getElementById('userList');
    if (searchInput && userList) {
        const items = Array.from(userList.getElementsByClassName('user-search-item'));
        searchInput.addEventListener('input', () => {
            const filter = searchInput.value.toLowerCase();
            items.forEach(item => {
                const isVisible = item.textContent.toLowerCase().includes(filter);
                item.classList.toggle('d-none', !isVisible);
                item.classList.toggle('d-flex', isVisible);
            });
        });
    }

    // --- BILDVISNING MODAL ---
    if (chatHistory) {
        chatHistory.addEventListener('click', function(e) {
            if (e.target.classList.contains('chat-img-msg')) {
                const imgSrc = e.target.getAttribute('src');
                const modalImg = document.getElementById('modalImage');
                if (modalImg) {
                    modalImg.src = imgSrc;
                    const imageModal = new bootstrap.Modal(document.getElementById('imageModal'));
                    imageModal.show();
                }
            }
        });
    }
});
