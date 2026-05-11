document.addEventListener('DOMContentLoaded', () => {
    
    // Filuppladdning och preview
    const fileInput = document.getElementById('quack-images');
    
    if (fileInput) {
        const container = document.getElementById('img-preview-container');
        const quackForm = fileInput.closest('form'); 
        let allFiles = [];

        if (quackForm && container) {
            fileInput.addEventListener('change', function(e) {
                const newFiles = Array.from(e.target.files);
                
                // Max 4 filer totalt
                if (allFiles.length + newFiles.length > 4) {
                    alert("You can upload a maximum of 4 files per quack!");
                    this.value = ''; // Rensa input
                    return;
                }

                newFiles.forEach(file => {
                    allFiles.push(file);

                    // Skapa en temporär URL för preview
                    const fileUrl = URL.createObjectURL(file);
                    const isVideo = file.type.startsWith('video/');

                    const div = document.createElement('div');
                    div.className = 'position-relative d-inline-block m-1';
                    
                    // Skapa media-elementet
                    let mediaHtml;
                    if (isVideo) {
                        // #t=0.1 tvingar fram första bildrutan
                        mediaHtml = `<video src="${fileUrl}#t=0.1" class="rounded shadow-sm preview-img" muted preload="metadata" style="width: 80px; height: 80px; object-fit: cover;"></video>`;
                    } else {
                        mediaHtml = `<img src="${fileUrl}" class="rounded shadow-sm preview-img" style="width: 80px; height: 80px; object-fit: cover;">`;
                    }

                    div.innerHTML = `
                        ${mediaHtml}
                        <button type="button" class="remove-selected-btn btn-close position-absolute top-0 end-0 bg-white rounded-circle p-1 m-1" 
                                aria-label="Remove" style="width: 10px; height: 10px;"></button>
                    `;

                    // Ta bort markerad bild/video
                    div.querySelector('button').onclick = () => {
                        URL.revokeObjectURL(fileUrl); // Städa upp minnet
                        allFiles = allFiles.filter(f => f !== file);
                        div.remove();
                    };

                    container.appendChild(div);
                });

                // Reset input så man kan välja samma fil igen om man vill
                fileInput.value = '';
            });

            // Innan form skickas, bifoga alla sparade filer
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
            tabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            feedContainer.style.opacity = '0.5';

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
