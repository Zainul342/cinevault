/**
 * Modal Component - Accessible modal dialogs
 */
const Modal = (() => {
    let activeModal = null;
    let backdrop = null;

    // Create backdrop once
    function getBackdrop() {
        if (!backdrop) {
            backdrop = document.createElement('div');
            backdrop.className = 'modal-backdrop';
            backdrop.style.cssText = `
                position: fixed;
                inset: 0;
                background: rgba(0,0,0,0.7);
                display: none;
                z-index: 999;
                backdrop-filter: blur(4px);
            `;
            backdrop.addEventListener('click', close);
            document.body.appendChild(backdrop);
        }
        return backdrop;
    }

    /**
     * Open a modal
     * @param {Object} options - Modal configuration
     */
    function open(options = {}) {
        const {
            title = '',
            content = '',
            size = 'medium', // 'small' | 'medium' | 'large' | 'fullscreen'
            closable = true,
            onClose = () => { },
            actions = [], // [{label, class, onClick}]
        } = options;

        // Close any existing modal
        if (activeModal) close();

        const modal = document.createElement('div');
        modal.className = `modal modal--${size}`;
        modal.setAttribute('role', 'dialog');
        modal.setAttribute('aria-modal', 'true');
        modal.style.cssText = `
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: var(--bg-card);
            border: var(--border-width) solid var(--border-color);
            border-radius: var(--radius-md);
            box-shadow: 8px 8px 0 var(--border-color);
            z-index: 1000;
            max-width: ${size === 'small' ? '400px' : size === 'large' ? '800px' : '600px'};
            width: 90%;
            max-height: 90vh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        `;

        modal.innerHTML = `
            <div class="modal-header" style="display:flex; justify-content:space-between; align-items:center; padding: var(--spacing-md); border-bottom: var(--border-width) solid var(--border-color);">
                <h2 style="font-family: var(--font-heading); font-size: 1rem; margin:0;">${title}</h2>
                ${closable ? '<button class="modal-close" aria-label="Close"><i class="fas fa-times"></i></button>' : ''}
            </div>
            <div class="modal-body" style="padding: var(--spacing-lg); overflow-y: auto; flex:1;">
                ${typeof content === 'string' ? content : ''}
            </div>
            ${actions.length ? `
                <div class="modal-footer" style="padding: var(--spacing-md); border-top: var(--border-width) solid var(--border-color); display: flex; gap: var(--spacing-sm); justify-content: flex-end;">
                    ${actions.map((a, i) => `<button class="btn ${a.class || ''}" data-action-idx="${i}">${a.label}</button>`).join('')}
                </div>
            ` : ''}
        `;

        // If content is an element, append it
        if (content instanceof HTMLElement) {
            modal.querySelector('.modal-body').innerHTML = '';
            modal.querySelector('.modal-body').appendChild(content);
        }

        // Event handlers
        if (closable) {
            modal.querySelector('.modal-close').addEventListener('click', () => {
                onClose();
                close();
            });
        }

        actions.forEach((action, idx) => {
            const btn = modal.querySelector(`[data-action-idx="${idx}"]`);
            if (btn && action.onClick) {
                btn.addEventListener('click', () => action.onClick());
            }
        });

        // Show
        getBackdrop().style.display = 'block';
        document.body.appendChild(modal);
        activeModal = modal;

        // Trap focus
        modal.querySelector('.modal-close, button')?.focus();

        // Escape key
        document.addEventListener('keydown', handleEscape);

        return modal;
    }

    function handleEscape(e) {
        if (e.key === 'Escape') close();
    }

    function close() {
        if (activeModal) {
            activeModal.remove();
            activeModal = null;
        }
        if (backdrop) {
            backdrop.style.display = 'none';
        }
        document.removeEventListener('keydown', handleEscape);
    }

    /**
     * Confirm dialog shortcut
     */
    function confirm(message, onConfirm, onCancel = () => { }) {
        return open({
            title: 'Confirm',
            content: `<p>${message}</p>`,
            size: 'small',
            actions: [
                { label: 'Cancel', class: '', onClick: () => { onCancel(); close(); } },
                { label: 'Confirm', class: 'btn-primary', onClick: () => { onConfirm(); close(); } },
            ],
        });
    }

    return { open, close, confirm };
})();
