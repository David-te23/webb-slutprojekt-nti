/**
 * --- ADMIN PANEL LOGIK ---
 */
document.addEventListener('DOMContentLoaded', () => {
    // Variabler för radering
    let userIdToDelete = null;
    let userCardToRemove = null;

    const searchInput = document.getElementById('adminUserSearch');
    const userCards = document.querySelectorAll('.user-card-item');
    const noUsersMsg = document.getElementById('noUsersFound');
    const confirmDeleteBtn = document.getElementById('confirmDeleteUserBtn');
    
    // Initiera Bootstrap-modalen för användare
    const deleteUserModalEl = document.getElementById('deleteUserModal');
    const bsDeleteUserModal = deleteUserModalEl ? new bootstrap.Modal(deleteUserModalEl) : null;

    // --- LIVE SÖKFUNKTION ---
    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            const term = e.target.value.toLowerCase().trim();
            let hasResults = false;

            userCards.forEach(card => {
                const username = card.dataset.username || "";
                const displayName = card.dataset.displayname || "";

                if (username.includes(term) || displayName.includes(term)) {
                    card.classList.remove('d-none');
                    hasResults = true;
                } else {
                    card.classList.add('d-none');
                }
            });

            // Visa/dölj meddelande om ingen träffas
            if (noUsersMsg) {
                noUsersMsg.classList.toggle('d-none', hasResults);
            }
        });
    }

    // --- ÖPPNA MODAL FÖR RADERING ---
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.delete-user-btn');
        if (!btn) return;

        userIdToDelete = btn.dataset.userId;
        userCardToRemove = btn.closest('.user-card-item');
        
        // Uppdatera texten i modalen så man ser vem man raderar
        const displayUsername = document.getElementById('delete-username-display');
        if (displayUsername) {
            displayUsername.innerText = '@' + btn.dataset.username;
        }

        if (bsDeleteUserModal) {
            bsDeleteUserModal.show();
        }
    });

    // --- BEKRÄFTA RADERING (AJAX) ---
    if (confirmDeleteBtn) {
        confirmDeleteBtn.addEventListener('click', () => {
            if (!userIdToDelete) return;

            const formData = new FormData();
            formData.append('user_id', userIdToDelete);

            fetch('actions/delete_user.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    // Stäng modal
                    if (bsDeleteUserModal) bsDeleteUserModal.hide();

                    // Animera bort kortet
                    if (userCardToRemove) {
                        userCardToRemove.style.opacity = '0';
                        userCardToRemove.style.transform = 'scale(0.9)';
                        userCardToRemove.style.transition = 'all 0.3s ease';
                        
                        setTimeout(() => {
                            userCardToRemove.remove();
                            userIdToDelete = null;
                            userCardToRemove = null;
                        }, 300);
                    }
                } else {
                    alert('Error: ' + (data.error || 'Could not delete user'));
                }
            })
            .catch(err => console.error('Admin delete error:', err));
        });
    }
});
