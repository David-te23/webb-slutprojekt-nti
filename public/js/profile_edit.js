document.addEventListener('DOMContentLoaded', function() {
    const pfpInput = document.getElementById('pfpInput');
    const previewImg = document.getElementById('previewImg');

    if (pfpInput && previewImg) {
        pfpInput.addEventListener('change', function() {
            const [file] = this.files;
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImg.src = e.target.result;
                }
                reader.readAsDataURL(file);
            }
        });
    }
});
