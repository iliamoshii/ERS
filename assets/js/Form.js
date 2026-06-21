/**
 * Form Page JavaScript - Login & Registration
 * Preserves all original frontend features.
 * Adds: real backend fetch, CSRF tokens, Popup notifications.
 */

(function ($) {
    'use strict';

    /* ═══════════════════════════════════════════════════════════
       CONFIG
    ═══════════════════════════════════════════════════════════ */
    const API_BASE = 'backend/api';

    /* ═══════════════════════════════════════════════════════════
       CSRF TOKEN  (fetched once on load, rotated after each use)
    ═══════════════════════════════════════════════════════════ */
    const CSRFManager = {
        token: '',

        async fetch() {
            try {
                const res  = await window.fetch(`${API_BASE}/csrf.php`, { credentials: 'same-origin' });
                const data = await res.json();
                this.token = data.token || '';
            } catch (e) {
                console.warn('CSRF fetch failed', e);
            }
        },

        get() {
            return this.token;
        },

        set(newToken) {
            if (newToken) this.token = newToken;
        }
    };

    /* ═══════════════════════════════════════════════════════════
       FLOATING ICONS BACKGROUND  (original — unchanged)
    ═══════════════════════════════════════════════════════════ */
    const FloatingIcons = {
        icons: [
            'fa-futbol', 'fa-trophy', 'fa-whistle', 'fa-tshirt',
            'fa-stopwatch', 'fa-medal', 'fa-flag-checkered',
            'fa-user-friends', 'fa-running', 'fa-basketball-ball',
            'fa-volleyball-ball', 'fa-dumbbell'
        ],

        init() {
            const container = document.getElementById('bg-icons-container');
            if (!container) return;
            const count = window.innerWidth > 768 ? 60 : 30;
            for (let i = 0; i < count; i++) this.createIcon(container);
        },

        createIcon(container) {
            const icon       = document.createElement('div');
            const randomIcon = this.icons[Math.floor(Math.random() * this.icons.length)];
            icon.className   = `floating-icon fas ${randomIcon}`;
            icon.style.top              = `${Math.random() * 100}%`;
            icon.style.left             = `${Math.random() * 100}%`;
            icon.style.fontSize         = `${Math.random() * 30 + 15}px`;
            icon.style.animationDuration= `${Math.random() * 7 + 5}s`;
            icon.style.animationDelay   = `${Math.random() * 5}s`;
            icon.style.opacity          = Math.random() * 0.12 + 0.08;
            container.appendChild(icon);
        }
    };

    /* ═══════════════════════════════════════════════════════════
       TAB SWITCHER  (original — unchanged)
    ═══════════════════════════════════════════════════════════ */
    const TabSwitcher = {
        init() {
            document.querySelectorAll('.auth-tab').forEach(tab => {
                tab.addEventListener('click', e => {
                    e.preventDefault();
                    this.switchTab(tab);
                });
            });
        },

        switchTab(clickedTab) {
            const target = clickedTab.getAttribute('data-tab');
            document.querySelectorAll('.auth-tab').forEach(t => t.classList.remove('active'));
            clickedTab.classList.add('active');
            document.querySelectorAll('.auth-form-wrapper').forEach(f => f.classList.remove('active'));
            const targetForm = document.getElementById(`${target}-form`);
            if (targetForm) {
                setTimeout(() => targetForm.classList.add('active'), 100);
            }
        }
    };

    /* ═══════════════════════════════════════════════════════════
       PASSWORD TOGGLE  (original — unchanged)
    ═══════════════════════════════════════════════════════════ */
    const PasswordToggle = {
        init() {
            document.querySelectorAll('.toggle-password').forEach(btn => {
                btn.addEventListener('click', () => this.toggle(btn));
            });
        },

        toggle(btn) {
            const input = document.getElementById(btn.getAttribute('data-target'));
            const icon  = btn.querySelector('i');
            if (!input || !icon) return;
            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            icon.classList.toggle('fa-eye',       !isPassword);
            icon.classList.toggle('fa-eye-slash',  isPassword);
        }
    };

    /* ═══════════════════════════════════════════════════════════
       PASSWORD STRENGTH  (original — unchanged)
    ═══════════════════════════════════════════════════════════ */
    const PasswordStrength = {
        init() {
            const input = document.getElementById('registerPassword');
            if (input) input.addEventListener('input', e => this.update(e.target.value));
        },

        score(pw) {
            let s = 0;
            if (pw.length >= 6)  s++;
            if (pw.length >= 8)  s++;
            if (/[a-z]/.test(pw) && /[A-Z]/.test(pw)) s++;
            if (/\d/.test(pw))   s++;
            if (/[^a-zA-Z0-9]/.test(pw)) s++;
            return s;
        },

        update(pw) {
            const bar  = document.querySelector('.strength-progress');
            const text = document.querySelector('.strength-text');
            if (!bar || !text) return;

            bar.classList.remove('weak', 'medium', 'strong');

            if (!pw) {
                bar.style.width  = '0';
                text.textContent = 'قدرت رمز عبور';
                text.style.color = '';
                return;
            }

            const s = this.score(pw);
            if (s <= 2) {
                bar.classList.add('weak');
                text.textContent = 'ضعیف';
                text.style.color = '#ff3366';
            } else if (s <= 4) {
                bar.classList.add('medium');
                text.textContent = 'متوسط';
                text.style.color = '#ffaa00';
            } else {
                bar.classList.add('strong');
                text.textContent = 'قوی';
                text.style.color = '#39FF14';
            }
        }
    };

    /* ═══════════════════════════════════════════════════════════
       SMOOTH ANIMATIONS  (original — unchanged)
    ═══════════════════════════════════════════════════════════ */
    const SmoothAnimations = {
        init() {
            document.querySelectorAll('.form-control-neon').forEach(input => {
                input.addEventListener('focus', function () {
                    this.parentElement.classList.add('focused');
                });
                input.addEventListener('blur', function () {
                    if (!this.value) this.parentElement.classList.remove('focused');
                });
            });
        }
    };

    /* ═══════════════════════════════════════════════════════════
       PHONE FORMATTER  (original — unchanged)
    ═══════════════════════════════════════════════════════════ */
    const PhoneFormatter = {
        init() {
            document.querySelectorAll('input[type="tel"]').forEach(input => {
                input.addEventListener('input', () => {
                    let v = input.value.replace(/\D/g, '');
                    if (v.length > 11) v = v.slice(0, 11);
                    input.value = v;
                });
            });
        }
    };

    /* ═══════════════════════════════════════════════════════════
       KEYBOARD NAV  (original — unchanged)
    ═══════════════════════════════════════════════════════════ */
    const KeyboardNav = {
        init() {
            document.addEventListener('keydown', e => {
                if (e.altKey && e.key === 'Tab') {
                    e.preventDefault();
                    const tabs    = Array.from(document.querySelectorAll('.auth-tab'));
                    const current = tabs.indexOf(document.querySelector('.auth-tab.active'));
                    tabs[(current + 1) % tabs.length].click();
                }
                if (e.key === 'Escape') {
                    const back = document.querySelector('.back-btn');
                    if (back) window.location.href = back.getAttribute('href');
                }
            });
        }
    };

    /* ═══════════════════════════════════════════════════════════
       SOCIAL LOGIN  (original — unchanged behaviour)
    ═══════════════════════════════════════════════════════════ */
    const SocialLogin = {
        init() {
            document.querySelector('.google-btn')?.addEventListener('click', () => {
                Popup.info('گوگل', 'ورود با گوگل در حال توسعه است.');
            });
            document.querySelector('.apple-btn')?.addEventListener('click', () => {
                Popup.info('اپل', 'ورود با اپل در حال توسعه است.');
            });
        }
    };

    /* ═══════════════════════════════════════════════════════════
       AUTO TAB FROM URL  (original — unchanged)
    ═══════════════════════════════════════════════════════════ */
    const AutoTabSwitch = {
        init() {
            const tab = new URLSearchParams(window.location.search).get('tab');
            if (tab === 'register') {
                setTimeout(() => {
                    document.querySelector('[data-tab="register"]')?.click();
                }, 500);
            }
        }
    };

    /* ═══════════════════════════════════════════════════════════
       AOS  (original — unchanged)
    ═══════════════════════════════════════════════════════════ */
    const AOSAnimation = {
        init() {
            if (typeof AOS !== 'undefined') {
                AOS.init({ duration: 800, easing: 'ease-out-cubic', once: true, offset: 50 });
            }
        }
    };

    /* ═══════════════════════════════════════════════════════════
       LOADING OVERLAY  (original — unchanged)
    ═══════════════════════════════════════════════════════════ */
    const Loader = {
        show() { document.getElementById('loadingOverlay')?.classList.add('active'); },
        hide() { document.getElementById('loadingOverlay')?.classList.remove('active'); }
    };

    /* ═══════════════════════════════════════════════════════════
       FORM VALIDATION (field-level)
    ═══════════════════════════════════════════════════════════ */
    const FieldValidator = {
        init() {
            document.querySelectorAll('.form-control-neon').forEach(input => {
                input.addEventListener('blur', () => this.validate(input));
                input.addEventListener('input', () => {
                    if (input.classList.contains('is-invalid')) this.validate(input);
                });
            });
        },

        validate(field) {
            const value = field.value.trim();
            let ok = true;

            if (field.hasAttribute('required') && !value) ok = false;

            if (ok && field.type === 'email' && value) {
                ok = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
            }

            if (ok && field.type === 'tel' && value) {
                ok = /^09[0-9]{9}$/.test(value);
            }

            if (ok && field.hasAttribute('minlength')) {
                ok = value.length >= parseInt(field.getAttribute('minlength'));
            }

            if (ok && field.getAttribute('pattern')) {
                ok = new RegExp('^' + field.getAttribute('pattern') + '$').test(value);
            }

            field.classList.toggle('is-valid',   ok);
            field.classList.toggle('is-invalid', !ok);
            return ok;
        }
    };

    /* ═══════════════════════════════════════════════════════════
       BACKEND COMMUNICATION  (new)
    ═══════════════════════════════════════════════════════════ */
    const BackendAuth = {

        /**
         * POST to auth.php with form data + CSRF token.
         * Returns parsed JSON or throws.
         */
        async post(action, formData) {
            formData.append('action',     action);
            formData.append('csrf_token', CSRFManager.get());

            const res  = await window.fetch(`${API_BASE}/auth.php?action=${action}`, {
                method      : 'POST',
                credentials : 'same-origin',
                body        : formData,
            });

            const data = await res.json();

            // Rotate CSRF token if server returned a new one
            if (data.csrf_token) CSRFManager.set(data.csrf_token);

            return data;
        },

        /* ── Login ── */
        async login(form) {
            const fd = new FormData(form);
            Loader.show();

            try {
                const data = await this.post('login', fd);

                if (data.success) {
                    Popup.success('خوش آمدید!', data.message || 'در حال انتقال به پنل...');
                    setTimeout(() => { window.location.href = data.redirect || 'user/dashboard.php'; }, 1200);
                } else {
                    Popup.error('ورود ناموفق', data.message || 'اطلاعات وارد شده صحیح نیست.');
                    // Re-fetch CSRF for next attempt
                    await CSRFManager.fetch();
                }
            } catch (err) {
                console.error(err);
                Popup.error('خطای ارتباطی', 'ارتباط با سرور برقرار نشد. لطفاً دوباره تلاش کنید.');
                await CSRFManager.fetch();
            } finally {
                Loader.hide();
            }
        },

        /* ── Register ── */
        async register(form) {
            const fd = new FormData(form);
            Loader.show();

            try {
                const data = await this.post('register', fd);

                if (data.success) {
                    Popup.success('ثبت‌نام موفق!', data.message || 'حساب کاربری ایجاد شد.');
                    setTimeout(() => { window.location.href = data.redirect || 'user/dashboard.php'; }, 1400);
                } else {
                    const msg = data.message || 'خطایی رخ داده است.';
                    Popup.error('خطا در ثبت‌نام', msg);
                    await CSRFManager.fetch();
                }
            } catch (err) {
                console.error(err);
                Popup.error('خطای ارتباطی', 'ارتباط با سرور برقرار نشد. لطفاً دوباره تلاش کنید.');
                await CSRFManager.fetch();
            } finally {
                Loader.hide();
            }
        }
    };

    /* ═══════════════════════════════════════════════════════════
       FORM SUBMIT HANDLERS
    ═══════════════════════════════════════════════════════════ */
    const FormHandlers = {
        init() {
            /* Login */
            document.getElementById('loginForm')?.addEventListener('submit', async e => {
                e.preventDefault();
                const form   = e.target;
                const inputs = form.querySelectorAll('.form-control-neon[required]');
                let   valid  = true;
                inputs.forEach(i => { if (!FieldValidator.validate(i)) valid = false; });
                if (!valid) {
                    Popup.warning('فرم ناقص', 'لطفاً تمام فیلدهای الزامی را پر کنید.');
                    return;
                }
                await BackendAuth.login(form);
            });

            /* Register */
            document.getElementById('registerForm')?.addEventListener('submit', async e => {
                e.preventDefault();
                const form  = e.target;
                const inputs = form.querySelectorAll('.form-control-neon[required]');
                let   valid  = true;
                inputs.forEach(i => { if (!FieldValidator.validate(i)) valid = false; });

                // Password match check (client-side early feedback)
                const pw  = document.getElementById('registerPassword')?.value || '';
                const cpw = document.getElementById('registerPasswordConfirm')?.value || '';
                if (pw !== cpw) {
                    Popup.error('عدم تطابق رمز', 'رمز عبور و تکرار آن یکسان نیستند.');
                    document.getElementById('registerPasswordConfirm')?.classList.add('is-invalid');
                    valid = false;
                }

                const terms = form.querySelector('input[name="terms"]');
                if (terms && !terms.checked) {
                    Popup.warning('قوانین', 'برای ادامه باید قوانین و مقررات را بپذیرید.');
                    valid = false;
                }

                if (!valid) return;
                await BackendAuth.register(form);
            });
        }
    };

    /* ═══════════════════════════════════════════════════════════
       BOOTSTRAP APP
    ═══════════════════════════════════════════════════════════ */
    const App = {
        async init() {
            // Fetch CSRF token early (async, doesn't block UI init)
            CSRFManager.fetch();

            FloatingIcons.init();
            TabSwitcher.init();
            PasswordToggle.init();
            PasswordStrength.init();
            FieldValidator.init();
            SmoothAnimations.init();
            AOSAnimation.init();
            AutoTabSwitch.init();
            PhoneFormatter.init();
            KeyboardNav.init();
            SocialLogin.init();
            FormHandlers.init();

            console.log('🎮 Form Page Initialized');
        }
    };

    // DOMContentLoaded guard
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => App.init());
    } else {
        App.init();
    }

})(typeof jQuery !== 'undefined' ? jQuery : function (fn) { fn({}); });
