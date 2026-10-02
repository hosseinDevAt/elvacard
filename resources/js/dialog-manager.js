/**
 * ElvaCard Unified Dialog & Toast Manager
 * Persian RTL accessible modal dialogs and toast notifications.
 */

class DialogManager {
    constructor() {
        this.confirmResolver = null;
        this.activeOptions = null;
    }

    /**
     * Open confirmation modal.
     * @param {Object} options
     * @returns {Promise<boolean>}
     */
    confirm(options = {}) {
        return new Promise((resolve) => {
            const detail = {
                title: options.title || 'تأیید عملیات',
                message: options.message || 'آیا از انجام این عملیات مطمئن هستید؟',
                confirmText: options.confirmText || 'تایید',
                cancelText: options.cancelText || 'انصراف',
                variant: options.variant || 'primary',
                onConfirm: () => {
                    if (typeof options.onConfirm === 'function') {
                        options.onConfirm();
                    }
                    resolve(true);
                },
                onCancel: () => {
                    if (typeof options.onCancel === 'function') {
                        options.onCancel();
                    }
                    resolve(false);
                }
            };
            window.dispatchEvent(new CustomEvent('open-confirmation', { detail }));
        });
    }

    /**
     * Dispatch a toast notification.
     * @param {Object|string} options
     */
    toast(options) {
        const detail = typeof options === 'string'
            ? { message: options, type: 'info' }
            : {
                message: options.message || '',
                type: options.type || 'info',
                title: options.title || null,
                duration: options.duration || 4000
            };
        window.dispatchEvent(new CustomEvent('elva-toast', { detail }));
    }
}

const elvaDialog = new DialogManager();
window.ElvaDialog = elvaDialog;
window.$confirm = (message, options = {}) => elvaDialog.confirm({ message, ...options });
window.$toast = (message, type = 'info', options = {}) => elvaDialog.toast({ message, type, ...options });

/**
 * Register Alpine.js components
 */
export function registerDialogComponents(Alpine) {
    // 1. Shared Confirmation Modal Component
    Alpine.data('confirmationModal', () => ({
        isOpen: false,
        isProcessing: false,
        title: '',
        message: '',
        confirmText: 'تایید',
        cancelText: 'انصراف',
        variant: 'primary',
        previousActiveElement: null,
        onConfirmCallback: null,
        onCancelCallback: null,

        init() {
            window.addEventListener('open-confirmation', (event) => {
                this.open(event.detail);
            });
        },

        open(detail) {
            this.previousActiveElement = document.activeElement;
            this.title = detail.title || 'تأیید عملیات';
            this.message = detail.message || 'آیا مطمئن هستید؟';
            this.confirmText = detail.confirmText || 'تایید';
            this.cancelText = detail.cancelText || 'انصراف';
            this.variant = detail.variant || 'primary';
            this.onConfirmCallback = detail.onConfirm || null;
            this.onCancelCallback = detail.onCancel || null;
            this.isProcessing = false;
            this.isOpen = true;

            // Focus management: default to Cancel button for destructive actions
            this.$nextTick(() => {
                if (this.variant === 'danger' || this.variant === 'warning') {
                    if (this.$refs.cancelBtn) {
                        this.$refs.cancelBtn.focus();
                    }
                } else {
                    if (this.$refs.confirmBtn) {
                        this.$refs.confirmBtn.focus();
                    }
                }
            });
        },

        confirm() {
            if (this.isProcessing) return;
            this.isProcessing = true;
            try {
                if (typeof this.onConfirmCallback === 'function') {
                    this.onConfirmCallback();
                }
            } finally {
                this.close();
            }
        },

        cancel() {
            if (this.isProcessing) return;
            try {
                if (typeof this.onCancelCallback === 'function') {
                    this.onCancelCallback();
                }
            } finally {
                this.close();
            }
        },

        close() {
            this.isOpen = false;
            this.isProcessing = false;
            if (this.previousActiveElement && typeof this.previousActiveElement.focus === 'function') {
                this.$nextTick(() => {
                    this.previousActiveElement.focus();
                });
            }
        },

        trapFocus(event) {
            const focusables = [this.$refs.cancelBtn, this.$refs.confirmBtn].filter(Boolean);
            if (focusables.length === 0) return;

            const first = focusables[0];
            const last = focusables[focusables.length - 1];

            if (event.shiftKey && document.activeElement === first) {
                last.focus();
                event.preventDefault();
            } else if (!event.shiftKey && document.activeElement === last) {
                first.focus();
                event.preventDefault();
            }
        }
    }));

    // 2. Shared Toast Container Component
    Alpine.data('toastContainer', (initialToasts = []) => ({
        toasts: [],

        init() {
            // Seed initial flash messages from server if provided
            if (Array.isArray(initialToasts)) {
                initialToasts.forEach(t => this.add(t));
            }

            window.addEventListener('elva-toast', (event) => {
                this.add(event.detail);
            });
        },

        add(detail) {
            if (!detail || !detail.message) return;

            // Avoid rapid duplicate toasts with identical text
            const exists = this.toasts.find(t => t.message === detail.message && Date.now() - t.timestamp < 1500);
            if (exists) return;

            const id = 'toast_' + Date.now() + '_' + Math.random().toString(36).substring(2, 7);
            const duration = detail.duration !== undefined ? detail.duration : 4000;
            const toast = {
                id,
                message: detail.message,
                type: detail.type || 'info',
                title: detail.title || null,
                timestamp: Date.now(),
                remaining: duration,
                timer: null,
                startedAt: Date.now()
            };

            this.toasts.push(toast);

            if (duration > 0) {
                this.startTimer(toast);
            }
        },

        startTimer(toast) {
            toast.startedAt = Date.now();
            toast.timer = setTimeout(() => {
                this.remove(toast.id);
            }, toast.remaining);
        },

        pause(toast) {
            if (toast.timer) {
                clearTimeout(toast.timer);
                toast.timer = null;
                const elapsed = Date.now() - toast.startedAt;
                toast.remaining = Math.max(500, toast.remaining - elapsed);
            }
        },

        resume(toast) {
            if (!toast.timer && toast.remaining > 0) {
                this.startTimer(toast);
            }
        },

        remove(id) {
            const index = this.toasts.findIndex(t => t.id === id);
            if (index !== -1) {
                const toast = this.toasts[index];
                if (toast.timer) clearTimeout(toast.timer);
                this.toasts.splice(index, 1);
            }
        }
    }));
}

