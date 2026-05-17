document.addEventListener('DOMContentLoaded', function() {
    const canvas = document.getElementById('quackChart');
    if(!canvas) return;
    
    const ctx = canvas.getContext('2d');
    
    // Läser in och parsar arrayerna som skickades från PHP (profile.php) via data-attribut
    const days = JSON.parse(canvas.dataset.days);
    const counts = JSON.parse(canvas.dataset.counts);

    // ANIMATIONS-VARIABLER: Progress går från 0 (start) till 1 (helt färdigritad)
    let animationProgress = 0;
    const animationSpeed = 0.02;

    function renderChart() {
        // RETINA-SKALNING (DPR): Läser av skärmens pixeltäthet. 
        // Detta förhindrar att Canvas-grafiken blir suddig eller pixlig på moderna mobiler och laptops.
        const dpr = window.devicePixelRatio || 1;
        const rect = canvas.getBoundingClientRect();
        
        // Anpassar internt antal pixlar i förhållande till Canvasens fysiska storlek på skärmen
        canvas.width = rect.width * dpr;
        canvas.height = rect.height * dpr;
        ctx.scale(dpr, dpr); // Skalar om koordinatsystemet automatiskt
        ctx.clearRect(0, 0, rect.width, rect.height); // Rensar ytan inför nästa frame

        // MATEMATISK LAYOUT: Sätter 5 som lägsta tak i grafen så att 1 quack inte fyller hela rutan.
        const maxVal = Math.max(...counts, 5);
        const barWidth = rect.width / 12; 
        const spacing = (rect.width - (barWidth * counts.length)) / (counts.length + 1); // Jämn fördelning av marginaler

        counts.forEach((val, i) => {
            // Beräknar exakt X-position för varje enskild stapel i diagrammet
            const x = spacing + (i * (barWidth + spacing));
            const chartBottom = rect.height - 40;
            const maxBarHeight = rect.height - 70;
            
            // ANIMATIONS-EASING: Multiplicerar den tänkta höjden med animationProgress 
            // vilket gör att staplarna "växer" uppåt när sidan laddas.
            const finalBarHeight = (val / maxVal) * maxBarHeight;
            const currentBarHeight = finalBarHeight * animationProgress;
            
            // ANVÄNDARVÄNLIGHET: Gör stapeln för nuvarande kalenderdag grön, resterande vita
            ctx.fillStyle = (days[i] === "Today") ? "#1ba926" : "#FFFFFF";
            
            if (currentBarHeight > 2) {
                ctx.beginPath();
                // Ritar stapeln med snyggt rundade hörn (4px) högst upp
                ctx.roundRect(x, chartBottom - currentBarHeight, barWidth, currentBarHeight, 4);
                ctx.fill();
            } else {
                // Design-fallback: Om användaren har 0 quacks en dag ritas ett svagt streck ut som baslinje
                ctx.globalAlpha = 0.3;
                ctx.fillRect(x, chartBottom - 2, barWidth, 2);
                ctx.globalAlpha = 1.0;
            }
            
            // Ritar ut text för veckodag (t.ex. "Mon", "Today") under stapeln
            ctx.fillStyle = "#FFFFFF";
            ctx.font = "bold 12px sans-serif";
            ctx.textAlign = "center";
            ctx.fillText(days[i], x + (barWidth / 2), rect.height - 15);

            // ANIMERAD TEXTUTSKRIFT: Visar antalet quacks ovanför stapeln.
            // Tonar mjukt in texten (globalAlpha) först när stapeln har växt till 80% av sin höjd.
            if(val > 0 && animationProgress > 0.8) {
                ctx.globalAlpha = (animationProgress - 0.8) * 5;
                ctx.shadowColor = "rgba(0, 0, 0, 0.5)"; // Lägger på en mjuk skugga för läsbarhet
                ctx.shadowBlur = 4;
                ctx.font = "bold 13px sans-serif";
                ctx.fillText(val, x + (barWidth / 2), chartBottom - currentBarHeight - 12);
                ctx.shadowBlur = 0; // Återställer skuggan så att inte nästa frame blir lidande
                ctx.globalAlpha = 1.0;
            }
        });

        // ANIMATIONS-LOOP: Så länge progress är under 1 räknar vi upp värdet och ritar nästa frame.
        if (animationProgress < 1) {
            animationProgress += animationSpeed;
            // Lägger på en acceleration/inbromsning (easing) så att animationen känns organisk och mjuk
            animationProgress += (1 - animationProgress) * 0.05; 
            requestAnimationFrame(renderChart); // Webbläsarkontrollerad loop (60 FPS)
        }
    }

    // Startar igång ritandet direkt vid sidladdning
    renderChart();

    // RESPONSIVITET: Om användaren ändrar storlek på webbläsarfönstret (resize) 
    // ritas grafen genast om statiskt (animationProgress = 1) för att passa den nya skärmbredden.
    window.addEventListener('resize', () => {
        animationProgress = 1;
        renderChart();
    });
});
