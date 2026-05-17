document.addEventListener("DOMContentLoaded", () => {
    setupLiveSearch("header-search", "desktop-search-results");
    setupLiveSearch("mobileInput", "mobile-search-results");
});

function setupLiveSearch(inputId, resultsId) {
    const searchInput = document.getElementById(inputId);
    const resultsDropdown = document.getElementById(resultsId);
    if (!searchInput || !resultsDropdown) return;

    // DEBOUNCE-LOGIK: Håller reda på timern för sökfördröjningen
    let debounceTimeout;

    searchInput.addEventListener("input", () => {
        // Rensar föregående timer så fort användaren trycker på en ny tangent
        clearTimeout(debounceTimeout);
        const query = searchInput.value.trim();

        // Skicka inga tomma eller extremt korta sökningar till servern
        if (query.length < 2) {
            resultsDropdown.innerHTML = "";
            resultsDropdown.style.display = "none";
            return;
        }

         // DEBOUNCE: Väntar 300ms efter att användaren har slutat skriva innan AJAX-anropet görs.
        // Detta förhindrar att servern bombarderas med databasfrågor vid varje enskilt knapptryck.
        debounceTimeout = setTimeout(() => {
            // encodeURIComponent ser till att specialtecken (som t.ex. # eller åäö) kodas säkert i URL:en
            fetch(`actions/live_search.php?query=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(data => {
                    renderResults(data, resultsDropdown);
                })
                .catch(err => console.error("Search error:", err));
        }, 300);
    });

    // Stäng rullgardinsmenyn om användaren klickar utanför sökrutan
    document.addEventListener("click", (e) => {
        if (!searchInput.contains(e.target) && !resultsDropdown.contains(e.target)) {
            resultsDropdown.style.display = "none";
        }
    });
}

function renderResults(data, container) {
    container.innerHTML = ""; // Rensar gammalt sökresultat
    let hasResults = false;

    // Bygg Användarresultat
    if (data.users && data.users.length > 0) {
        hasResults = true;
        const title = document.createElement("div");
        title.className = "section-title";
        title.textContent = "Användare";
        container.appendChild(title);

        data.users.forEach(user => {
            const item = document.createElement("a");
            item.className = "search-item";
            item.href = `profile.php?id=${user.id}`;
            
            // Fallback om användaren inte har laddat upp en egen profilbild
            const hasCustomImg = user.profile_image && 
                     user.profile_image !== 'default_pfp.jpg' && 
                     user.profile_image.trim() !== '';

            const imgPath = hasCustomImg 
                ? `../uploads/pfp/${user.profile_image}` 
                : `images/default_pfp.jpg`;

            // All text körs genom escapeHtml() för att stoppa lagrad XSS
            item.innerHTML = `
                <img src="${imgPath}" alt="">
                <div>
                    <strong>${escapeHtml(user.display_name)}</strong><br>
                    <span class="text-muted">@${escapeHtml(user.username)}</span>
                </div>
            `;
            container.appendChild(item);
        });
    }

    // Bygg Quacks-resultat
    if (data.quacks && data.quacks.length > 0) {
        hasResults = true;
        const title = document.createElement("div");
        title.className = "section-title";
        title.textContent = "Quacks";
        container.appendChild(title);

        data.quacks.forEach(quack => {
            const item = document.createElement("a");
            item.className = "search-item";
            item.href = `quack.php?id=${quack.id}`; // Länk till det specifika inlägget
            
            // text-truncate ser till att långa inlägg inte förstör dropdown-designen
            item.innerHTML = `
                <div>
                    <span class="text-muted"><strong>${escapeHtml(quack.display_name)}</strong> @${escapeHtml(quack.username)}</span>
                    <p class="mb-0 text-truncate" style="max-width: 300px;">${escapeHtml(quack.content)}</p>
                </div>
            `;
            container.appendChild(item);
        });
    }

    // Växlar synlighet på rullgardinsmenyn baserat på om det blev träff eller inte
    if (hasResults) {
        container.style.display = "block";
    } else {
        container.innerHTML = '<div class="p-3 text-muted text-center">Inga resultat matchade sökningen</div>';
        container.style.display = "block";
    }
}

// Enkel XSS-säkring för JavaScript-strängar
function escapeHtml(string) {
    return String(string).replace(/[&<>"']/g, function (s) {
        return { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[s];
    });
}
