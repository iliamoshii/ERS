/**
 * User Panel — shared behavior across dashboard.php / profile.php / reservations.php
 *
 * Handles only the GENERIC, cross-page pieces:
 *   - sidebar mobile toggle + overlay
 *   - topbar user-menu dropdown
 *   - logout (both sidebar's #logoutLink and the topbar dropdown's logout link)
 *   - individual notification click-to-mark-read on dashboard.php
 *
 * Anything already self-contained inline on a page (profile.php's forms,
 * reservations.php's cancel modal, dashboard.php's "mark all read" button)
 * is left alone — this file does not duplicate that logic.
 */

(function () {
    'use strict';

    const API_BASE = '../backend/api';

    /* ── CSRF token helper ──────────────────────────────────────
       Each page defines `const CSRF_TOKEN = '...'` in its own trailing
       inline <script>, which runs AFTER this file. That's fine — these
       handlers only execute later, on user interaction, by which point
       CSRF_TOKEN already exists in the global scope. We still fall back
       to scraping a hidden input for extra safety on any page that
       doesn't define the constant. */
    function getCSRFToken() {
        try {
            if (typeof CSRF_TOKEN !== 'undefined' && CSRF_TOKEN) {
                return CSRF_TOKEN;
            }
        } catch (e) { /* not defined yet, fall through */ }

        const input = document.querySelector('input[name="csrf_token"]');
        return input ? input.value : '';
    }

    /* ═══════════════════════════════════════════════════════════
       SIDEBAR — mobile toggle + overlay
    ═══════════════════════════════════════════════════════════ */
    function initSidebarToggle() {
        const toggleBtn = document.getElementById('sidebarToggle');
        const sidebar   = document.getElementById('panelSidebar');
        const overlay   = document.getElementById('sidebarOverlay');

        if (!toggleBtn || !sidebar) return;

        function openSidebar() {
            sidebar.classList.add('open');
            overlay?.classList.add('show');
        }

        function closeSidebar() {
            sidebar.classList.remove('open');
            overlay?.classList.remove('show');
        }

        toggleBtn.addEventListener('click', () => {
            sidebar.classList.contains('open') ? closeSidebar() : openSidebar();
        });

        overlay?.addEventListener('click', closeSidebar);

        // Close on navigation-width resize back to desktop
        window.addEventListener('resize', () => {
            if (window.innerWidth > 991) closeSidebar();
        });
    }

    /* ═══════════════════════════════════════════════════════════
       TOPBAR — user dropdown menu
    ═══════════════════════════════════════════════════════════ */
    function initUserDropdown() {
        const btn      = document.getElementById('userMenuBtn');
        const dropdown = document.getElementById('userDropdown');

        if (!btn || !dropdown) return;

        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            const isOpen = dropdown.classList.contains('show');
            dropdown.classList.toggle('show', !isOpen);
            btn.classList.toggle('open', !isOpen);
        });

        document.addEventListener('click', (e) => {
            if (!dropdown.contains(e.target) && !btn.contains(e.target)) {
                dropdown.classList.remove('show');
                btn.classList.remove('open');
            }
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                dropdown.classList.remove('show');
                btn.classList.remove('open');
            }
        });
    }

    /* ═══════════════════════════════════════════════════════════
       LOGOUT — works for sidebar's #logoutLink AND the topbar
       dropdown's logout link (neither has a working href on its own,
       since auth.php's router requires POST + CSRF)
    ═══════════════════════════════════════════════════════════ */
    function initLogout() {
        const logoutLinks = document.querySelectorAll('a[href*="action=logout"]');
        if (!logoutLinks.length) return;

        logoutLinks.forEach((link) => {
            link.addEventListener('click', async (e) => {
                e.preventDefault();

                const original = link.textContent;
                link.style.opacity = '0.6';
                link.style.pointerEvents = 'none';

                try {
                    const fd = new FormData();
                    fd.append('action', 'logout');
                    fd.append('csrf_token', getCSRFToken());

                    const res  = await fetch(`${API_BASE}/auth.php?action=logout`, {
                        method      : 'POST',
                        credentials : 'same-origin',
                        body        : fd,
                    });

                    // auth.php's logout() redirects via Response::redirect(),
                    // which sends a Location header — fetch follows it and
                    // res.url will be the final destination either way.
                    window.location.href = res.url || '../index.html';
                } catch (err) {
                    console.error('Logout failed', err);
                    // Even on a network hiccup, send the user to the login
                    // page rather than leaving a dead button.
                    window.location.href = '../index.html';
                }
            });
        });
    }

    /* ═══════════════════════════════════════════════════════════
       DASHBOARD — click an individual notification to mark it read
       (the page's own inline script only wires up "mark ALL read")
    ═══════════════════════════════════════════════════════════ */
    function initNotificationItemClick() {
        const items = document.querySelectorAll('.notif-item[data-id]');
        if (!items.length) return;

        items.forEach((item) => {
            if (!item.classList.contains('unread')) return;

            item.style.cursor = 'pointer';
            item.addEventListener('click', async () => {
                const id = item.getAttribute('data-id');
                if (!id) return;

                try {
                    const fd = new FormData();
                    fd.append('action', 'mark_read');
                    fd.append('notification_id', id);
                    fd.append('csrf_token', getCSRFToken());

                    const res  = await fetch(`${API_BASE}/notifications.php`, {
                        method      : 'POST',
                        credentials : 'same-origin',
                        body        : fd,
                    });
                    const data = await res.json();

                    if (data.success) {
                        item.classList.remove('unread');
                        item.style.cursor = 'default';

                        // Decrement the topbar/dashboard badge if present
                        document.querySelectorAll('.notif-badge').forEach((badge) => {
                            const n = parseInt(badge.textContent, 10);
                            if (!isNaN(n) && n > 1) {
                                badge.textContent = n - 1;
                            } else {
                                badge.remove();
                            }
                        });
                    }
                } catch (err) {
                    console.error('Mark-read failed', err);
                }
            });
        });
    }

    /* ═══════════════════════════════════════════════════════════
       BELL — no dropdown panel exists for it; simplest useful
       behavior is taking the user to the dashboard, where the full
       notification list already lives
    ═══════════════════════════════════════════════════════════ */
    function initNotifBellNav() {
        const bell = document.getElementById('notifBtn');
        if (!bell) return;

        bell.addEventListener('click', () => {
            if (!window.location.pathname.endsWith('dashboard.php')) {
                window.location.href = 'dashboard.php';
            }
        });
    }

    /* ═══════════════════════════════════════════════════════════
       BOOTSTRAP
    ═══════════════════════════════════════════════════════════ */
    function init() {
        initSidebarToggle();
        initUserDropdown();
        initLogout();
        initNotificationItemClick();
        initNotifBellNav();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
