document.addEventListener('DOMContentLoaded', function() {
    const pfpInput = document.getElementById('pfpInput');
    const previewImg = document.getElementById('previewImg');

    if (pfpInput && previewImg) {
        pfpInput.addEventListener('change', function() {
            // plockar ut den första filen ur 'this.files'-arrayen
            const [file] = this.files;
            if (file) {
                // 2. LOKAL PREVIEW: Skapar en Base64-textsträng av bilden.
                // Det gör att användaren ser sin nya profilbild i webbläsaren DIREKT 
                // innan formuläret ens har skickats till backend (update_profile.php).
                const reader = new FileReader();
                reader.onload = function(e) {
                    // När filen är färdigläst i minnet byter vi ut 'src' på profilbilden till förhandsvisningen
                    previewImg.src = e.target.result;
                }
                 // Startar asynkron inläsning av bildfilen
                reader.readAsDataURL(file);
            }
        });
    }
});
