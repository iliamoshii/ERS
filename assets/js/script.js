/**
 * Stadium Reservation System - Main JavaScript
 * Enhanced UI/UX with smooth animations and interactions
 */

(function($) {
    'use strict';

    // ==================== Utility Functions ====================
    const utils = {
        // Convert Persian/Arabic numbers to English
        persianToEnglish: (str) => {
            const persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
            const english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
            
            for (let i = 0; i < 10; i++) {
                str = str.toString().replace(new RegExp(persian[i], 'g'), english[i]);
            }
            return str;
        },

        // Convert English numbers to Persian
        englishToPersian: (str) => {
            const persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
            const english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
            
            for (let i = 0; i < 10; i++) {
                str = str.toString().replace(new RegExp(english[i], 'g'), persian[i]);
            }
            return str;
        },

        // Debounce function
        debounce: (func, wait) => {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }
    };

    // ==================== Preloader ====================
    const preloader = {
        init: () => {
            $(window).on('load', function() {
                $('.preloader').fadeOut(800, function() {
                    $(this).remove();
                });
            });

            // Fallback: Remove preloader after 3 seconds
            setTimeout(() => {
                if ($('.preloader').length) {
                    $('.preloader').fadeOut(500, function() {
                        $(this).remove();
                    });
                }
            }, 3000);
        }
    };

    // ==================== Header Scroll Effect ====================
    const header = {
        init: () => {
            const $header = $('.main-header');
            const scrollThreshold = 100;

            $(window).on('scroll', utils.debounce(() => {
                if ($(window).scrollTop() > scrollThreshold) {
                    $header.addClass('scrolled');
                } else {
                    $header.removeClass('scrolled');
                }
            }, 10));
        }
    };

    // ==================== Mobile Menu ====================
    const mobileMenu = {
        init: () => {
            const $menu = $('#mobileMenu');
            const $toggle = $('#mobileMenuToggle');
            const $close = $('#mobileMenuClose');
            const $overlay = $menu.find('.mobile-menu-overlay');
            const $links = $menu.find('.mobile-navigation a');

            // Open menu
            $toggle.on('click', () => {
                $menu.addClass('active');
                $('body').css('overflow', 'hidden');
            });

            // Close menu
            const closeMenu = () => {
                $menu.removeClass('active');
                $('body').css('overflow', '');
            };

            $close.on('click', closeMenu);
            $overlay.on('click', closeMenu);
            $links.on('click', closeMenu);

            // Close on Escape key
            $(document).on('keydown', (e) => {
                if (e.key === 'Escape' && $menu.hasClass('active')) {
                    closeMenu();
                }
            });
        }
    };

    // ==================== Smooth Scroll ====================
    const smoothScroll = {
        init: () => {
            $('a[href^="#"]').on('click', function(e) {
                const href = $(this).attr('href');
                
                if (href === '#' || href === '') return;

                const $target = $(href);
                
                if ($target.length) {
                    e.preventDefault();
                    const headerHeight = $('.main-header').outerHeight();
                    const targetOffset = $target.offset().top - headerHeight - 20;

                    $('html, body').animate({
                        scrollTop: targetOffset
                    }, 800, 'swing');
                }
            });
        }
    };

    // ==================== Counter Animation ====================
    const counterAnimation = {
        init: () => {
            const $counters = $('.counter-number');
            let animated = false;

            const animateCounters = () => {
                $counters.each(function() {
                    const $this = $(this);
                    const targetText = $this.attr('data-count');
                    const target = parseInt(utils.persianToEnglish(targetText));
                    const duration = 2000;
                    const increment = target / (duration / 16);
                    let current = 0;

                    const updateCounter = () => {
                        current += increment;
                        if (current < target) {
                            $this.text(utils.englishToPersian(Math.ceil(current).toString()));
                            requestAnimationFrame(updateCounter);
                        } else {
                            $this.text(targetText);
                        }
                    };

                    updateCounter();
                });
            };

            // Trigger animation when counter section is in view
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting && !animated) {
                        animated = true;
                        setTimeout(animateCounters, 200);
                    }
                });
            }, {
                threshold: 0.5
            });

            const counterSection = document.querySelector('.counter-section');
            if (counterSection) {
                observer.observe(counterSection);
            }
        }
    };

    // ==================== Gallery Carousel ====================
        const galleryCarousel = {
            init: () => {
                if ($('.gallery-carousel').length) {
                    $('.gallery-carousel').slick({
                        dots: true,
                        infinite: true,
                        speed: 600,
                        slidesToShow: 3,
                        slidesToScroll: 1,
                        autoplay: true,
                        autoplaySpeed: 4000,
                        rtl: true,
                        arrows: true,
                        pauseOnHover: true,
                        responsive: [
                            {
                                breakpoint: 1024,
                                settings: {
                                    slidesToShow: 2,
                                    slidesToScroll: 1
                                }
                            },
                            {
                                breakpoint: 768,
                                settings: {
                                    slidesToShow: 1,
                                    slidesToScroll: 1,
                                    arrows: false
                                }
                            }
                        ]
                    });
                }
            }
        };

    // ==================== Scroll to Top ====================
    const scrollToTop = {
        init: () => {
            const $scrollTop = $('#scrollTop');

            $(window).on('scroll', utils.debounce(() => {
                if ($(window).scrollTop() > 300) {
                    $scrollTop.addClass('active');
                } else {
                    $scrollTop.removeClass('active');
                }
            }, 100));

            $scrollTop.on('click', () => {
                $('html, body').animate({
                    scrollTop: 0
                }, 800);
            });
        }
    };

    // ==================== AOS Animation ====================
    const aosAnimation = {
        init: () => {
            if (typeof AOS !== 'undefined') {
                AOS.init({
                    duration: 1000,
                    easing: 'ease-out-cubic',
                    once: true,
                    offset: 50,
                    delay: 0
                });

                // Refresh AOS on window resize
                $(window).on('resize', utils.debounce(() => {
                    AOS.refresh();
                }, 250));
            }
        }
    };

    // ==================== Active Menu Item ====================
    const activeMenuItem = {
        init: () => {
            const sections = document.querySelectorAll('section[id]');
            const navLinks = document.querySelectorAll('.navigation a[href^="#"], .mobile-navigation a[href^="#"]');

            const highlightNav = () => {
                const scrollY = window.pageYOffset;
                const headerHeight = document.querySelector('.main-header')?.offsetHeight || 0;

                sections.forEach(section => {
                    const sectionHeight = section.offsetHeight;
                    const sectionTop = section.offsetTop - headerHeight - 100;
                    const sectionId = section.getAttribute('id');

                    if (scrollY > sectionTop && scrollY <= sectionTop + sectionHeight) {
                        navLinks.forEach(link => {
                            link.parentElement.classList.remove('current');
                            if (link.getAttribute('href') === `#${sectionId}`) {
                                link.parentElement.classList.add('current');
                            }
                        });
                    }
                });
            };

            window.addEventListener('scroll', utils.debounce(highlightNav, 100));
        }
    };

    // ==================== Parallax Effect ====================
    const parallax = {
        init: () => {
            $(window).on('scroll', utils.debounce(() => {
                const scrolled = $(window).scrollTop();
                
                // Parallax for floating elements
                $('.floating-ball, .floating-item').each(function(index) {
                    const speed = 0.5 + (index * 0.1);
                    $(this).css('transform', `translateY(${scrolled * speed}px)`);
                });
            }, 10));
        }
    };

    // ==================== Form Validation (if exists) ====================
    const formValidation = {
        init: () => {
            $('form').on('submit', function(e) {
                const $form = $(this);
                let isValid = true;

                $form.find('input[required], textarea[required]').each(function() {
                    const $field = $(this);
                    
                    if (!$field.val().trim()) {
                        isValid = false;
                        $field.addClass('is-invalid');
                    } else {
                        $field.removeClass('is-invalid');
                    }
                });

                if (!isValid) {
                    e.preventDefault();
                }
            });

            // Remove invalid class on input
            $('input, textarea').on('input', function() {
                $(this).removeClass('is-invalid');
            });
        }
    };

    // ==================== Lazy Loading Images ====================
    const lazyLoad = {
        init: () => {
            const images = document.querySelectorAll('img[data-src]');
            
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.dataset.src;
                        img.classList.add('loaded');
                        observer.unobserve(img);
                    }
                });
            });

            images.forEach(img => imageObserver.observe(img));
        }
    };

    // ==================== Service Card Tilt Effect ====================
    const cardTilt = {
        init: () => {
            $('.service-card, .counter-card').on('mousemove', function(e) {
                const $card = $(this);
                const rect = this.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                
                const centerX = rect.width / 2;
                const centerY = rect.height / 2;
                
                const rotateX = (y - centerY) / 10;
                const rotateY = (centerX - x) / 10;
                
                $card.css({
                    'transform': `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) scale3d(1.02, 1.02, 1.02)`
                });
            });

            $('.service-card, .counter-card').on('mouseleave', function() {
                $(this).css({
                    'transform': 'perspective(1000px) rotateX(0) rotateY(0) scale3d(1, 1, 1)'
                });
            });
        }
    };

    // ==================== Cursor Trail Effect (Optional) ====================
    const cursorEffect = {
        init: () => {
            // Only on desktop
            if ($(window).width() > 991) {
                const cursor = $('<div class="custom-cursor"></div>');
                $('body').append(cursor);

                $(document).on('mousemove', (e) => {
                    cursor.css({
                        left: e.clientX + 'px',
                        top: e.clientY + 'px'
                    });
                });

                $('a, button, .service-card, .counter-card').on('mouseenter', () => {
                    cursor.addClass('cursor-hover');
                });

                $('a, button, .service-card, .counter-card').on('mouseleave', () => {
                    cursor.removeClass('cursor-hover');
                });

                // Add cursor styles
                $('<style>')
                    .prop('type', 'text/css')
                    .html(`
                        .custom-cursor {
                            position: fixed;
                            width: 20px;
                            height: 20px;
                            border: 2px solid var(--neon-green);
                            border-radius: 50%;
                            pointer-events: none;
                            z-index: 99999;
                            transition: transform 0.2s, background 0.2s;
                            transform: translate(-50%, -50%);
                        }
                        .custom-cursor.cursor-hover {
                            transform: translate(-50%, -50%) scale(1.5);
                            background: rgba(57, 255, 20, 0.2);
                        }
                    `)
                    .appendTo('head');
            }
        }
    };

    // ==================== Performance Optimization ====================
    const performanceOptimizations = {
        init: () => {
            // Disable animations on low-end devices
            const isLowEndDevice = () => {
                return navigator.hardwareConcurrency < 4 || 
                       /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
            };

            if (isLowEndDevice()) {
                $('body').addClass('reduce-motion');
                
                $('<style>')
                    .prop('type', 'text/css')
                    .html(`
                        .reduce-motion * {
                            animation-duration: 0.01ms !important;
                            animation-iteration-count: 1 !important;
                            transition-duration: 0.01ms !important;
                        }
                    `)
                    .appendTo('head');
            }

            // Reduce animations on scroll for better performance
            let ticking = false;
            
            $(window).on('scroll', () => {
                if (!ticking) {
                    window.requestAnimationFrame(() => {
                        ticking = false;
                    });
                    ticking = true;
                }
            });
        }
    };

    // ==================== Accessibility Enhancements ====================
    const accessibility = {
        init: () => {
            // Add skip to main content link
            const skipLink = $('<a href="#main-content" class="skip-link">پرش به محتوای اصلی</a>');
            $('body').prepend(skipLink);

            $('<style>')
                .prop('type', 'text/css')
                .html(`
                    .skip-link {
                        position: absolute;
                        top: -40px;
                        left: 0;
                        background: var(--neon-green);
                        color: var(--pure-black);
                        padding: 8px 16px;
                        text-decoration: none;
                        border-radius: 0 0 4px 0;
                        z-index: 100000;
                    }
                    .skip-link:focus {
                        top: 0;
                    }
                `)
                .appendTo('head');

            // Keyboard navigation for cards
            $('.service-card, .counter-card, .gallery-item').attr('tabindex', '0');
            
            // Add ARIA labels
            $('button:not([aria-label])').each(function() {
                const text = $(this).text().trim() || 'دکمه';
                $(this).attr('aria-label', text);
            });
        }
    };

    // ==================== Initialize All Features ====================
    const app = {
        init: () => {
            preloader.init();
            header.init();
            mobileMenu.init();
            smoothScroll.init();
            counterAnimation.init();
            galleryCarousel.init();
            scrollToTop.init();
            aosAnimation.init();
            activeMenuItem.init();
            parallax.init();
            formValidation.init();
            lazyLoad.init();
            cardTilt.init();
            // cursorEffect.init(); // Uncomment for custom cursor
            performanceOptimizations.init();
            accessibility.init();

            // Log initialization
            console.log('🏟️ Stadium Reservation System Initialized');
            console.log('✅ All features loaded successfully');
        }
    };

    // ==================== Document Ready ====================
    $(document).ready(() => {
        app.init();
    });

    // ==================== Window Load ====================
    $(window).on('load', () => {
        // Refresh AOS after all content loaded
        if (typeof AOS !== 'undefined') {
            AOS.refresh();
        }

        // Trigger lazy load
        $(window).trigger('scroll');
    });

    // ==================== Error Handling ====================
    window.addEventListener('error', (e) => {
        console.error('JavaScript Error:', e.error);
    });

})(jQuery);
