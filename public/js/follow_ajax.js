document.addEventListener('click', function (e) {
    // Hitta om klicket skedde på en follow-knapp (profilen eller sidebaren)
    const btn = e.target.closest('#follow-btn, .follow-btn-sm');
    if (!btn) return;

    e.preventDefault();

    const userId = btn.dataset.userId;
    const currentAction = btn.dataset.action;
    const followerCountEl = document.getElementById('follower-count');

    // Skicka data till PHP
    const formData = new FormData();
    formData.append('user_id', userId);
    formData.append('action', currentAction);

    fetch('actions/follow_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Uppdatera ALLA knappar på sidan som rör denna användare
            // Detta gör att om du följer i sidebaren, så uppdateras även knappen på profilsidan (och vice versa)
            const allRelatedButtons = document.querySelectorAll(`[data-user-id="${userId}"]`);
            
            allRelatedButtons.forEach(button => {
                if (data.action === 'follow') {
                    // Om vi just började följa
                    button.dataset.action = 'unfollow';
                    
                    if (button.id === 'follow-btn') {
                        // Stora knappen på profilen
                        button.textContent = 'Unfollow';
                        button.classList.replace('btn-light', 'btn-outline-danger');
                    } else {
                        // Lilla knappen i sidebaren
                        button.textContent = '✓';
                        button.classList.replace('btn-outline-success', 'btn-success');
                    }
                } else {
                    // Om vi just slutade följa
                    button.dataset.action = 'follow';

                    if (button.id === 'follow-btn') {
                        // Stora knappen på profilen
                        button.textContent = 'Follow';
                        button.classList.replace('btn-outline-danger', 'btn-light');
                    } else {
                        // Lilla knappen i sidebaren
                        button.textContent = '+';
                        button.classList.replace('btn-success', 'btn-outline-success');
                    }
                }
            });

            // 4. Uppdatera siffran på profilsidan (om den finns där)
            if (followerCountEl) {
                followerCountEl.textContent = data.newCount;
            }
        }
    })
    .catch(error => console.error('Error:', error));
});
