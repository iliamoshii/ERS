/**
 * Admin Panel — shared behavior across all admin/*.php pages
 *
 * Generic action contract (covers approve/reject/role-toggle/
 * status-toggle/delete-category/delete-location — anything that's
 * just "click a button, POST some fields, reload on success"):
 *
 *   <button data-admin-post="../backend/api/admin_x.php"
 *           data-admin-fields='{"action":"approve","reservation_id":5}'
 *           data-admin-confirm="این رزرو تأیید شود؟">تأیید</button>
 *
 * First click arms a 4-second confirm window (button text swaps to
 * the confirm message); a second click within that window fires the
 * request. No native confirm() dialogs, stays in the established
 * Popup-toast visual language.
 *
 * Anything involving real form fields (location create/edit, image
 * upload, category create/edit) gets its own dedicated handler below
 * since those can't be reduced to a single data-attribute contract.
 */

(function () {
    'use strict';

    const CONFIRM_WINDOW_MS = 4000;

    function getCSRFToken() {
        try {
            if (typeof CSRF_TOKEN !== 'undefined' && CSRF_TOKEN) return CSRF_TOKEN;
        } catch (e) { /* not defined yet */ }
        const input = document.querySelector('input[name="csrf_token"]');
        return input ? input.value : '';
    }

    /* ═══════════════════════════════════════════════════════════
       GENERIC ACTION BUTTONS (approve/reject/toggle/delete/...)
    ═══════════════════════════════════════════════════════════ */
    function initGenericActions() {
        document.addEventListener('click', async (e) => {
            const btn = e.target.closest('[data-admin-post]');
            if (!btn) return;

            const url         = btn.getAttribute('data-admin-post');
            const fields       = JSON.parse(btn.getAttribute('data-admin-fields') || '{}');
            const confirmMsg   = btn.getAttribute('data-admin-confirm');
            const reloadTarget = btn.getAttribute('data-admin-reload'); // optional custom redirect

            // First click — arm the confirm window
            if (confirmMsg && !btn.classList.contains('confirm-pending')) {
                btn.dataset.originalHtml = btn.innerHTML;
                btn.classList.add('confirm-pending');
                btn.innerHTML = `<i class="fas fa-question-circle"></i> ${confirmMsg}`;

                btn._confirmTimer = setTimeout(() => {
                    btn.classList.remove('confirm-pending');
                    if (btn.dataset.originalHtml) btn.innerHTML = btn.dataset.originalHtml;
                }, CONFIRM_WINDOW_MS);

                return;
            }

            if (btn._confirmTimer) clearTimeout(btn._confirmTimer);
            btn.disabled = true;

            try {
                const fd = new FormData();
                Object.entries(fields).forEach(([k, v]) => fd.append(k, v));
                fd.append('csrf_token', getCSRFToken());

                const res  = await fetch(url, { method: 'POST', credentials: 'same-origin', body: fd });
                const data = await res.json();

                if (data.success) {
                    window.Popup?.success('انجام شد', data.message || '');
                    setTimeout(() => {
                        window.location.href = reloadTarget || window.location.href;
                    }, 900);
                } else {
                    window.Popup?.error('خطا', data.message || 'مشکلی پیش آمد.');
                    btn.disabled = false;
                    btn.classList.remove('confirm-pending');
                    if (btn.dataset.originalHtml) btn.innerHTML = btn.dataset.originalHtml;
                }
            } catch (err) {
                console.error('Admin action failed', err);
                window.Popup?.error('خطای ارتباطی', 'ارتباط با سرور برقرار نشد.');
                btn.disabled = false;
            }
        });
    }

    /* ═══════════════════════════════════════════════════════════
       LOCATION FORM (create + edit share the same markup/handler)
    ═══════════════════════════════════════════════════════════ */
    function initLocationForm() {
        const form = document.getElementById('locationForm');
        if (!form) return;

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const submitBtn = form.querySelector('[type="submit"]');
            const original  = submitBtn?.innerHTML;
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> در حال ذخیره...';
            }

            try {
                const fd = new FormData(form);
                fd.set('csrf_token', getCSRFToken());

                const res  = await fetch('../backend/api/admin_locations.php', {
                    method: 'POST', credentials: 'same-origin', body: fd,
                });
                const data = await res.json();

                if (data.success) {
                    window.Popup?.success('ذخیره شد', data.message || '');
                    const isCreate = form.dataset.mode === 'create' && data.id;
                    setTimeout(() => {
                        window.location.href = isCreate
                            ? `location-form.php?id=${data.id}`
                            : window.location.href;
                    }, 1000);
                } else {
                    window.Popup?.error('خطا', data.message || 'مشکلی پیش آمد.');
                }
            } catch (err) {
                console.error('Location save failed', err);
                window.Popup?.error('خطای ارتباطی', 'ارتباط با سرور برقرار نشد.');
            } finally {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = original;
                }
            }
        });
    }

    /* ═══════════════════════════════════════════════════════════
       IMAGE MANAGER (location-form.php — upload / delete / set-primary)
    ═══════════════════════════════════════════════════════════ */
    function initImageManager() {
        const uploadInput = document.getElementById('imageUploadInput');
        const grid         = document.getElementById('imageGrid');
        const locationId   = document.body.dataset.locationId;

        if (!uploadInput || !grid || !locationId) return;

        uploadInput.addEventListener('change', async () => {
            const file = uploadInput.files[0];
            if (!file) return;

            try {
                const fd = new FormData();
                fd.append('action', 'add_image');
                fd.append('location_id', locationId);
                fd.append('image', file);
                fd.append('csrf_token', getCSRFToken());

                const res  = await fetch('../backend/api/admin_locations.php', {
                    method: 'POST', credentials: 'same-origin', body: fd,
                });
                const data = await res.json();

                if (data.success) {
                    window.Popup?.success('تصویر اضافه شد', '');
                    setTimeout(() => window.location.reload(), 700);
                } else {
                    window.Popup?.error('خطا', data.message || '');
                }
            } catch (err) {
                window.Popup?.error('خطای ارتباطی', 'آپلود انجام نشد.');
            } finally {
                uploadInput.value = '';
            }
        });

        // Delete / set-primary buttons inside the image grid use the
        // same generic [data-admin-post] contract — nothing extra needed.
    }

    /* ═══════════════════════════════════════════════════════════
       CATEGORY INLINE CREATE / EDIT FORM (categories.php)
    ═══════════════════════════════════════════════════════════ */
    function initCategoryForm() {
        const form = document.getElementById('categoryForm');
        if (!form) return;

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const submitBtn = form.querySelector('[type="submit"]');
            const original  = submitBtn?.innerHTML;
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            }

            try {
                const fd = new FormData(form);
                fd.set('csrf_token', getCSRFToken());

                const res  = await fetch('../backend/api/admin_categories.php', {
                    method: 'POST', credentials: 'same-origin', body: fd,
                });
                const data = await res.json();

                if (data.success) {
                    window.Popup?.success('ذخیره شد', data.message || '');
                    setTimeout(() => window.location.reload(), 800);
                } else {
                    window.Popup?.error('خطا', data.message || 'مشکلی پیش آمد.');
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = original;
                    }
                }
            } catch (err) {
                window.Popup?.error('خطای ارتباطی', 'ارتباط با سرور برقرار نشد.');
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = original;
                }
            }
        });
    }

    /* ═══════════════════════════════════════════════════════════
       BOOTSTRAP
    ═══════════════════════════════════════════════════════════ */
    function init() {
        initGenericActions();
        initLocationForm();
        initImageManager();
        initCategoryForm();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();