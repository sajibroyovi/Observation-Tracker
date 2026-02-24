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

// Background Animation logic removed as per request

// Form validation for observation images
function validateImageCount(input) {
    if (input.files.length > 2) {
        alert("You can only upload a maximum of 2 images.");
        input.value = ""; // Clear selection
    }
}
