document.addEventListener('click', function (e) {
    const btn = e.target.closest('#follow-btn, .follow-btn-sm');
    if (!btn) return;

    e.preventDefault();

    const userId = btn.dataset.userId; // ID på personen knappen rör
    const currentAction = btn.dataset.action;
    
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
            // 1. Uppdatera alla knappar för denna användare (t.ex. både sidebar och profil)
            const allRelatedButtons = document.querySelectorAll(`[data-user-id="${userId}"]`);
            
            allRelatedButtons.forEach(button => {
                if (data.action === 'follow') {
                    button.dataset.action = 'unfollow';
                    if (button.id === 'follow-btn') {
                        button.textContent = 'Unfollow';
                        button.classList.remove('btn-light');
                        button.classList.add('btn-outline-danger');
                    } else {
                        button.textContent = '✓';
                        button.classList.replace('btn-outline-success', 'btn-success');
                    }
                } else {
                    button.dataset.action = 'follow';
                    if (button.id === 'follow-btn') {
                        button.textContent = 'Follow';
                        button.classList.remove('btn-outline-danger');
                        button.classList.add('btn-light');
                    } else {
                        button.textContent = '+';
                        button.classList.replace('btn-success', 'btn-outline-success');
                    }
                }
            });

            // Uppdatera siffror live baserat på vilken sida vi står på
            const followerCountEl = document.getElementById('follower-count');
            const followingCountEl = document.getElementById('following-count');
            
            const urlParams = new URLSearchParams(window.location.search);
            const profilePageId = urlParams.get('id');

            // Om vi är på den andras profil: Uppdatera deras Followers
            if (followerCountEl && profilePageId === userId) {
                followerCountEl.textContent = data.targetFollowers;
            }

            // Om vi följer någon från vår egen profil (via sidebar): Uppdatera vår Following
            // (Här antar vi att om profilePageId inte finns eller matchar inloggad, visas Following-räknaren)
            if (followingCountEl && profilePageId !== userId) {
                followingCountEl.textContent = data.myFollowing;
            }
        }
    })
    .catch(error => console.error('Error:', error));
});
