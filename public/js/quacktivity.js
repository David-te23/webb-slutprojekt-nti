document.addEventListener('DOMContentLoaded', function() {
    const canvas = document.getElementById('quackChart');
    if(!canvas) return;
    
    const ctx = canvas.getContext('2d');
    const days = JSON.parse(canvas.dataset.days);
    const counts = JSON.parse(canvas.dataset.counts);

    let animationProgress = 0;
    const animationSpeed = 0.02; // Justera för snabbare/långsammare animation

    function renderChart() {
        const dpr = window.devicePixelRatio || 1;
        const rect = canvas.getBoundingClientRect();
        
        canvas.width = rect.width * dpr;
        canvas.height = rect.height * dpr;
        ctx.scale(dpr, dpr);
        ctx.clearRect(0, 0, rect.width, rect.height);

        const maxVal = Math.max(...counts, 5);
        const barWidth = rect.width / 12; 
        const spacing = (rect.width - (barWidth * counts.length)) / (counts.length + 1);

        counts.forEach((val, i) => {
            const x = spacing + (i * (barWidth + spacing));
            const chartBottom = rect.height - 40;
            const maxBarHeight = rect.height - 70;
            
            // Multiplicera barHeight med progress för animation
            const finalBarHeight = (val / maxVal) * maxBarHeight;
            const currentBarHeight = finalBarHeight * animationProgress;
            
            // Rita stapel (vid dagens dag rita ut med grönt)
            ctx.fillStyle = (days[i] === "Today") ? "#1ba926" : "#FFFFFF";
            
            if (currentBarHeight > 2) {
                ctx.beginPath();
                ctx.roundRect(x, chartBottom - currentBarHeight, barWidth, currentBarHeight, 4);
                ctx.fill();
            } else {
                ctx.globalAlpha = 0.3;
                ctx.fillRect(x, chartBottom - 2, barWidth, 2);
                ctx.globalAlpha = 1.0;
            }
            
            // Text för veckodag
            ctx.fillStyle = "#FFFFFF";
            ctx.font = "bold 12px sans-serif";
            ctx.textAlign = "center";
            ctx.fillText(days[i], x + (barWidth / 2), rect.height - 15);

            // Text för antal quacks 
            if(val > 0 && animationProgress > 0.8) {
                ctx.globalAlpha = (animationProgress - 0.8) * 5;
                ctx.shadowColor = "rgba(0, 0, 0, 0.5)";
                ctx.shadowBlur = 4;
                ctx.font = "bold 13px sans-serif";
                ctx.fillText(val, x + (barWidth / 2), chartBottom - currentBarHeight - 12);
                ctx.shadowBlur = 0;
                ctx.globalAlpha = 1.0;
            }
        });

        if (animationProgress < 1) {
            animationProgress += animationSpeed;
            animationProgress += (1 - animationProgress) * 0.05; 
            requestAnimationFrame(renderChart);
        }
    }

    // Starta animationen
    renderChart();

    // Vid resize rita om grafen statiskt
    window.addEventListener('resize', () => {
        animationProgress = 1;
        renderChart();
    });
});
