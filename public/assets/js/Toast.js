/**
 * Toast Notifications - Retro styled
 */
const Toast = (() => {
    let container = null;
    const queue = [];

    function getContainer() {
        if (!container) {
            container = document.createElement('div');
            container.className = 'toast-container';
            container.style.cssText = `
                position: fixed;
                bottom: 20px;
                right: 20px;
                z-index: 9999;
                display: flex;
                flex-direction: column;
                gap: 10px;
                max-width: 350px;
            `;
            document.body.appendChild(container);
        }
        return container;
    }

    /**
     * Show a toast notification
     * @param {string} message - Toast message
     * @param {Object} options - Configuration
     */
    function show(message, options = {}) {
        const {
            type = 'info', // 'success' | 'error' | 'warning' | 'info'
            duration = 4000,
            dismissible = true,
        } = options;

        const colors = {
            success: 'var(--accent)',
            error: '#ff6b6b',
            warning: 'var(--primary)',
            info: 'var(--bg-blue)',
        };

        const icons = {
            success: 'fa-check-circle',
            error: 'fa-times-circle',
            warning: 'fa-exclamation-triangle',
            info: 'fa-info-circle',
        };

        const toast = document.createElement('div');
        toast.className = `toast toast--${type}`;
        toast.style.cssText = `
            background: var(--bg-card);
            border: var(--border-width) solid var(--border-color);
            border-left: 4px solid ${colors[type]};
            border-radius: var(--radius-sm);
            padding: var(--spacing-md);
            box-shadow: var(--shadow-hard);
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
            animation: slideIn 0.3s ease;
        `;

        toast.innerHTML = `
            <i class="fas ${icons[type]}" style="color: ${colors[type]}; font-size: 1.2rem;"></i>
            <span style="flex:1;">${message}</span>
            ${dismissible ? '<button class="toast-close" style="background:none; border:none; cursor:pointer; padding:4px;"><i class="fas fa-times"></i></button>' : ''}
        `;

        // Dismiss handler
        if (dismissible) {
            toast.querySelector('.toast-close').addEventListener('click', () => dismiss(toast));
        }

        getContainer().appendChild(toast);

        // Auto dismiss
        if (duration > 0) {
            setTimeout(() => dismiss(toast), duration);
        }

        return toast;
    }

    function dismiss(toast) {
        toast.style.animation = 'slideOut 0.2s ease';
        setTimeout(() => toast.remove(), 200);
    }

    // Shortcut methods
    const success = (msg, opts) => show(msg, { ...opts, type: 'success' });
    const error = (msg, opts) => show(msg, { ...opts, type: 'error' });
    const warning = (msg, opts) => show(msg, { ...opts, type: 'warning' });
    const info = (msg, opts) => show(msg, { ...opts, type: 'info' });

    return { show, success, error, warning, info };
})();

// Add toast animations to document
const toastStyle = document.createElement('style');
toastStyle.textContent = `
    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    @keyframes slideOut {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
`;
document.head.appendChild(toastStyle);
