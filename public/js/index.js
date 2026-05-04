document.addEventListener('DOMContentLoaded', () => {
    
    // Filuppladdning och preview
    const fileInput = document.getElementById('quack-images');
    
    if (fileInput) {
        const container = document.getElementById('img-preview-container');
        const quackForm = fileInput.closest('form'); // Detta orsakar inte längre fel tack vare if(fileInput)
        let allFiles = [];

        if (quackForm && container) {
            fileInput.addEventListener('change', function(e) {
                const newFiles = Array.from(e.target.files);
                
                // Max 4 filer
                if (allFiles.length + newFiles.length > 4) {
                    alert("You can upload a maximum of 4 files per quack!");
                    return;
                }

                newFiles.forEach(file => {
                    allFiles.push(file);

                    const reader = new FileReader();
                    reader.onload = function(event) {
                        const div = document.createElement('div');
                        div.className = 'position-relative';
                        div.innerHTML = `
                            <img src="${event.target.result}" class="rounded shadow-sm preview-img">
                            <button type="button" class="remove-selected-btn btn-close position-absolute top-0 end-0 bg-white rounded-circle p-1" 
                                    aria-label="Remove"></button>
                        `;

                        // Ta bort markerad bild
                        div.querySelector('button').onclick = () => {
                            allFiles = allFiles.filter(f => f !== file);
                            div.remove();
                        };

                        container.appendChild(div);
                    }
                    reader.readAsDataURL(file);
                });

                // Reset input
                fileInput.value = '';
            });

            // Innan form skickas, bifoga filerna
            quackForm.addEventListener('submit', function(e) {
                if (allFiles.length > 0) {
                    const dataTransfer = new DataTransfer();
                    allFiles.forEach(file => dataTransfer.items.add(file));
                    fileInput.files = dataTransfer.files;
                }
            });
        }
    }

    // All/Following tabs för quacks
    const tabs = document.querySelectorAll('.feed-tab');
    const feedContainer = document.getElementById('feed-container');

    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const filter = this.dataset.filter;

            // Ändra utseende med CSS klass
            tabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');

            // Enkel laddningseffekt
            feedContainer.style.opacity = '0.5';

            // Hämta den filtrerade feeden
            fetch(`actions/fetch_feed.php?filter=${filter}`)
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.text();
                })
                .then(html => {
                    feedContainer.innerHTML = html;
                    feedContainer.style.opacity = '1';
                })
                .catch(error => {
                    console.error('Error fetching feed:', error);
                    feedContainer.innerHTML = '<p class="text-white text-center">Failed to load quacks.</p>';
                    feedContainer.style.opacity = '1';
                });
        });
    });
});
