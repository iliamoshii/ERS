/**
 * Details Page JavaScript - Stadium Booking & Gallery
 *
 * Changes from the original mockup:
 *  - TimeSlotSelection no longer reads static markup; it fetches real
 *    availability per selected date from backend/api/locations.php.
 *  - BookNowButton submits a real reservation to backend/api/booking.php
 *    instead of redirecting to a non-existent payment.html.
 *  - New CommentSubmission module (not in the original — there was no
 *    review system in the mockup at all).
 *  - No Jalali calendar conversion: dates are real Gregorian dates
 *    (server-generated in details.php) with Persian weekday names and
 *    the standard Persian-language names for Gregorian months. A full
 *    Jalali implementation was out of scope for this pass.
 *  - The original mockup's custom scroll-based "sticky sidebar" JS has
 *    been removed. It manually toggled the sidebar between position:
 *    absolute and position: sticky using document-relative offsets
 *    applied as offset-parent-relative values — a coordinate mismatch
 *    that caused it to jump and overlap surrounding content on scroll.
 *    details.css already implements the same sticky/static behavior
 *    correctly and responsively via plain CSS (position: sticky on
 *    desktop, position: static below 991px), so no JS is needed here.
 */

(function($) {
    'use strict';

    const API_BASE = 'backend/api';
    const LOCATION_ID  = window.LOCATION_ID;
    const IS_LOGGED_IN = window.IS_LOGGED_IN;
    const CSRF_TOKEN    = window.CSRF_TOKEN;

    // ==================== Gallery Slider ====================
    const GallerySlider = {
        init: function() {
            const galleryThumbs = new Swiper('.galleryThumbs', {
                spaceBetween: 10,
                slidesPerView: 3,
                freeMode: true,
                watchSlidesProgress: true,
            });

            const galleryMain = new Swiper('.galleryMain', {
                spaceBetween: 10,
                navigation: { nextEl: '.swiper-button-next', prevEl: '.swiper-button-prev' },
                thumbs: { swiper: galleryThumbs },
                on: {
                    slideChange: function() { GallerySlider.updateCounter(this); },
                    init: function() { GallerySlider.updateCounter(this); }
                }
            });
        },
        updateCounter: function(swiper) {
            $('.image-counter').text(`${swiper.activeIndex + 1} / ${swiper.slides.length}`);
        }
    };

    // ==================== Date Selection ====================
    const DateSelection = {
        selectedDate: null,

        init: function() {
            $('.date-item').on('click', function() {
                $('.date-item').removeClass('active');
                $(this).addClass('active');
                DateSelection.selectedDate = $(this).data('date');
                TimeSlotSelection.loadSlotsForDate(DateSelection.selectedDate);
            });

            $('#prevDate').on('click', function() {
                $('#dateScroll').animate({ scrollLeft: '-=200' }, 300);
            });
            $('#nextDate').on('click', function() {
                $('#dateScroll').animate({ scrollLeft: '+=200' }, 300);
            });

            // Auto-select the first (today) date on load
            const $first = $('.date-item').first();
            if ($first.length) {
                this.selectedDate = $first.data('date');
                TimeSlotSelection.loadSlotsForDate(this.selectedDate);
            }
        },

        getSelectedDateText: function() {
            const $selected = $('.date-item.active');
            if ($selected.length) {
                return `${$selected.find('.day-name').text()} ${$selected.find('.day-number').text()} ${$selected.find('.month-name').text()}`;
            }
            return '-';
        }
    };

    // ==================== Time Slot Selection (real data) ====================
    const TimeSlotSelection = {
        selectedSlot: null,
        selectedPrice: 0,

        async loadSlotsForDate(date) {
            this.selectedSlot = null;
            this.selectedPrice = 0;
            BookingSummary.update();

            const $grid = $('#timeSlots');
            $grid.html('<p class="text-muted" style="padding:20px; text-align:center; width:100%;"><i class="fas fa-spinner fa-spin"></i> در حال بارگذاری سانس‌ها...</p>');

            try {
                const res  = await fetch(`${API_BASE}/locations.php?action=slots&id=${LOCATION_ID}&date=${date}`);
                const data = await res.json();

                if (!data.success) {
                    $grid.html(`<p class="text-muted" style="padding:20px; text-align:center; width:100%;">${data.message || 'خطا در دریافت سانس‌ها'}</p>`);
                    return;
                }

                if (!data.data || data.data.length === 0) {
                    $grid.html('<p class="text-muted" style="padding:20px; text-align:center; width:100%;">برای این روز سانسی تعریف نشده است.</p>');
                    return;
                }

                $grid.html(data.data.map(slot => this.renderSlot(slot)).join(''));
                this.bindClicks();
            } catch (err) {
                console.error(err);
                $grid.html('<p class="text-muted" style="padding:20px; text-align:center; width:100%;">خطا در ارتباط با سرور.</p>');
            }
        },

        renderSlot(slot) {
            if (slot.is_reserved) {
                return `<button class="time-slot reserved" disabled>
                            <span class="time">${slot.start_time} - ${slot.end_time}</span>
                            <span class="price">رزرو شده</span>
                        </button>`;
            }
            return `<button class="time-slot" data-time="${slot.start_time}-${slot.end_time}"
                             data-start="${slot.start_time}" data-price="${slot.price}">
                        <span class="time">${slot.start_time} - ${slot.end_time}</span>
                        <span class="price">${Number(slot.price).toLocaleString('fa-IR')} تومان</span>
                    </button>`;
        },

        bindClicks() {
            $('.time-slot:not(.reserved)').off('click').on('click', function() {
                $('.time-slot').removeClass('selected');
                $(this).addClass('selected');
                TimeSlotSelection.selectedSlot  = $(this).data('start');
                TimeSlotSelection.selectedPrice = parseInt($(this).data('price'), 10);
                BookingSummary.update();
            });
        },

        getSelectedTime() { return this.selectedSlot || '-'; },
        getPrice()        { return this.selectedPrice; }
    };

    // ==================== Booking Summary ====================
    const BookingSummary = {
        update: function() {
            const hasDate = DateSelection.selectedDate !== null;
            const hasTime = TimeSlotSelection.selectedSlot !== null;
            hasDate && hasTime ? this.showSummary() : this.showEmptyState();
        },

        showSummary: function() {
            $('#summaryContent .empty-state').hide();
            $('#selectedInfo').show();

            $('#selectedDate').text(DateSelection.getSelectedDateText());
            $('#selectedTime').text(TimeSlotSelection.getSelectedTime());

            const price = TimeSlotSelection.getPrice();
            $('#slotPrice').text(this.formatPrice(price) + ' تومان');
            $('#totalPrice').text(this.formatPrice(price) + ' تومان');
        },

        showEmptyState: function() {
            $('#summaryContent .empty-state').show();
            $('#selectedInfo').hide();
        },

        formatPrice: function(price) {
            return price.toLocaleString('fa-IR');
        }
    };

    // ==================== Favorite Button ====================
    const FavoriteButton = {
        init: function() {
            $('#btnFavorite').on('click', function() {
                $(this).toggleClass('active');
                const $icon = $(this).find('i');
                if ($(this).hasClass('active')) {
                    $icon.removeClass('far').addClass('fas');
                    FavoriteButton.addToFavorites();
                } else {
                    $icon.removeClass('fas').addClass('far');
                    FavoriteButton.removeFromFavorites();
                }
            });

            const favorites = FavoriteButton.getFavorites();
            if (favorites.includes(String(LOCATION_ID))) {
                $('#btnFavorite').addClass('active');
                $('#btnFavorite i').removeClass('far').addClass('fas');
            }
        },

        getFavorites: function() {
            const favorites = localStorage.getItem('favorites');
            return favorites ? JSON.parse(favorites) : [];
        },

        addToFavorites: function() {
            const favorites = this.getFavorites();
            const id = String(LOCATION_ID);
            if (!favorites.includes(id)) {
                favorites.push(id);
                localStorage.setItem('favorites', JSON.stringify(favorites));
            }
            window.Popup?.success('علاقه‌مندی‌ها', 'به علاقه‌مندی‌ها اضافه شد.');
        },

        removeFromFavorites: function() {
            let favorites = this.getFavorites().filter(id => id !== String(LOCATION_ID));
            localStorage.setItem('favorites', JSON.stringify(favorites));
            window.Popup?.info('علاقه‌مندی‌ها', 'از علاقه‌مندی‌ها حذف شد.');
        }
    };

    // ==================== Share Button ====================
    const ShareButton = {
        init: function() {
            $('#btnShare').on('click', function() {
                if (navigator.share) {
                    navigator.share({ title: document.title, url: window.location.href }).catch(() => {});
                } else {
                    ShareButton.copyToClipboard();
                }
            });
        },
        copyToClipboard: function() {
            const url = window.location.href;
            if (navigator.clipboard) {
                navigator.clipboard.writeText(url).then(() => window.Popup?.success('کپی شد', 'لینک صفحه کپی شد.'));
            }
        }
    };

    // ==================== WhatsApp Contact ====================
    const ContactButton = {
        init: function() {
            $('#btnWhatsapp').on('click', function() {
                const phone  = (window.LOCATION_PHONE || '').replace(/\D/g, '');
                const target = phone ? `98${phone.replace(/^0/, '')}` : '989123456789';
                const message = encodeURIComponent('سلام، در مورد رزرو این مکان سوال دارم');
                window.open(`https://wa.me/${target}?text=${message}`, '_blank');
            });
        }
    };

    // ==================== Book Now Button (real booking) ====================
    const BookNowButton = {
        init: function() {
            $('#btnBookNow').on('click', async function() {
                const date  = DateSelection.selectedDate;
                const start = TimeSlotSelection.selectedSlot;

                if (!date || !start) {
                    window.Popup?.warning('انتخاب ناقص', 'لطفاً تاریخ و زمان را انتخاب کنید.');
                    return;
                }

                if (!IS_LOGGED_IN) {
                    window.Popup?.info('ورود لازم است', 'برای ثبت رزرو ابتدا وارد حساب کاربری خود شوید.');
                    setTimeout(() => { window.location.href = 'Form.html'; }, 1500);
                    return;
                }

                const $btn = $(this);
                $btn.prop('disabled', true).find('span').last().text('در حال ثبت...');

                try {
                    const fd = new FormData();
                    fd.append('action', 'create');
                    fd.append('location_id', LOCATION_ID);
                    fd.append('reservation_date', date);
                    fd.append('start_time', start);
                    fd.append('csrf_token', CSRF_TOKEN);

                    const res  = await fetch(`${API_BASE}/booking.php`, {
                        method: 'POST', credentials: 'same-origin', body: fd,
                    });
                    const data = await res.json();

                    if (data.success) {
                        window.Popup?.success('رزرو ثبت شد', data.message);
                        setTimeout(() => { window.location.href = 'user/reservations.php'; }, 1800);
                    } else {
                        window.Popup?.error('خطا', data.message || 'ثبت رزرو ناموفق بود.');
                    }
                } catch (err) {
                    console.error(err);
                    window.Popup?.error('خطای ارتباطی', 'ارتباط با سرور برقرار نشد.');
                } finally {
                    $btn.prop('disabled', false).find('span').last().text('ثبت درخواست رزرو');
                }
            });
        }
    };

    // ==================== Comment Submission (new) ====================
    const CommentSubmission = {
        rating: 0,

        init: function() {
            $('#starsInput i').on('click', function() {
                CommentSubmission.rating = parseInt($(this).data('val'), 10);
                $('#starsInput i').each(function() {
                    const val = parseInt($(this).data('val'), 10);
                    $(this).toggleClass('active fas', val <= CommentSubmission.rating)
                        .toggleClass('far', val > CommentSubmission.rating);
                });
            });

            $('#submitCommentBtn').on('click', async function() {
                const body = $('#commentBody').val().trim();
                if (body.length < 5) {
                    window.Popup?.warning('متن کوتاه است', 'لطفاً حداقل ۵ کاراکتر بنویسید.');
                    return;
                }

                const $btn = $(this);
                $btn.prop('disabled', true);

                try {
                    const fd = new FormData();
                    fd.append('action', 'submit');
                    fd.append('location_id', LOCATION_ID);
                    fd.append('body', body);
                    if (CommentSubmission.rating > 0) fd.append('rating', CommentSubmission.rating);
                    fd.append('csrf_token', CSRF_TOKEN);

                    const res  = await fetch(`${API_BASE}/comments.php?action=submit`, {
                        method: 'POST', credentials: 'same-origin', body: fd,
                    });
                    const data = await res.json();

                    if (data.success) {
                        window.Popup?.success('ثبت شد', data.message);
                        $('#commentBody').val('');
                        $('#starsInput i').removeClass('active fas').addClass('far');
                        CommentSubmission.rating = 0;
                    } else {
                        window.Popup?.error('خطا', data.message || 'ثبت نظر ناموفق بود.');
                    }
                } catch (err) {
                    window.Popup?.error('خطای ارتباطی', 'ارتباط با سرور برقرار نشد.');
                } finally {
                    $btn.prop('disabled', false);
                }
            });
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

    // ==================== Image Lazy Loading ====================
    const LazyLoad = {
        init: function() {
            if ('IntersectionObserver' in window) {
                const imageObserver = new IntersectionObserver((entries, observer) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting && entry.target.dataset.src) {
                            entry.target.src = entry.target.dataset.src;
                            entry.target.removeAttribute('data-src');
                            observer.unobserve(entry.target);
                        }
                    });
                });
                document.querySelectorAll('img[data-src]').forEach(img => imageObserver.observe(img));
            }
        }
    };

    // ==================== Initialize All Features ====================
    const App = {
        init: function() {
            if (typeof Swiper !== 'undefined') GallerySlider.init();

            DateSelection.init();
            BookingSummary.update();

            FavoriteButton.init();
            ShareButton.init();
            BookNowButton.init();
            ContactButton.init();
            CommentSubmission.init();

            SmoothScroll.init();
            LazyLoad.init();
        }
    };

    $(document).ready(() => { App.init(); });
    $(window).on('load', () => { if (typeof AOS !== 'undefined') AOS.refresh(); });

})(jQuery);

// ==================== Animation CSS ====================
const style = document.createElement('style');
style.textContent = `
    @keyframes bounceIn { from { opacity: 0; transform: scale(0.8); } to { opacity: 1; transform: scale(1); } }
    .btn-action.active { animation: bounceIn 0.3s ease; }
    .time-slot.selected { animation: bounceIn 0.3s ease; }
`;
document.head.appendChild(style);