/**
 * Initialize Interceptors for Livewire and HTML Forms
 */
export function initDialogInterceptors() {
    // 1. Livewire wire:confirm directive interceptor
    document.addEventListener('livewire:init', () => {
        if (!window.Livewire) return;

        window.Livewire.hook('directive.init', ({ el, directive }) => {
            if (directive.value === 'confirm') {
                const message = directive.expression || 'آیا از انجام این عملیات مطمئن هستید؟';

                // Notice: In Persian, the word "پرداخت" (payment) contains "رد" as a substring (پ-رد-اخت).
                // We use precise regex to avoid false positives on payment approval.
                const isPaymentApprove = /تأیید|تایید|approve/i.test(message) && !/رد/i.test(message.replace(/پرداخت/g, ''));
                const isPaymentReject = /رد.*پرداخت/i.test(message) || /(^|\s)رد(\s|$|[\.\،\؟])/i.test(message.replace(/پرداخت/g, ''));
                const isDelete = /حذف|delete/i.test(message);
                const isBlock = /مسدودسازی/i.test(message);
                const isUnblock = /رفع مسدودی/i.test(message);

                let title, variant, confirmText;

                if (isPaymentApprove || isUnblock) {
                    variant = 'success';
                    title = isPaymentApprove ? 'تأیید پرداخت' : 'رفع مسدودی حساب کاربر';
                    confirmText = isPaymentApprove ? 'بله، تأیید شود' : 'بله، رفع مسدودی شود';
                } else if (isPaymentReject) {
                    variant = 'danger';
                    title = 'رد پرداخت';
                    confirmText = 'بله، رد شود';
                } else if (isDelete) {
                    variant = 'danger';
                    title = 'تأیید حذف';
                    confirmText = 'بله، حذف شود';
                } else if (isBlock) {
                    variant = 'danger';
                    title = 'مسدودسازی حساب کاربر';
                    confirmText = 'بله، مسدود شود';
                } else {
                    variant = 'primary';
                    title = 'تأیید عملیات';
                    confirmText = 'تایید';
                }

                title = el.getAttribute('data-confirm-title') || title;
                variant = el.getAttribute('data-confirm-variant') || variant;
                confirmText = el.getAttribute('data-confirm-btn') || confirmText;

                el.__livewire_confirm = (action, instead) => {
                    elvaDialog.confirm({
                        title,
                        message,
                        variant,
                        confirmText,
                        cancelText: 'انصراف',
                        onConfirm: () => action(),
                        onCancel: () => instead()
                    });
                };
            }
        });

        // Listen for server-side Livewire toast events: $this->dispatch('toast', ...)
        window.Livewire.on('toast', (data) => {
            const payload = Array.isArray(data) ? data[0] : data;
            elvaDialog.toast(payload);
        });
    });

    // 2. Public HTML Forms with [data-confirm] (e.g. cart.empty)
    document.addEventListener('submit', (e) => {
        const form = e.target.closest('form');
        if (!form || !form.hasAttribute('data-confirm')) return;

        // If form has already been confirmed by modal, allow submission
        if (form.dataset.dialogConfirmed === 'true') {
            form.dataset.dialogConfirmed = '';
            return;
        }

        e.preventDefault();
        e.stopPropagation();

        const message = form.getAttribute('data-confirm');
        const title = form.getAttribute('data-confirm-title') || 'تأیید عملیات';
        const variant = form.getAttribute('data-confirm-variant') || 'warning';
        const confirmText = form.getAttribute('data-confirm-btn') || 'بله، انجام شود';

        elvaDialog.confirm({
            title,
            message,
            variant,
            confirmText,
            cancelText: 'انصراف',
            onConfirm: () => {
                form.dataset.dialogConfirmed = 'true';
                if (typeof form.requestSubmit === 'function') {
                    form.requestSubmit();
                } else {
                    form.submit();
                }
            }
        });
    }, true); // Capture phase ensures intercepting before any other submit handlers
}

export default elvaDialog;
