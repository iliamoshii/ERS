<?php
// فراخوانی هسته سیستم برای بررسی وضعیت ورود
require_once __DIR__ . '/backend/bootstrap.php';
use Core\Auth;

$isLoggedIn = Auth::check();
$isAdmin = $isLoggedIn ? Auth::isAdmin() : false;
$currentUser = $isLoggedIn ? Auth::user() : null;
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
    <title>رزرو استادیوم | انتخاب زمین ورزشی</title>

    <link rel="shortcut icon" href="assets/images/favicon.svg" type="image/x-icon">

    <link href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css" rel="stylesheet" type="text/css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/style-service.css">
    <link rel="stylesheet" href="assets/css/popup.css">
    <style>
        .skeleton-card {
            height: 360px; border-radius: 16px;
            background: linear-gradient(110deg, rgba(255,255,255,0.04) 8%, rgba(255,255,255,0.08) 18%, rgba(255,255,255,0.04) 33%);
            background-size: 200% 100%;
            animation: skeletonShine 1.5s linear infinite;
        }
        @keyframes skeletonShine { to { background-position-x: -200%; } }
    </style>
</head>

<body class="service-page">
<div class="page-wrapper">

    <div class="preloader">
        <div class="loader-container">
            <div class="football-loader"></div>
            <p class="loading-text">در حال بارگذاری...</p>
        </div>
    </div>

    <header class="main-header" id="header">
        <div class="header-upper">
            <div class="container">
                <div class="header-inner d-flex align-items-center justify-content-between">
                    <div class="logo-outer">
                        <div class="logo">
                            <a href="index.php">
                                <img src="assets/images/Full-logo.png" alt="لوگو سامانه رزرو" title="سامانه رزرو استادیوم">
                            </a>
                        </div>
                    </div>

                    <nav class="main-menu d-none d-lg-block">
                        <ul class="navigation">
                            <li><a href="index.php">صفحه اصلی</a></li>
                            <li class="current"><a href="service.php">رزرو استادیوم</a></li>
                            <li><a href="contact_us.php">تماس با ما</a></li>
                        </ul>
                    </nav>

                    <div class="header-actions d-flex align-items-center gap-3">

                        <?php if ($isLoggedIn): ?>
                            <?php if ($isAdmin): ?>
                                <a href="admin/dashboard.php" class="theme-btn btn-neon primary" id="userPanelLink">
                                    <i class="fas fa-user-shield ms-2"></i>
                                    پنل مدیریت
                                </a>
                            <?php else: ?>
                                <a href="user/dashboard.php" class="theme-btn" id="userPanelLink">
                                    <i class="fas fa-user ms-2"></i>
                                    <?= htmlspecialchars(mb_substr($currentUser['full_name'], 0, 15)) ?>
                                </a>
                            <?php endif; ?>
                        <?php else: ?>
                            <a href="Form.html" class="theme-btn" id="userPanelLink">
                                <i class="fas fa-sign-in-alt ms-2"></i>
                                ورود / ثبت‌نام
                            </a>
                        <?php endif; ?>

                        <button class="mobile-menu-toggle d-lg-none" id="mobileMenuToggle">
                            <span></span><span></span><span></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <div class="mobile-menu" id="mobileMenu">
        <div class="mobile-menu-overlay"></div>
        <div class="mobile-menu-content">
            <button class="mobile-menu-close" id="mobileMenuClose"><i class="fas fa-times"></i></button>
            <nav>
                <ul class="mobile-navigation">
                    <li><a href="index.php">صفحه اصلی</a></li>
                    <li><a href="service.php">رزرو استادیوم</a></li>
                    <li><a href="contact_us.php">تماس با ما</a></li>
                </ul>
            </nav>
        </div>
    </div>

    <section class="page-banner">
        <div class="banner-bg"></div>
        <div class="container">
            <div class="banner-content" data-aos="fade-up">
                <span class="banner-subtitle"><i class="fas fa-futbol"></i> بهترین زمین‌های ورزشی</span>
                <h1 class="banner-title">انتخاب میدان نبرد</h1>
                <p class="banner-description">مجموعه کامل زمین‌های ورزشی قابل رزرو</p>
                <div class="banner-stats">
                    <div class="stat-item">
                        <div class="stat-number" id="bannerTotalCount">--</div>
                        <div class="stat-label">استادیوم</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">۲۴/۷</div>
                        <div class="stat-label">دسترسی</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">آنلاین</div>
                        <div class="stat-label">رزرو</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="search-filter-section">
        <div class="container">
            <div class="search-filter-wrapper" data-aos="fade-up">

                <div class="main-search-bar">
                    <div class="search-input-wrapper">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" class="search-input" id="searchInput" placeholder="جستجو بر اساس نام، منطقه یا امکانات...">
                        <button class="clear-search" id="clearSearch"><i class="fas fa-times"></i></button>
                    </div>
                    <button class="filter-toggle-btn" id="filterToggleBtn">
                        <i class="fas fa-sliders-h"></i>
                        <span>فیلترها</span>
                        <span class="filter-badge" id="filterBadge">0</span>
                    </button>
                </div>

                <div class="advanced-filters" id="advancedFilters">
                    <div class="row g-3">
                        <div class="col-lg-3 col-md-6">
                            <div class="filter-group">
                                <label class="filter-label"><i class="fas fa-layer-group"></i> نوع سطح</label>
                                <select class="filter-select" id="surfaceType">
                                    <option value="">همه موارد</option>
                                    <option value="artificial">چمن مصنوعی</option>
                                    <option value="natural">چمن طبیعی</option>
                                    <option value="indoor">سالن سرپوشیده</option>
                                    <option value="outdoor">فضای باز</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <div class="filter-group">
                                <label class="filter-label"><i class="fas fa-map-marker-alt"></i> منطقه</label>
                                <select class="filter-select" id="location">
                                    <option value="">همه مناطق</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <div class="filter-group">
                                <label class="filter-label"><i class="fas fa-dollar-sign"></i> بازه قیمت</label>
                                <select class="filter-select" id="priceRange">
                                    <option value="">همه قیمت‌ها</option>
                                    <option value="0-300000">کمتر از ۳۰۰ هزار تومان</option>
                                    <option value="300000-500000">۳۰۰ تا ۵۰۰ هزار تومان</option>
                                    <option value="500000-700000">۵۰۰ تا ۷۰۰ هزار تومان</option>
                                    <option value="700000-999999999">بیشتر از ۷۰۰ هزار تومان</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <div class="filter-group">
                                <label class="filter-label"><i class="fas fa-star"></i> امتیاز</label>
                                <select class="filter-select" id="rating">
                                    <option value="">همه امتیازها</option>
                                    <option value="4.5">۴.۵ به بالا</option>
                                    <option value="4">۴ به بالا</option>
                                    <option value="3.5">۳.۵ به بالا</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="filter-group">
                                <label class="filter-label"><i class="fas fa-check-circle"></i> امکانات</label>
                                <div class="amenities-checkboxes">
                                    <label class="amenity-checkbox">
                                        <input type="checkbox" name="amenity" value="پارکینگ">
                                        <span class="checkbox-custom"></span>
                                        <i class="fas fa-parking"></i><span>پارکینگ</span>
                                    </label>
                                    <label class="amenity-checkbox">
                                        <input type="checkbox" name="amenity" value="دوش">
                                        <span class="checkbox-custom"></span>
                                        <i class="fas fa-shower"></i><span>دوش</span>
                                    </label>
                                    <label class="amenity-checkbox">
                                        <input type="checkbox" name="amenity" value="نورافکن">
                                        <span class="checkbox-custom"></span>
                                        <i class="fas fa-lightbulb"></i><span>نورافکن</span>
                                    </label>
                                    <label class="amenity-checkbox">
                                        <input type="checkbox" name="amenity" value="بوفه">
                                        <span class="checkbox-custom"></span>
                                        <i class="fas fa-coffee"></i><span>بوفه / کافه</span>
                                    </label>
                                    <label class="amenity-checkbox">
                                        <input type="checkbox" name="amenity" value="رختکن">
                                        <span class="checkbox-custom"></span>
                                        <i class="fas fa-tshirt"></i><span>رختکن</span>
                                    </label>
                                    <label class="amenity-checkbox">
                                        <input type="checkbox" name="amenity" value="کمد">
                                        <span class="checkbox-custom"></span>
                                        <i class="fas fa-lock"></i><span>کمد</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="filter-actions">
                        <button class="btn-reset-filters" id="resetFilters"><i class="fas fa-redo"></i> پاک کردن فیلترها</button>
                        <button class="btn-apply-filters" id="applyFilters"><i class="fas fa-check"></i> اعمال فیلترها</button>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <section class="results-header">
        <div class="container">
            <div class="results-info">
                <div class="results-count">
                    <span class="count-number" id="resultsCount">0</span>
                    <span class="count-text">استادیوم یافت شد</span>
                </div>
                <div class="sort-options">
                    <label>مرتب‌سازی:</label>
                    <select class="sort-select" id="sortBy">
                        <option value="popular">محبوب‌ترین</option>
                        <option value="rating">بالاترین امتیاز</option>
                        <option value="price-low">ارزان‌ترین</option>
                        <option value="price-high">گران‌ترین</option>
                        <option value="newest">جدیدترین</option>
                    </select>
                </div>
                <div class="view-toggle">
                    <button class="view-btn active" data-view="grid"><i class="fas fa-th"></i></button>
                    <button class="view-btn" data-view="list"><i class="fas fa-list"></i></button>
                </div>
            </div>
        </div>
    </section>

    <section class="stadium-grid-section">
        <div class="container">

            <div class="stadium-grid" id="loadingSkeleton">
                <div class="skeleton-card"></div>
                <div class="skeleton-card"></div>
                <div class="skeleton-card"></div>
            </div>

            <div class="stadium-grid" id="stadiumGrid"></div>

            <div class="no-results" id="noResults" style="display: none;">
                <div class="no-results-icon"><i class="fas fa-search"></i></div>
                <h3>هیچ نتیجه‌ای یافت نشد</h3>
                <p>فیلترهای خود را تغییر دهید یا جستجوی دیگری امتحان کنید</p>
                <button class="btn-reset" id="btnResetAll">پاک کردن همه فیلترها</button>
            </div>

            <div class="load-more-wrapper" data-aos="fade-up" id="loadMoreWrapper" style="display:none;">
                <button class="btn-load-more" id="loadMore">
                    <span>نمایش بیشتر</span>
                    <i class="fas fa-chevron-down"></i>
                </button>
            </div>
        </div>
    </section>

    <section class="cta-section">
        <div class="container">
            <div class="cta-wrapper" data-aos="zoom-in">
                <div class="cta-content">
                    <h2 class="cta-title">استادیوم خود را پیدا نکردید؟</h2>
                    <p class="cta-description">با تیم پشتیبانی ما تماس بگیرید تا بهترین گزینه را برای شما پیدا کنیم</p>
                    <div class="cta-actions">
                        <a href="contact_us.php" class="theme-btn btn-lg">
                            <i class="fas fa-phone ms-2"></i> تماس با پشتیبانی
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <footer class="main-footer">
        <div class="container">
            <div class="footer-content">
                <div class="row g-4">
                    <div class="col-lg-4 col-md-6">
                        <div class="footer-widget">
                            <div class="footer-logo"><img src="assets/images/Full-logo.png" alt="لوگو"></div>
                            <p class="footer-about">سامانه پیشرو در خدمات ورزشی و رزرو آنلاین استادیوم‌های کشور.</p>
                            <div class="social-links">
                                <a href="#"><i class="fab fa-instagram"></i></a>
                                <a href="#"><i class="fab fa-telegram"></i></a>
                                <a href="#"><i class="fab fa-twitter"></i></a>
                                <a href="#"><i class="fab fa-whatsapp"></i></a>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-6">
                        <div class="footer-widget">
                            <h5 class="footer-widget-title">دسترسی سریع</h5>
                            <ul class="footer-links">
                                <li><a href="index.php">صفحه اصلی</a></li>
                                <li><a href="service.php">رزرو استادیوم</a></li>
                                <li><a href="contact_us.php">تماس با ما</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="footer-widget">
                            <h5 class="footer-widget-title">خدمات</h5>
                            <ul class="footer-links">
                                <li><a href="#">رزرو آنلاین</a></li>
                                <li><a href="#">اپلیکیشن موبایل</a></li>
                                <li><a href="#">پشتیبانی ۲۴/۷</a></li>
                                <li><a href="#">تخفیف‌های ویژه</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="footer-widget">
                            <h5 class="footer-widget-title">تماس با ما</h5>
                            <ul class="footer-contact">
                                <li><i class="fas fa-phone"></i><span>۰۳۱-۱۲۳۴۵۶۷۸</span></li>
                                <li><i class="fas fa-envelope"></i><span>info@stadium.ir</span></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <div class="container">
                <p class="copyright-text">© ۱۴۰۳ تمام حقوق این وب‌سایت برای سامانه رزرو محفوظ است.</p>
            </div>
        </div>
    </footer>

</div>

<button class="scroll-top" id="scrollTop"><i class="fas fa-chevron-up"></i></button>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script src="assets/js/popup.js"></script>
<script src="assets/js/script.js"></script>
<script src="assets/js/service.js"></script>

</body>
</html>