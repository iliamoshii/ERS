<?php
/**
 * Location Details + Booking Page
 * GET ?id=N
 */

declare(strict_types=1);

require_once __DIR__ . '/backend/bootstrap.php';

use Core\Auth;
use Core\CSRF;
use Core\Response;
use Models\Location;
use Models\Comment;

$e = fn($v) => Response::e($v);

$id       = (int) ($_GET['id'] ?? 0);
$locModel = new Location();
$location = $id > 0 ? $locModel->findById($id) : null;

if (!$location || $location['status'] !== 'active') {
    header('Location: service.html');
    exit;
}

$images    = $locModel->images($id);
$amenities = $location['amenities'] ? (json_decode($location['amenities'], true) ?: []) : [];

$commentModel  = new Comment();
$comments      = $commentModel->forLocation($id, 'approved');

$similar = array_filter($locModel->allActive('', (int) $location['category_id']), fn($l) => (int) $l['id'] !== $id);
$similar = array_slice($similar, 0, 3);

$isLoggedIn = Auth::check();
$user       = $isLoggedIn ? Auth::user() : null;

$surfaceLabels = ['artificial' => 'مصنوعی', 'natural' => 'طبیعی', 'indoor' => 'سرپوشیده', 'outdoor' => 'روباز'];

$AMENITY_ICON = [
    'پارکینگ' => 'fa-parking', 'دوش' => 'fa-shower', 'نورافکن' => 'fa-lightbulb',
    'بوفه' => 'fa-coffee', 'کافه' => 'fa-coffee', 'رختکن' => 'fa-tshirt',
    'کمد' => 'fa-lock', 'وای' => 'fa-wifi', 'کمک' => 'fa-first-aid', 'دوربین' => 'fa-video',
];
function amenityIcon(string $name, array $map): string
{
    foreach ($map as $k => $icon) {
        if (mb_strpos($name, $k) !== false) return $icon;
    }
    return 'fa-check';
}

// Persian weekday + Gregorian month names (no Jalali conversion in this
// build — see assets/js/details.js header note for why)
$weekdayFa = ['یکشنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه', 'پنج‌شنبه', 'جمعه', 'شنبه'];
$monthFa   = ['', 'ژانویه', 'فوریه', 'مارس', 'آوریل', 'مه', 'ژوئن', 'ژوئیه', 'اوت', 'سپتامبر', 'اکتبر', 'نوامبر', 'دسامبر'];
function toFa(int $n): string
{
    $d = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
    return str_replace(range(0, 9), $d, (string) $n);
}

