/**
 * Contact Page JavaScript - Form Validation & Interactivity
 * Modern contact form with validation and animations.
 *
 * Only ContactForm.handleSubmit() was changed from the original:
 * the setTimeout-based fake submission is replaced with a real
 * fetch() to backend/api/contact.php, with CSRF token handling.
 * Every other module is unchanged.
 */

(function($) {
    'use strict';

    const API_BASE = 'backend/api';
    let csrfToken = '';

    async function fetchCsrfToken() {
        try {
            const res  = await fetch(`${API_BASE}/csrf.php`, { credentials: 'same-origin' });
            const data = await res.json();
            csrfToken  = data.token || '';
        } catch (e) {
            console.warn('CSRF fetch failed', e);
        }
    }

    // ==================== Form Validation ====================
    const ContactForm = {
        init: function() {
            const $form = $('#contactForm');

            if ($form.length) {
                $form.on('submit', function(e) {
                    e.preventDefault();
                    ContactForm.handleSubmit($(this));
                });

                $form.find('input, textarea, select').on('blur', function() {
                    ContactForm.validateField($(this));
                });
            }
        },

        validateField: function($field) {
            const value = $field.val().trim();
            const fieldName = $field.attr('name');
            let isValid = true;
            let errorMessage = '';

            if ($field.prop('required') && !value) {
                isValid = false;
                errorMessage = 'این فیلد الزامی است';
            }

            if (fieldName === 'email' && value) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(value)) {
                    isValid = false;
                    errorMessage = 'فرمت ایمیل صحیح نیست';
                }
            }

            if (fieldName === 'phone' && value) {
                const phoneRegex = /^09[0-9]{9}$/;
                if (!phoneRegex.test(value.replace(/\s/g, ''))) {
                    isValid = false;
                    errorMessage = 'شماره موبایل باید ۱۱ رقم باشد';
                }
            }

            if (isValid) {
                $field.removeClass('is-invalid').addClass('is-valid');
                $field.next('.error-message').remove();
            } else {
                $field.removeClass('is-valid').addClass('is-invalid');
                if (!$field.next('.error-message').length) {
                    $field.after(`<div class="error-message">${errorMessage}</div>`);
                }
            }

            return isValid;
        },

        validateForm: function($form) {
            let isValid = true;
            $form.find('input[required], textarea[required], select[required]').each(function() {
                if (!ContactForm.validateField($(this))) {
                    isValid = false;
                }
            });
            return isValid;
        },

        handleSubmit: async function($form) {
            if (!this.validateForm($form)) {
                return;
            }

            // Send the visible Persian label as the subject, not the raw
            // English <option value>, so it reads clearly in the admin inbox.
            const $subjectSelect = $form.find('[name="subject"]');
            const subjectLabel   = $subjectSelect.find('option:selected').text();

            const $submitBtn   = $form.find('.btn-submit');
            const originalText = $submitBtn.find('.btn-text').text();
            $submitBtn.prop('disabled', true);
            $submitBtn.find('.btn-text').text('در حال ارسال...');
            $submitBtn.find('.btn-icon i').removeClass('fa-paper-plane').addClass('fa-spinner fa-spin');

            try {
                if (!csrfToken) {
                    await fetchCsrfToken();
                }

                const fd = new FormData();
                fd.append('name',       $form.find('[name="name"]').val());
                fd.append('phone',      $form.find('[name="phone"]').val());
                fd.append('email',      $form.find('[name="email"]').val());
                fd.append('subject',    subjectLabel);
                fd.append('message',    $form.find('[name="message"]').val());
                fd.append('csrf_token', csrfToken);

                const res  = await fetch(`${API_BASE}/contact.php`, {
                    method: 'POST', credentials: 'same-origin', body: fd,
                });
                const data = await res.json();

                if (data.success) {
                    this.showSuccess(data.message);
                    $form[0].reset();
                    $form.find('.is-valid, .is-invalid').removeClass('is-valid is-invalid');
                    $('#charCount').text('۰');
                    FormAutoSave.clearDraft();
                } else {
                    this.showError(data.message);
                    await fetchCsrfToken();
                }
            } catch (err) {
                console.error('Contact form submit failed', err);
                this.showError('ارتباط با سرور برقرار نشد. لطفاً اتصال اینترنت خود را بررسی کنید.');
            } finally {
                $submitBtn.prop('disabled', false);
                $submitBtn.find('.btn-text').text(originalText);
                $submitBtn.find('.btn-icon i').removeClass('fa-spinner fa-spin').addClass('fa-paper-plane');
            }
        },

        showSuccess: function(message) {
            const $success = $('#successMessage');
            const $error   = $('#errorMessage');

            if (message) { $success.find('p').text(message); }

            $error.hide();
            $success.fadeIn(400);

            if (window.Popup) {
                Popup.success('ارسال شد', message || 'پیام شما با موفقیت ارسال شد.');
            }

            setTimeout(() => { $success.fadeOut(400); }, 5000);
        },

        showError: function(message) {
            const $success = $('#successMessage');
            const $error   = $('#errorMessage');

            if (message) { $error.find('p').text(message); }

            $success.hide();
            $error.fadeIn(400);

            if (window.Popup) {
                Popup.error('خطا', message || 'ارسال پیام ناموفق بود.');
            }

            setTimeout(() => { $error.fadeOut(400); }, 5000);
        }
    };

    // ==================== Character Counter ====================
    const CharacterCounter = {
        init: function() {
            const $textarea = $('textarea[name="message"]');
            const maxLength = 500;

            if ($textarea.length) {
                $textarea.attr('maxlength', maxLength);

                $textarea.on('input', function() {
                    const currentLength = $(this).val().length;
                    $('#charCount').text(CharacterCounter.toPersianNumber(currentLength));

                    const $counter = $('.char-counter');
                    if (currentLength > maxLength * 0.9) {
                        $counter.css('color', '#ff3b30');
                    } else if (currentLength > maxLength * 0.7) {
                        $counter.css('color', '#ffaa00');
                    } else {
                        $counter.css('color', 'rgba(255, 255, 255, 0.5)');
                    }
                });
            }
        },

        toPersianNumber: function(num) {
            const persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
            return num.toString().split('').map(n => persian[n] || n).join('');
        }
    };

    // ==================== Smooth Scroll ====================
    const SmoothScroll = {
        init: function() {
            $('a[href^="#"]').on('click', function(e) {
                const target = $(this.getAttribute('href'));
                if (target.length) {
                    e.preventDefault();
                    $('html, body').stop().animate({ scrollTop: target.offset().top - 100 }, 800);
                }
            });
        }
    };

    // ==================== Map Directions ====================
    const MapDirections = {
        init: function() {
            $('.btn-directions').on('click', function() {
                const destination = 'Tehran,Iran';
                const url = `https://www.google.com/maps/dir/?api=1&destination=${encodeURIComponent(destination)}`;
                window.open(url, '_blank');
            });
        }
    };

    // ==================== Social Links ====================
    const SocialLinks = {
        init: function() {
            $('.social-item.instagram').on('click', function(e) {
                e.preventDefault();
                window.open('https://instagram.com/stadium', '_blank');
            });
            $('.social-item.telegram').on('click', function(e) {
                e.preventDefault();
                window.open('https://t.me/stadium', '_blank');
            });
            $('.social-item.whatsapp').on('click', function(e) {
                e.preventDefault();
                const phone = '989123456789';
                const message = encodeURIComponent('سلام، در مورد خدمات شما سوال دارم');
                window.open(`https://wa.me/${phone}?text=${message}`, '_blank');
            });
            $('.social-item.twitter').on('click', function(e) {
                e.preventDefault();
                window.open('https://twitter.com/stadium', '_blank');
            });
        }
    };

    // ==================== Phone Number Formatter ====================
    const PhoneFormatter = {
        init: function() {
            $('input[name="phone"]').on('input', function() {
                let value = $(this).val().replace(/\D/g, '');
                if (value.length > 11) { value = value.slice(0, 11); }
                $(this).val(value);
            });
        }
    };

    // ==================== Info Card Hover Effects ====================
    const InfoCardEffects = {
        init: function() {
            $('.info-card').on('mouseenter', function() {
                $(this).find('.icon-wrapper').addClass('animate-bounce');
            });
            $('.info-card').on('mouseleave', function() {
                $(this).find('.icon-wrapper').removeClass('animate-bounce');
            });
        }
    };

    // ==================== FAQ Toggle ====================
    const FAQToggle = {
        init: function() {
            $('.faq-item').on('click', function() {
                $(this).toggleClass('active');
                $(this).find('.faq-answer').slideToggle(300);
            });
            $('.faq-item:not(:first) .faq-answer').hide();
            $('.faq-item:first').addClass('active');
        }
    };

    // ==================== Contact Link Actions ====================
    const ContactLinks = {
        init: function() {
            $('a[href^="tel:"]').on('click', function(e) {
                console.log('Phone link clicked:', $(this).attr('href'));
            });
            $('a[href^="mailto:"]').on('click', function(e) {
                console.log('Email link clicked:', $(this).attr('href'));
            });
        }
    };

    // ==================== Quick Links ====================
    const QuickLinks = {
        init: function() {
            $('.quick-link').on('click', function(e) {
                const href = $(this).attr('href');
                if (href.startsWith('#')) {
                    e.preventDefault();
                    const target = $(href);
                    if (target.length) {
                        $('html, body').animate({ scrollTop: target.offset().top - 100 }, 800);
                    }
                }
            });
        }
    };

    // ==================== Form Auto-save ====================
    const FormAutoSave = {
        storageKey: 'contactFormDraft',

        init: function() {
            const $form = $('#contactForm');
            this.loadDraft($form);
            $form.find('input, textarea, select').on('input change', $.debounce(1000, function() {
                FormAutoSave.saveDraft($form);
            }));
        },

        saveDraft: function($form) {
            const formData = {};
            $form.find('input, textarea, select').each(function() {
                const name = $(this).attr('name');
                if (name) { formData[name] = $(this).val(); }
            });
            localStorage.setItem(this.storageKey, JSON.stringify(formData));
        },

        loadDraft: function($form) {
            const savedData = localStorage.getItem(this.storageKey);
            if (savedData) {
                try {
                    const formData = JSON.parse(savedData);
                    Object.keys(formData).forEach(name => {
                        $form.find(`[name="${name}"]`).val(formData[name]);
                    });
                } catch (e) {
                    console.error('Error loading draft:', e);
                }
            }
        },

        clearDraft: function() {
            localStorage.removeItem(this.storageKey);
        }
    };

    // ==================== Debounce Utility ====================
    $.debounce = function(delay, fn) {
        let timeoutId;
        return function(...args) {
            clearTimeout(timeoutId);
            timeoutId = setTimeout(() => fn.apply(this, args), delay);
        };
    };

    // ==================== Initialize All Features ====================
    const App = {
        init: function() {
            fetchCsrfToken();
            ContactForm.init();
            CharacterCounter.init();
            SmoothScroll.init();
            MapDirections.init();
            SocialLinks.init();
            PhoneFormatter.init();
            InfoCardEffects.init();
            FAQToggle.init();
            ContactLinks.init();
            QuickLinks.init();
            FormAutoSave.init();
        }
    };

    $(document).ready(() => {
        App.init();
    });

})(jQuery);

// ==================== Add Animation CSS ====================
const style = document.createElement('style');
style.textContent = `
    .animate-bounce { animation: bounceAnimation 0.6s ease; }
    @keyframes bounceAnimation { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.1); } }
    .error-message { color: #ff3b30; font-size: 12px; margin-top: 5px; animation: fadeInDown 0.3s; }
    @keyframes fadeInDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
    .form-control-modern.is-invalid { border-color: #ff3b30 !important; animation: shake 0.5s; }
    .form-control-modern.is-valid { border-color: #39FF14 !important; }
    @keyframes shake { 0%, 100% { transform: translateX(0); } 25% { transform: translateX(-10px); } 75% { transform: translateX(10px); } }
`;
document.head.appendChild(style);
