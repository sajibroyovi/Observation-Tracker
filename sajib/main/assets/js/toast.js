/**
 * Professional Toast Notification System
 */
const toast = {
    container: null,

    init() {
        if (!document.getElementById('toast-container')) {
            this.container = document.createElement('div');
            this.container.id = 'toast-container';
            document.body.appendChild(this.container);
        } else {
            this.container = document.getElementById('toast-container');
        }
    },

    show(message, type = 'info', duration = 4000) {
        if (!this.container) this.init();

        const toastEl = document.createElement('div');
        toastEl.className = `toast-message ${type}`;

        // Icon mapping
        const icons = {
            success: 'fa-circle-check',
            error: 'fa-circle-xmark',
            info: 'fa-circle-info'
        };

        const iconEl = document.createElement('i');
        iconEl.className = `fa-solid ${icons[type] || icons.info}`;

        const messageEl = document.createElement('span');
        messageEl.textContent = message;

        toastEl.appendChild(iconEl);
        toastEl.appendChild(messageEl);

        this.container.appendChild(toastEl);

        // Auto remove
        setTimeout(() => {
            toastEl.style.transform = 'translateX(100%)';
            toastEl.style.opacity = '0';
            setTimeout(() => toastEl.remove(), 400);
        }, duration);
    }
};

// Check for status in URL on page load
window.addEventListener('load', () => {
    const urlParams = new URLSearchParams(window.location.search);
    const status = urlParams.get('status');
    const msg = urlParams.get('msg');

    if (status && msg) {
        toast.show(decodeURIComponent(msg), status);
        // Clean up URL without reload
        const newUrl = window.location.pathname;
        window.history.replaceState({}, document.title, newUrl);
    }
});
