document.addEventListener('DOMContentLoaded', () => {
    // Emoji picker
    const trigger = document.querySelector('#emoji-trigger');
    const textarea = document.querySelector('#quack-textarea');
    const pickerContainer = document.querySelector('#picker-container');
    const picker = document.querySelector('emoji-picker');

    if (trigger && pickerContainer && textarea && picker) {
        // Visa/dölj picker
        trigger.addEventListener('click', (e) => {
            e.preventDefault();
            const isVisible = pickerContainer.style.display === 'block';
            pickerContainer.style.display = isVisible ? 'none' : 'block';
        });

        // Lägg till emoji i textarea
        picker.addEventListener('emoji-click', event => {
            textarea.value += event.detail.unicode;
            pickerContainer.style.display = 'none';
        });

        // Stäng om man klickar utanför
        document.addEventListener('click', (e) => {
            if (!trigger.contains(e.target) && !pickerContainer.contains(e.target)) {
                pickerContainer.style.display = 'none';
            }
        });
    }

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
});
