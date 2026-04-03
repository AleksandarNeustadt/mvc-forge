/**
 * Starfield Animation - Realistic Stars
 * Tiny stars that twinkle and move slowly like real stars in the night sky
 */

export class Starfield {
    constructor(canvasId, options = {}) {
        this.canvas = document.getElementById(canvasId);
        if (!this.canvas) {
            return;
        }

        this.ctx = this.canvas.getContext('2d');
        this.stars = [];

        // Configuration for realistic stars
        this.config = {
            starCount: options.starCount || 300,
            maxSize: options.maxSize || 1.2,
            minSize: options.minSize || 0.3,
            maxSpeed: options.maxSpeed || 0.08,
            minSpeed: options.minSpeed || 0.01,
            twinkleSpeed: options.twinkleSpeed || 0.005,
            baseOpacity: options.baseOpacity || 0.7
        };

        this.resizeCanvas();
        this.init();
        this.animate();

        // Handle window resize
        window.addEventListener('resize', () => this.resizeCanvas());
    }

    resizeCanvas() {
        this.canvas.width = window.innerWidth;
        this.canvas.height = window.innerHeight;

        // Reinitialize stars on resize if needed
        if (this.stars.length > 0) {
            this.stars.forEach(star => {
                if (star.x > this.canvas.width) star.x = this.canvas.width;
                if (star.y > this.canvas.height) star.y = this.canvas.height;
            });
        }
    }

    init() {
        this.stars = [];

        for (let i = 0; i < this.config.starCount; i++) {
            this.stars.push(this.createStar());
        }
    }

    createStar() {
        const size = Math.random() * (this.config.maxSize - this.config.minSize) + this.config.minSize;

        // Realistic star colors (temperature-based)
        const starTypes = [
            { r: 255, g: 255, b: 255, weight: 60 },   // White (most common)
            { r: 255, g: 250, b: 240, weight: 20 },   // Warm white
            { r: 240, g: 245, b: 255, weight: 15 },   // Cool white/blue tint
            { r: 255, g: 245, b: 230, weight: 5 }     // Yellowish white
        ];

        // Weighted random selection
        const totalWeight = starTypes.reduce((sum, type) => sum + type.weight, 0);
        let random = Math.random() * totalWeight;
        let selectedColor = starTypes[0];

        for (const type of starTypes) {
            if (random < type.weight) {
                selectedColor = type;
                break;
            }
            random -= type.weight;
        }

        return {
            x: Math.random() * this.canvas.width,
            y: Math.random() * this.canvas.height,
            size: size,
            baseSize: size,
            speedX: (Math.random() - 0.5) * this.config.maxSpeed,
            speedY: (Math.random() - 0.5) * this.config.maxSpeed,
            opacity: Math.random() * 0.4 + this.config.baseOpacity,
            twinkleSpeed: Math.random() * this.config.twinkleSpeed + 0.002,
            twinkleDirection: Math.random() > 0.5 ? 1 : -1,
            color: selectedColor
        };
    }

    updateStar(star) {
        // Move star
        star.x += star.speedX;
        star.y += star.speedY;

        // Wrap around screen edges
        if (star.x < 0) star.x = this.canvas.width;
        if (star.x > this.canvas.width) star.x = 0;
        if (star.y < 0) star.y = this.canvas.height;
        if (star.y > this.canvas.height) star.y = 0;

        // Twinkle effect
        star.opacity += star.twinkleSpeed * star.twinkleDirection;

        // Reverse twinkle direction at bounds
        if (star.opacity >= 1) {
            star.opacity = 1;
            star.twinkleDirection = -1;
        } else if (star.opacity <= 0.2) {
            star.opacity = 0.2;
            star.twinkleDirection = 1;
        }

        // Subtle size pulsing
        star.size = star.baseSize * (0.8 + star.opacity * 0.3);
    }

    drawStar(star) {
        this.ctx.save();

        const { r, g, b } = star.color;

        // Create subtle glow
        const gradient = this.ctx.createRadialGradient(
            star.x, star.y, 0,
            star.x, star.y, star.size * 1.5
        );

        gradient.addColorStop(0, `rgba(${r}, ${g}, ${b}, ${star.opacity})`);
        gradient.addColorStop(0.4, `rgba(${r}, ${g}, ${b}, ${star.opacity * 0.4})`);
        gradient.addColorStop(1, `rgba(${r}, ${g}, ${b}, 0)`);

        // Draw star glow
        this.ctx.fillStyle = gradient;
        this.ctx.beginPath();
        this.ctx.arc(star.x, star.y, star.size * 1.5, 0, Math.PI * 2);
        this.ctx.fill();

        // Draw bright center point
        this.ctx.fillStyle = `rgba(${r}, ${g}, ${b}, ${Math.min(star.opacity * 1.2, 1)})`;
        this.ctx.beginPath();
        this.ctx.arc(star.x, star.y, star.size * 0.6, 0, Math.PI * 2);
        this.ctx.fill();

        this.ctx.restore();
    }

    animate() {
        // Clear canvas completely (no trail effect)
        this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);

        // Update and draw all stars
        this.stars.forEach(star => {
            this.updateStar(star);
            this.drawStar(star);
        });

        requestAnimationFrame(() => this.animate());
    }

    // Method to update star count dynamically
    updateStarCount(count) {
        this.config.starCount = count;
        this.init();
    }

    // Method to change speed
    updateSpeed(speedMultiplier) {
        this.stars.forEach(star => {
            star.speedX *= speedMultiplier;
            star.speedY *= speedMultiplier;
        });
    }
}
