document.addEventListener('DOMContentLoaded', function() {
    const chatHistory = document.getElementById('chatHistory');
    const conversationList = document.getElementById('conversationList');
    const messageForm = document.querySelector('.chat-input-container form');
    const messageInput = document.getElementById('chat-input-field');
    const fileInput = document.getElementById('chat-image-input');
    const previewContainer = document.getElementById('chat-img-preview');

    // CACHELOGIK: Sparar förra polling-svarets HTML för att undvika onödiga omritningar
    // om inga nya meddelanden tagits emot
    let lastHTML = ""; 
    let lastConvHTML = "";

    // --- INITIALISERING ---
    if (typeof setupEmojiPicker === "function") {
        setupEmojiPicker('chat-emoji-trigger', 'chat-picker-container', 'chat-input-field', 'chat-emoji-picker');
    }

    // Scroll-funktion med en kort timeout för att säkerställa att DOM:en är redo
    const scrollToBottom = (behavior = 'auto') => {
        if (chatHistory) {
            setTimeout(() => {
                chatHistory.scrollTo({ 
                    top: chatHistory.scrollHeight, 
                    behavior: behavior 
                });
            }, 50); // En liten delay löser 99% av alla scroll-problem i JS
        }
    };

    // --- FILHANTERING & PREVIEW ---
    if (fileInput && previewContainer) {
        fileInput.addEventListener('change', function() {
            previewContainer.innerHTML = ''; 
            if (this.files && this.files[0]) {
                const file = this.files[0];
                const reader = new FileReader();
                const isVideo = file.type.startsWith('video/');

                // Skapar en lokal Base64-URL så användaren kan se bilden/videon direkt
                // innan den ens laddats upp till webbservern
                reader.onload = function(event) {
                    const div = document.createElement('div');
                    div.className = 'position-relative d-inline-block mt-2 mb-2';
                    
                    let mediaHtml = isVideo 
                        ? `<video src="${event.target.result}" class="rounded border shadow-sm chat-preview-img" muted></video>`
                        : `<img src="${event.target.result}" class="rounded border shadow-sm chat-preview-img">`;

                    div.innerHTML = `
                        ${mediaHtml}
                        <button type="button" class="btn-close preview-remove-btn position-absolute top-0 end-0 bg-white rounded-circle p-1 m-1" 
                                aria-label="Remove"></button>
                    `;

                    // Gör det möjligt för användaren att ångra och rensa den valda filen
                    div.querySelector('button').onclick = () => {
                        fileInput.value = ''; 
                        div.remove();
                    };
                    previewContainer.appendChild(div);
                }
                reader.readAsDataURL(file);
            }
        });
    }

    // --- AJAX (SKICKA MEDDELANDE) ---
    if (messageForm) {
        messageForm.addEventListener('submit', function(e) {
            e.preventDefault(); // Stoppar traditionell formulärinsändning
            if (!messageInput.value.trim() && (!fileInput.files || !fileInput.files[0])) return;

            const formData = new FormData(messageForm);

            // Skickar data asynkront (AJAX) till backend via Fetch API
            fetch(messageForm.getAttribute('action'), {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                     // Återställer inmatningsfälten vid lyckat svar
                    messageInput.value = '';
                    fileInput.value = '';
                    previewContainer.innerHTML = '';
                    refreshMessages(true); // Tvinga scroll vid eget skickat meddelande
                    refreshConversations();
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
                // Skriv bara ut om HTML-strukturen faktiskt har ändrats (sparar prestanda)
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
                    // SMART SCROLL-SPÄRR: Kollar om användaren står högst upp och läser gamla meddelanden.
                    // Om användaren INTE är i botten (100px marginal), tvingar vi inte ner vyn så att läsningen störs.
                    const isAtBottom = chatHistory.scrollTop + chatHistory.clientHeight >= chatHistory.scrollHeight - 100;
                    const firstLoad = lastHTML === "";

                    chatHistory.innerHTML = html;
                    lastHTML = html;

                    // Automatisk scroll sker endast vid första laddningen, eget skickat meddelande, eller om man redan var i botten
                    if (firstLoad || forceScroll || isAtBottom) {
                        scrollToBottom(firstLoad ? 'auto' : 'smooth');
                    }
                    refreshConversations();
                }
            })
            .catch(err => console.error("Polling-fel:", err));
        }
    };

    // Starta polling-loopen
     // som hämtar nya chattmeddelanden var 3:e sekund, och inkorgen var 5:e sekund.
    if (window.location.search.includes('user_id=')) {
        refreshMessages(true); // Initial laddning med tvingad scroll
        setInterval(() => refreshMessages(false), 3000);
    }
    
    setInterval(refreshConversations, 5000);

    // Lyssna på bildladdning - om en bild dyker upp i chatten, scrolla ner igen
    if (chatHistory) {
        chatHistory.addEventListener('load', (e) => {
            if (e.target.tagName === 'IMG' || e.target.tagName === 'VIDEO') {
                scrollToBottom();
            }
        }, true);
    }

    // --- MODAL SÖK ---
    const searchInput = document.getElementById('userSearchInput');
    const userList = document.getElementById('userList');
    if (searchInput && userList) {
        const items = Array.from(userList.getElementsByClassName('user-search-item'));
        searchInput.addEventListener('input', () => {
            const filter = searchInput.value.toLowerCase();
            // Växlar Bootstrap-klasser dynamiskt istället för DOM-raderingar
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
            // Fångar upp klick på skickade bilder via Event Delegation
            if (e.target.classList.contains('chat-img-msg')) {
                const imgSrc = e.target.getAttribute('src');
                const modalImg = document.getElementById('modalImage');
                if (modalImg) {
                    modalImg.src = imgSrc; // Flyttar över bildkällan till den tomma placeholder-bilden i modalen
                    const imageModal = new bootstrap.Modal(document.getElementById('imageModal'));
                    imageModal.show();
                }
            }
        });
    }
});
