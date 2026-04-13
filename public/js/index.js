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

    // multiple images preview
    document.getElementById('quack-images').addEventListener('change', function(e) {
        const container = document.getElementById('img-preview-container');
        container.innerHTML = '';
        Array.from(e.target.files).forEach(file => {
            const reader = new FileReader();
            reader.onload = function(event) {
                const div = document.createElement('div');
                div.innerHTML = `<img src="${event.target.result}" style="width:80px;height:80px;object-fit:cover;" class="rounded shadow-sm">`;
                container.appendChild(div);
            }
            reader.readAsDataURL(file);
        });
    });
});