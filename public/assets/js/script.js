// Night Mode Toggle Functionality
document.addEventListener('DOMContentLoaded', function () {
    const mainToggle = document.getElementById('night-mode-toggle');
    const sidebarToggle = document.getElementById('night-mode-toggle-sidebar');
    const body = document.body;

    function updateTheme(isDark) {
        if (isDark) {
            body.classList.add('night-mode');
            localStorage.setItem('theme', 'dark');
        } else {
            body.classList.remove('night-mode');
            localStorage.setItem('theme', 'light');
        }

        // Sync both toggles
        if (mainToggle) mainToggle.checked = isDark;
        if (sidebarToggle) sidebarToggle.checked = isDark;
    }

    // Initial load
    const currentTheme = localStorage.getItem('theme') || 'light';
    updateTheme(currentTheme === 'dark');

    // Add listeners to both toggles
    [mainToggle, sidebarToggle].forEach(toggle => {
        if (toggle) {
            toggle.addEventListener('change', function () {
                updateTheme(this.checked);
            });
        }
    });
});

/* --- Mobile Sidebar Toggle Logic --- */
document.addEventListener('DOMContentLoaded', function () {
    const toggleBtn = document.getElementById('mobileSidebarToggle');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');

    if (toggleBtn && sidebar && overlay) {
        function toggleSidebar() {
            sidebar.classList.toggle('show');
            overlay.classList.toggle('show');

            // Toggle icon
            const icon = toggleBtn.querySelector('i');
            if (sidebar.classList.contains('show')) {
                icon.classList.replace('fa-bars', 'fa-times');
            } else {
                icon.classList.replace('fa-times', 'fa-bars');
            }
        }

        toggleBtn.addEventListener('click', toggleSidebar);
        overlay.addEventListener('click', toggleSidebar);
    }
});

// Background Animation logic (Exact Antigravity Homepage Style)
(function () {
    if (document.getElementById('bg-canvas')) return;

    const canvas = document.createElement('canvas');
    canvas.id = 'bg-canvas';
    document.body.prepend(canvas);

    const ctx = canvas.getContext('2d');
    let width, height, particles = [], dots = [];
    let mouse = { x: -1000, y: -1000 };

    // Animation settings
    const particleCount = 80;
    const dotCount = 150;
    const colors = ['#1a73e8', '#4285F4', '#B7BFD9', '#AAB1CC'];

    class Particle {
        constructor() {
            this.init();
        }

        init() {
            this.x = Math.random() * width;
            this.y = Math.random() * height;
            this.vx = (Math.random() - 0.5) * 0.2;
            this.vy = -(Math.random() * 0.5 + 0.2); // Slow upward drift

            this.size = Math.random() * 2 + 2; // Thickness
            this.length = Math.random() * 20 + 10; // Dash length
            this.color = colors[Math.floor(Math.random() * colors.length)];
            this.opacity = Math.random() * 0.5 + 0.2;

            this.angle = Math.random() * 0.2 - 0.1; // Slight tilt
            this.baseX = this.x;
            this.baseY = this.y;
        }

        update() {
            // Constant drift
            this.x += this.vx;
            this.y += this.vy;

            // Mouse Repulsion
            const dx = this.x - mouse.x;
            const dy = this.y - mouse.y;
            const distance = Math.sqrt(dx * dx + dy * dy);
            const forceRadius = 150;

            if (distance < forceRadius) {
                const force = (forceRadius - distance) / forceRadius;
                this.x += (dx / distance) * force * 5;
                this.y += (dy / distance) * force * 5;
            }

            // Boundary check - wrap around
            if (this.y < -50) this.y = height + 50;
            if (this.y > height + 50) this.y = -50;
            if (this.x < -50) this.x = width + 50;
            if (this.x > width + 50) this.x = -50;
        }

        draw() {
            ctx.save();
            ctx.translate(this.x, this.y);
            ctx.rotate(this.angle);

            ctx.beginPath();
            ctx.moveTo(-this.length / 2, 0);
            ctx.lineTo(this.length / 2, 0);
            ctx.lineWidth = this.size;
            ctx.lineCap = 'round';
            ctx.strokeStyle = this.color;
            ctx.globalAlpha = document.body.classList.contains('night-mode') ? this.opacity * 0.4 : this.opacity;
            ctx.stroke();
            ctx.restore();
        }
    }

    class Dot {
        constructor() {
            this.x = Math.random() * width;
            this.y = Math.random() * height;
            this.size = Math.random() * 1.5;
            this.opacity = Math.random() * 0.3 + 0.1;
        }

        draw() {
            const color = document.body.classList.contains('night-mode') ? '255, 255, 255' : '0, 0, 0';
            ctx.fillStyle = `rgba(${color}, ${this.opacity})`;
            ctx.beginPath();
            ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
            ctx.fill();
        }
    }

    function resize() {
        width = canvas.width = window.innerWidth;
        height = canvas.height = window.innerHeight;
        setup();
    }

    function setup() {
        particles = [];
        dots = [];
        for (let i = 0; i < particleCount; i++) particles.push(new Particle());
        for (let i = 0; i < dotCount; i++) dots.push(new Dot());
    }

    function animate() {
        ctx.clearRect(0, 0, width, height);

        dots.forEach(d => d.draw());
        particles.forEach(p => {
            p.update();
            p.draw();
        });

        requestAnimationFrame(animate);
    }

    window.addEventListener('resize', resize);
    window.addEventListener('mousemove', (e) => {
        mouse.x = e.clientX;
        mouse.y = e.clientY;
    });

    resize(); // This calls setup() internally
    animate();
})();

// Form validation for observation images
function validateImageCount(input) {
    if (input.files.length > 2) {
        alert("You can only upload a maximum of 2 images.");
        input.value = ""; // Clear selection
    }
}
