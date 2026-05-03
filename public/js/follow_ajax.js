document.addEventListener('DOMContentLoaded', function() {
    const followBtn = document.getElementById('follow-btn');
    const followerCountEl = document.getElementById('follower-count');

    if (!followBtn) return;

    followBtn.addEventListener('click', function() {
        const userId = this.dataset.userId;
        const currentAction = this.dataset.action;

        // Skapa FormData för att skicka till PHP
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
                // Uppdatera siffran
                followerCountEl.textContent = data.newCount;

                // Uppdatera knappens utseende och text
                if (currentAction === 'follow') {
                    this.textContent = 'Unfollow';
                    this.classList.replace('btn-light', 'btn-outline-danger');
                    this.dataset.action = 'unfollow';
                } else {
                    this.textContent = 'Follow';
                    this.classList.replace('btn-outline-danger', 'btn-light');
                    this.dataset.action = 'follow';
                }
            }
        })
        .catch(error => console.error('Error:', error));
    });
});
