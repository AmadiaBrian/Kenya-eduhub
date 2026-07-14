/**
 * Custom Notification System - Google Console Dashboard风格
 */

class NotificationSystem {
    constructor() {
        this.container = null;
        this.init();
    }

    init() {
        // Create toast container
        this.container = document.createElement('div');
        this.container.className = 'toast-container';
        document.body.appendChild(this.container);
    }

    /**
     * Show a toast notification
     * @param {string} type - 'success', 'error', 'warning', 'info'
     * @param {string} title - Notification title
     * @param {string} message - Notification message
     * @param {number} duration - Auto-dismiss duration in ms (0 for no auto-dismiss)
     */
    toast(type, title, message, duration = 4000) {
        const toast = this.createToast(type, title, message);
        this.container.appendChild(toast);

        // Auto-dismiss
        if (duration > 0) {
            setTimeout(() => {
                this.dismissToast(toast);
            }, duration);
        }

        return toast;
    }

    createToast(type, title, message) {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;

        const icons = {
            success: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>',
            error: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.47 2 2 6.47 2 12s4.47 10 10 10 10-4.47 10-10S17.53 2 12 2zm5 13.59L15.59 17 12 13.41 8.41 17 7 15.59 10.59 12 7 8.41 8.41 7 12 10.59 15.59 7 17 8.41 13.41 12 17 15.59z"/></svg>',
            warning: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/></svg>',
            info: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>'
        };

        toast.innerHTML = `
            <div class="toast-icon">${icons[type] || icons.info}</div>
            <div class="toast-content">
                <div class="toast-title">${this.escapeHtml(title)}</div>
                <div class="toast-message">${this.escapeHtml(message)}</div>
            </div>
            <button class="toast-close" onclick="notificationSystem.dismissToast(this.parentElement)">
                <svg viewBox="0 0 24 24" fill="currentColor" width="16" height="16">
                    <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
                </svg>
            </button>
        `;

        return toast;
    }

    dismissToast(toast) {
        toast.classList.add('hide');
        toast.addEventListener('animationend', () => {
            if (toast.parentElement) {
                toast.remove();
            }
        });
    }

    /**
     * Show a success toast
     */
    success(title, message, duration) {
        return this.toast('success', title, message, duration);
    }

    /**
     * Show an error toast
     */
    error(title, message, duration) {
        return this.toast('error', title, message, duration);
    }

    /**
     * Show a warning toast
     */
    warning(title, message, duration) {
        return this.toast('warning', title, message, duration);
    }

    /**
     * Show an info toast
     */
    info(title, message, duration) {
        return this.toast('info', title, message, duration);
    }

    /**
     * Show a custom modal dialog
     * @param {string} title - Modal title
     * @param {string} message - Modal message
     * @param {object} options - Modal options
     */
    modal(title, message, options = {}) {
        return new Promise((resolve) => {
            const overlay = document.createElement('div');
            overlay.className = 'modal-overlay';

            const modal = document.createElement('div');
            modal.className = 'custom-modal';

            const defaultOptions = {
                confirmText: 'OK',
                cancelText: 'Cancel',
                showCancel: false,
                type: 'info'
            };

            const opts = { ...defaultOptions, ...options };

            modal.innerHTML = `
                <div class="custom-modal-header">
                    <div class="custom-modal-title">${this.escapeHtml(title)}</div>
                </div>
                <div class="custom-modal-body">
                    <div class="custom-modal-message">${this.escapeHtml(message)}</div>
                </div>
                <div class="custom-modal-footer">
                    ${opts.showCancel ? `<button class="custom-modal-btn custom-modal-btn-secondary" data-action="cancel">${this.escapeHtml(opts.cancelText)}</button>` : ''}
                    <button class="custom-modal-btn custom-modal-btn-primary" data-action="confirm">${this.escapeHtml(opts.confirmText)}</button>
                </div>
            `;

            overlay.appendChild(modal);
            document.body.appendChild(overlay);

            // Handle button clicks
            modal.addEventListener('click', (e) => {
                const action = e.target.dataset.action;
                if (action) {
                    this.dismissModal(overlay);
                    resolve(action === 'confirm');
                }
            });

            // Close on overlay click
            overlay.addEventListener('click', (e) => {
                if (e.target === overlay) {
                    this.dismissModal(overlay);
                    resolve(false);
                }
            });
        });
    }

    dismissModal(overlay) {
        overlay.classList.add('hide');
        overlay.addEventListener('animationend', () => {
            if (overlay.parentElement) {
                overlay.remove();
            }
        });
    }

    /**
     * Show a confirmation dialog
     */
    confirm(message, options = {}) {
        return this.modal('', message, { ...options, showCancel: true });
    }

    /**
     * Show an alert dialog
     */
    alert(message, options = {}) {
        return this.modal('', message, { ...options, showCancel: false });
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * Clear all toasts
     */
    clear() {
        while (this.container.firstChild) {
            this.container.removeChild(this.container.firstChild);
        }
    }
}

// Initialize global instance
const notificationSystem = new NotificationSystem();

// Override browser defaults
window.alert = function(message) {
    return notificationSystem.alert(message);
};

window.confirm = function(message) {
    return notificationSystem.confirm(message);
};
