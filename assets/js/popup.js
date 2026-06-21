/**
 * Popup Notification System
 * Global object: window.Popup
 *
 * Usage:
 *   Popup.success('عنوان', 'پیام اختیاری');
 *   Popup.error('خطا', 'توضیحات');
 *   Popup.warning('هشدار', 'متن');
 *   Popup.info('اطلاعات', 'متن');
 *   Popup.show({ type, title, message, duration });
 */

(function (global) {
    'use strict';

    /* ── Config ──────────────────────────────────────────────── */
    const DEFAULTS = {
        duration : 5000,   // ms before auto-close (0 = never)
        maxCount : 5,      // max toasts visible at once
    };

    const ICONS = {
        success : 'fas fa-check-circle',
        error   : 'fas fa-times-circle',
        warning : 'fas fa-exclamation-triangle',
        info    : 'fas fa-info-circle',
    };

    const TITLES = {
        success : 'موفقیت',
        error   : 'خطا',
        warning : 'هشدار',
        info    : 'اطلاعات',
    };

    /* ── Container ───────────────────────────────────────────── */
    let container = null;

    function getContainer() {
        if (container && document.body.contains(container)) {
            return container;
        }
        container = document.getElementById('popup-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'popup-container';
            document.body.appendChild(container);
        }
        return container;
    }

    /* ── Core: show ─────────────────────────────────────────── */
    function show(options) {
        const type     = options.type     || 'info';
        const title    = options.title    || TITLES[type] || 'اطلاعیه';
        const message  = options.message  || '';
        const duration = (options.duration !== undefined) ? options.duration : DEFAULTS.duration;

        const c = getContainer();

        // Enforce max count — remove oldest
        const existing = c.querySelectorAll('.popup-notification:not(.popup-hiding)');
        if (existing.length >= DEFAULTS.maxCount) {
            hidePopup(existing[0]);
        }

        /* ── Build DOM ── */
        const popup = document.createElement('div');
        popup.className = `popup-notification popup-${type}`;
        popup.setAttribute('role', 'alert');
        popup.setAttribute('aria-live', 'assertive');

        // Icon
        const iconEl = document.createElement('div');
        iconEl.className = 'popup-icon';
        iconEl.innerHTML = `<i class="${ICONS[type] || ICONS.info}"></i>`;

        // Body
        const bodyEl  = document.createElement('div');
        bodyEl.className = 'popup-body';

        const titleEl = document.createElement('p');
        titleEl.className  = 'popup-title';
        titleEl.textContent = title;

        bodyEl.appendChild(titleEl);

        if (message) {
            const msgEl = document.createElement('p');
            msgEl.className   = 'popup-message';
            msgEl.textContent  = message;
            bodyEl.appendChild(msgEl);
        }

        // Close button
        const closeBtn = document.createElement('button');
        closeBtn.className   = 'popup-close';
        closeBtn.setAttribute('aria-label', 'بستن');
        closeBtn.innerHTML   = '<i class="fas fa-times"></i>';
        closeBtn.addEventListener('click', () => hidePopup(popup));

        popup.appendChild(iconEl);
        popup.appendChild(bodyEl);
        popup.appendChild(closeBtn);

        // Progress bar
        if (duration > 0) {
            const progress = document.createElement('div');
            progress.className  = 'popup-progress';
            progress.style.animationDuration = duration + 'ms';
            popup.appendChild(progress);
        }

        c.appendChild(popup);

        /* ── Auto-hide ── */
        let timerId = null;
        if (duration > 0) {
            timerId = setTimeout(() => hidePopup(popup), duration);
        }

        // Pause on hover
        popup.addEventListener('mouseenter', () => {
            if (timerId) {
                clearTimeout(timerId);
                const pb = popup.querySelector('.popup-progress');
                if (pb) pb.style.animationPlayState = 'paused';
            }
        });

        popup.addEventListener('mouseleave', () => {
            const pb = popup.querySelector('.popup-progress');
            if (pb) {
                const remaining = parseFloat(getComputedStyle(pb).width)
                    / popup.offsetWidth * duration;
                pb.style.animationPlayState = 'running';
                timerId = setTimeout(() => hidePopup(popup), remaining || 1000);
            }
        });

        return popup;
    }

    /* ── Core: hide ─────────────────────────────────────────── */
    function hidePopup(popup) {
        if (!popup || popup.classList.contains('popup-hiding')) return;
        popup.classList.add('popup-hiding');
        popup.addEventListener('animationend', () => popup.remove(), { once: true });
        // Fallback if animation event never fires
        setTimeout(() => { if (popup.parentNode) popup.remove(); }, 500);
    }

    /* ── Public API ─────────────────────────────────────────── */
    const Popup = {
        show,
        success : (title, message = '', duration = DEFAULTS.duration) =>
            show({ type: 'success', title, message, duration }),
        error   : (title, message = '', duration = DEFAULTS.duration) =>
            show({ type: 'error', title, message, duration }),
        warning : (title, message = '', duration = DEFAULTS.duration) =>
            show({ type: 'warning', title, message, duration }),
        info    : (title, message = '', duration = DEFAULTS.duration) =>
            show({ type: 'info', title, message, duration }),
        dismiss : hidePopup,
        /** Remove all currently visible popups */
        clear   : () => {
            const c = getContainer();
            c.querySelectorAll('.popup-notification').forEach(hidePopup);
        },
    };

    global.Popup = Popup;

})(window);