$pageTitle = $location['title'];
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
    <title><?= $e($pageTitle) ?> | جزئیات و رزرو</title>

    <link rel="shortcut icon" href="assets/images/favicon.svg" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css" rel="stylesheet" type="text/css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/details.css">
    <link rel="stylesheet" href="assets/css/popup.css">
    <style>
        /* New review section + comment form — not in the original mockup */
        .reviews-list { display: flex; flex-direction: column; gap: 16px; }
        .review-item { padding: 16px; border-radius: 12px; background: rgba(255,255,255,0.03); }
        .review-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
        .review-author { font-weight: 700; font-size: 14px; }
        .review-stars { color: #ffaa00; font-size: 12px; }
        .review-date { font-size: 11px; color: rgba(255,255,255,0.4); }
        .review-body { font-size: 13px; color: rgba(255,255,255,0.75); line-height: 1.8; }
        .comment-form-wrap textarea { width:100%; min-height:90px; border-radius:10px; padding:12px; background:rgba(10,14,39,0.6); border:1px solid rgba(255,255,255,0.1); color:#fff; font-family:'Vazirmatn',sans-serif; }
        .comment-form-wrap .stars-input { display:flex; gap:6px; margin-bottom:10px; cursor:pointer; }
        .comment-form-wrap .stars-input i { font-size:20px; color:rgba(255,255,255,0.2); }
        .comment-form-wrap .stars-input i.active { color:#ffaa00; }
        .comment-login-prompt { padding:16px; text-align:center; border-radius:12px; background:rgba(255,255,255,0.03); font-size:13px; color:rgba(255,255,255,0.6); }
        .comment-login-prompt a { color:#39FF14; font-weight:700; }
    </style>
</head>

<body class="details-page" data-location-id="<?= $id ?>">
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
                        <div class="logo"><a href="index.php"><img src="assets/images/logo.svg" alt="لوگو سامانه رزرو"></a></div>
                    </div>
                    <nav class="main-menu d-none d-lg-block">
                        <ul class="navigation">
                            <li><a href="index.php">صفحه اصلی</a></li>
                            <li><a href="service.php">رزرو استادیوم</a></li>
                            <li><a href="contact_us.php">تماس با ما</a></li>
                        </ul>
                    </nav>
                    <div class="header-actions d-flex align-items-center gap-3">
                        <a href="<?= $isLoggedIn ? 'user/dashboard.php' : 'Form.html' ?>" class="theme-btn">
                            <i class="fas fa-user ms-2"></i>
                            <?= $isLoggedIn ? $e($user['full_name']) : 'پنل کاربری' ?>
                        </a>
                        <button class="mobile-menu-toggle d-lg-none" id="mobileMenuToggle"><span></span><span></span><span></span></button>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <div class="mobile-menu" id="mobileMenu">
        <div class="mobile-menu-overlay"></div>
        <div class="mobile-menu-content">
            <button class="mobile-menu-close" id="mobileMenuClose"><i class="fas fa-times"></i></button>
            <nav><ul class="mobile-navigation">
                <li><a href="index.html">صفحه اصلی</a></li>
                <li><a href="service.html">رزرو استادیوم</a></li>
                <li><a href="contact_us.html">تماس با ما</a></li>
            </ul></nav>
        </div>
    </div>

    <section class="back-section">
        <div class="container">
            <a href="service.html" class="back-link"><i class="fas fa-arrow-right"></i><span>بازگشت به لیست</span></a>
        </div>
    </section>

    <section class="stadium-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8" data-aos="fade-right">
                    <div class="stadium-title-section">
                        <h1 class="stadium-name"><?= $e($location['title']) ?></h1>
                        <div class="stadium-meta">
                            <div class="meta-item"><i class="fas fa-map-marker-alt"></i><span><?= $e($location['city']) ?><?= $location['district'] ? '، ' . $e($location['district']) : '' ?>، <?= $e($location['address']) ?></span></div>
                            <div class="meta-item"><i class="fas fa-star"></i><span><?= number_format((float) $location['rating_avg'], 1) ?></span><span class="reviews">(<?= (int) $location['rating_count'] ?> نظر)</span></div>
                            <div class="meta-item status-online"><span class="pulse"></span><span>فعال و آماده رزرو</span></div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 text-end" data-aos="fade-left">
                    <div class="stadium-actions">
                        <button class="btn-action favorite" id="btnFavorite"><i class="far fa-heart"></i></button>
                        <button class="btn-action share" id="btnShare"><i class="fas fa-share-alt"></i></button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="gallery-section">
        <div class="container">
            <div class="gallery-wrapper" data-aos="fade-up">
                <div class="swiper galleryMain">
                    <div class="swiper-wrapper">
                        <?php if (empty($images)): ?>
                            <div class="swiper-slide"><img src="assets/images/placeholder-stadium.jpg" alt="<?= $e($location['title']) ?>"></div>
                        <?php else: foreach ($images as $img): ?>
                            <div class="swiper-slide"><img src="<?= UPLOAD_URL . $e($img['filename']) ?>" alt="<?= $e($location['title']) ?>"></div>
                        <?php endforeach; endif; ?>
                    </div>
                    <div class="swiper-button-next"></div>
                    <div class="swiper-button-prev"></div>
                    <div class="image-counter"></div>
                </div>
                <?php if (count($images) > 1): ?>
                <div class="swiper galleryThumbs">
                    <div class="swiper-wrapper">
                        <?php foreach ($images as $img): ?>
                            <div class="swiper-slide"><img src="<?= UPLOAD_URL . $e($img['filename']) ?>" alt=""></div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section class="details-content-section">
        <div class="container">
            <div class="row g-4">

                <div class="col-lg-8">

                    <div class="content-card" data-aos="fade-up">
                        <h3 class="card-title"><i class="fas fa-info-circle"></i> درباره این مجموعه</h3>
                        <p class="card-description"><?= nl2br($e($location['description'] ?: 'توضیحاتی برای این مکان ثبت نشده است.')) ?></p>

                        <div class="features-grid">
                            <div class="feature-item">
                                <div class="feature-icon"><i class="fas fa-layer-group"></i></div>
                                <div class="feature-content"><h6>نوع سطح</h6><p><?= $e($surfaceLabels[$location['surface_type']] ?? $location['surface_type']) ?></p></div>
                            </div>
                            <div class="feature-item">
                                <div class="feature-icon"><i class="fas fa-expand-arrows-alt"></i></div>
                                <div class="feature-content"><h6>ظرفیت</h6><p><?= (int) $location['capacity'] ?> نفره</p></div>
                            </div>
                            <div class="feature-item">
                                <div class="feature-icon"><i class="fas fa-map-marker-alt"></i></div>
                                <div class="feature-content"><h6>منطقه</h6><p><?= $e($location['district'] ?: $location['city']) ?></p></div>
                            </div>
                            <div class="feature-item">
                                <div class="feature-icon"><i class="fas fa-tag"></i></div>
                                <div class="feature-content"><h6>قیمت پایه</h6><p><?= number_format((float) $location['price_per_session']) ?> ت</p></div>
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($amenities)): ?>
                    <div class="content-card" data-aos="fade-up" data-aos-delay="100">
                        <h3 class="card-title"><i class="fas fa-check-circle"></i> امکانات و تسهیلات</h3>
                        <div class="amenities-list">
                            <?php foreach ($amenities as $a): ?>
                                <div class="amenity-badge"><i class="fas <?= amenityIcon($a, $AMENITY_ICON) ?>"></i><span><?= $e($a) ?></span></div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Calendar & Booking -->
                    <div class="content-card booking-card" data-aos="fade-up" data-aos-delay="200">
                        <h3 class="card-title"><i class="fas fa-calendar-alt"></i> انتخاب زمان رزرو</h3>

                        <div class="date-selector">
                            <button class="date-nav prev" id="prevDate"><i class="fas fa-chevron-right"></i></button>
                            <div class="dates-wrapper">
                                <div class="date-scroll" id="dateScroll">
                                    <?php for ($i = 0; $i < 14; $i++):
                                        $ts = strtotime("+{$i} day");
                                        $isoDate = date('Y-m-d', $ts);
                                    ?>
                                        <button class="date-item <?= $i === 0 ? 'active' : '' ?>" data-date="<?= $isoDate ?>">
                                            <span class="day-name"><?= $weekdayFa[(int) date('w', $ts)] ?></span>
                                            <span class="day-number"><?= toFa((int) date('j', $ts)) ?></span>
                                            <span class="month-name"><?= $monthFa[(int) date('n', $ts)] ?></span>
                                        </button>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <button class="date-nav next" id="nextDate"><i class="fas fa-chevron-left"></i></button>
                        </div>

                        <div class="time-slots-wrapper">
                            <div class="time-slots-header">
                                <h6>سانس‌های موجود</h6>
                                <div class="legend">
                                    <span><span class="dot available"></span>موجود</span>
                                    <span><span class="dot selected"></span>انتخاب شده</span>
                                    <span><span class="dot reserved"></span>رزرو شده</span>
                                </div>
                            </div>
                            <div class="time-slots-grid" id="timeSlots">
                                <p class="text-muted" style="padding:20px; text-align:center; width:100%;">برای مشاهده سانس‌ها یک تاریخ انتخاب کنید...</p>
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($location['latitude']) && !empty($location['longitude'])): ?>
                    <div class="content-card" data-aos="fade-up" data-aos-delay="300">
                        <h3 class="card-title"><i class="fas fa-map-marked-alt"></i> موقعیت مکانی</h3>
                        <div class="map-wrapper">
                            <iframe
                                src="https://maps.google.com/maps?q=<?= $location['latitude'] ?>,<?= $location['longitude'] ?>&z=15&output=embed"
                                width="100%" height="400" style="border:0;" allowfullscreen="" loading="lazy">
                            </iframe>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Reviews (new section — not in original mockup) -->
                    <div class="content-card" data-aos="fade-up" data-aos-delay="350">
                        <h3 class="card-title"><i class="fas fa-comment-dots"></i> نظرات کاربران (<?= count($comments) ?>)</h3>

                        <div class="reviews-list mb-4">
                            <?php if (empty($comments)): ?>
                                <p class="text-muted">هنوز نظری برای این مکان ثبت نشده است.</p>
                            <?php else: foreach ($comments as $c): ?>
                                <div class="review-item">
                                    <div class="review-head">
                                        <span class="review-author"><?= $e($c['full_name']) ?></span>
                                        <span class="review-date"><?= $e(substr($c['created_at'], 0, 10)) ?></span>
                                    </div>
                                    <?php if ($c['rating']): ?>
                                        <div class="review-stars">
                                            <?php for ($s = 1; $s <= 5; $s++): ?><i class="fa<?= $s <= $c['rating'] ? 's' : 'r' ?> fa-star"></i><?php endfor; ?>
                                        </div>
                                    <?php endif; ?>
                                    <p class="review-body"><?= nl2br($e($c['body'])) ?></p>
                                </div>
                            <?php endforeach; endif; ?>
                        </div>

                        <?php if ($isLoggedIn): ?>
                            <div class="comment-form-wrap">
                                <h6 style="margin-bottom:10px;">ثبت نظر شما</h6>
                                <div class="stars-input" id="starsInput">
                                    <i class="far fa-star" data-val="1"></i>
                                    <i class="far fa-star" data-val="2"></i>
                                    <i class="far fa-star" data-val="3"></i>
                                    <i class="far fa-star" data-val="4"></i>
                                    <i class="far fa-star" data-val="5"></i>
                                </div>
                                <textarea id="commentBody" placeholder="تجربه خود را از این مکان بنویسید..." maxlength="1000"></textarea>
                                <button class="theme-btn submit-btn mt-2" id="submitCommentBtn"><span class="btn-text">ثبت نظر</span></button>
                            </div>
                        <?php else: ?>
                            <div class="comment-login-prompt">
                                برای ثبت نظر، ابتدا <a href="Form.html">وارد حساب کاربری خود شوید</a>.
                            </div>
                        <?php endif; ?>
                    </div>

                </div>

                <div class="col-lg-4">
                    <div class="booking-summary" id="bookingSummary">
                        <div class="summary-card">
                            <h4 class="summary-title">خلاصه رزرو</h4>
                            <div class="summary-content" id="summaryContent">
                                <div class="empty-state">
                                    <i class="fas fa-calendar-times"></i>
                                    <p>هنوز زمانی انتخاب نشده است</p>
                                    <span>لطفاً تاریخ و سانس مورد نظر را انتخاب کنید</span>
                                </div>
                            </div>
                            <div class="selected-info" id="selectedInfo" style="display: none;">
                                <div class="info-row"><span class="label">تاریخ:</span><span class="value" id="selectedDate">-</span></div>
                                <div class="info-row"><span class="label">ساعت:</span><span class="value" id="selectedTime">-</span></div>
                                <div class="price-breakdown">
                                    <div class="price-row"><span>قیمت سانس:</span><span id="slotPrice">-</span></div>
                                    <div class="price-row total"><span>مبلغ قابل پرداخت:</span><span id="totalPrice">-</span></div>
                                </div>
                                <button class="btn-book-now" id="btnBookNow">
                                    <i class="fas fa-check ms-2"></i><span>ثبت درخواست رزرو</span>
                                </button>
                                <div class="security-note"><i class="fas fa-shield-alt"></i><span>رزرو شما پس از تأیید مدیر نهایی می‌شود</span></div>
                            </div>
                        </div>
                    </div>

                    <div class="quick-info-card">
                        <h5>اطلاعات تماس</h5>
                        <?php if ($location['phone']): ?>
                            <div class="contact-item"><i class="fas fa-phone"></i><span><?= $e($location['phone']) ?></span></div>
                        <?php endif; ?>
                        <button class="btn-contact" id="btnWhatsapp"><i class="fab fa-whatsapp"></i><span>تماس از طریق واتساپ</span></button>
                    </div>

                    <?php if ($location['rules']): ?>
                    <div class="rules-card">
                        <h5>قوانین و مقررات</h5>
                        <ul>
                            <?php foreach (array_filter(array_map('trim', explode("\n", $location['rules']))) as $rule): ?>
                                <li><?= $e($rule) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </section>

    <?php if (!empty($similar)): ?>
    <section class="similar-stadiums-section">
        <div class="container">
            <h3 class="section-title" data-aos="fade-up">استادیوم‌های مشابه</h3>
            <div class="row g-4">
                <?php foreach ($similar as $s): $simImg = $locModel->getPrimaryImage((int) $s['id']); ?>
                    <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="100">
                        <div class="stadium-card-mini">
                            <img src="<?= $simImg ? UPLOAD_URL . $e($simImg) : 'assets/images/placeholder-stadium.jpg' ?>" alt="<?= $e($s['title']) ?>">
                            <div class="card-mini-content">
                                <h6><?= $e($s['title']) ?></h6>
                                <p><i class="fas fa-map-marker-alt"></i> <?= $e($s['district'] ?: $s['city']) ?></p>
                                <div class="card-mini-footer">
                                    <span class="price">از <?= number_format((float) $s['price_per_session']) ?> تومان</span>
                                    <a href="details.php?id=<?= (int) $s['id'] ?>" class="btn-view">مشاهده</a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <footer class="main-footer">
        <div class="container">
            <div class="footer-content">
                <div class="row g-4">
                    <div class="col-lg-4 col-md-6">
                        <div class="footer-widget">
                            <div class="footer-logo"><img src="assets/images/logo.svg" alt="لوگو"></div>
                            <p class="footer-about">سامانه پیشرو در خدمات ورزشی و رزرو آنلاین استادیوم‌های کشور.</p>
                            <div class="social-links">
                                <a href="#"><i class="fab fa-instagram"></i></a><a href="#"><i class="fab fa-telegram"></i></a>
                                <a href="#"><i class="fab fa-twitter"></i></a><a href="#"><i class="fab fa-whatsapp"></i></a>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-6">
                        <div class="footer-widget"><h5 class="footer-widget-title">دسترسی سریع</h5>
                            <ul class="footer-links"><li><a href="index.html">صفحه اصلی</a></li><li><a href="service.html">رزرو استادیوم</a></li><li><a href="contact_us.html">تماس با ما</a></li></ul>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="footer-widget"><h5 class="footer-widget-title">خدمات</h5>
                            <ul class="footer-links"><li><a href="#">رزرو آنلاین</a></li><li><a href="#">اپلیکیشن موبایل</a></li><li><a href="#">پشتیبانی ۲۴/۷</a></li></ul>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="footer-widget"><h5 class="footer-widget-title">تماس با ما</h5>
                            <ul class="footer-contact"><li><i class="fas fa-phone"></i><span>۰۳۱-۱۲۳۴۵۶۷۸</span></li><li><i class="fas fa-envelope"></i><span>info@stadium.ir</span></li></ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="footer-bottom"><div class="container"><p class="copyright-text">© ۱۴۰۳ تمام حقوق این وب‌سایت برای سامانه رزرو محفوظ است.</p></div></div>
    </footer>

</div>

<button class="scroll-top" id="scrollTop"><i class="fas fa-chevron-up"></i></button>

<script>
    // Bootstrap data the JS layer needs, injected server-side
    window.LOCATION_ID    = <?= $id ?>;
    window.IS_LOGGED_IN   = <?= $isLoggedIn ? 'true' : 'false' ?>;
    window.CSRF_TOKEN     = '<?= CSRF::token() ?>';
    window.LOCATION_PHONE = '<?= $e($location['phone'] ?? '') ?>';
</script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
<script src="assets/js/popup.js"></script>
<script src="assets/js/script.js"></script>
<script src="assets/js/details.js"></script>

</body>
</html>
