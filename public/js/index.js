document.addEventListener('DOMContentLoaded', () => {
    // emoji picker
    const trigger = document.querySelector('#emoji-trigger');
    const textarea = document.querySelector('#quack-textarea');
    const pickerContainer = document.querySelector('#picker-container');
    const picker = document.querySelector('emoji-picker');

    if (trigger && pickerContainer) {
        //show/hide picker when btn is clicked
        trigger.addEventListener('click', (e) => {
            e.preventDefault();
            const isVisible = pickerContainer.style.display === 'block';
            pickerContainer.style.display = isVisible ? 'none' : 'block';
        });

        picker.addEventListener('emoji-click', event => {
            //add unicode for chosen emoji to textarea
            textarea.value += event.detail.unicode;

            //close picker after an emoji is chosen
            pickerContainer.style.display = 'none';
        });

        //close picker if clicked elsewhere
        document.addEventListener('click', (e) => {
            if (!trigger.contains(e.target) && !pickerContainer.contains(e.target)) {
                pickerContainer.style.display = 'none';
            }
        });
    }

    //File upload and preview
    const fileInput = document.getElementById('quack-images');
    const container = document.getElementById('img-preview-container');
    const quackForm = fileInput.closest('form');
    let allFiles = [];

    fileInput.addEventListener('change', function(e) {
        const newFiles = Array.from(e.target.files);
        
        // limit: Max 4 files
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

                // Remove selected image
                div.querySelector('button').onclick = () => {
                    allFiles = allFiles.filter(f => f !== file);
                    div.remove();
                };

                container.appendChild(div);
            }
            reader.readAsDataURL(file);
        });

        // reset input so you can select same file name again.
        fileInput.value = '';
    });

    // before form gets sent, pack allFiles into the real input
    quackForm.addEventListener('submit', function(e) {
        if (allFiles.length > 0) {
            const dataTransfer = new DataTransfer();
            allFiles.forEach(file => dataTransfer.items.add(file));
            fileInput.files = dataTransfer.files;
        }
    });

    // Like function AJAX script for live update
    document.querySelectorAll('.like-btn').forEach(button => {
        button.addEventListener('click', function() {
            const quackId = this.getAttribute('data-quack-id');
            const countSpan = this.querySelector('.like-count');
    
            fetch('actions/like_handler.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `quack_id=${quackId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    countSpan.innerText = data.new_count;
                    this.classList.toggle('is-liked', data.is_liked);
                }
            });
        });
    });
    
});