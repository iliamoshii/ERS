<?php
/**
 * User Panel — My Reservations
 */

require_once dirname(__DIR__) . '/backend/bootstrap.php';

use Core\Auth;
use Core\CSRF;
use Core\Response;
use Middleware\AuthMiddleware;
use Models\Reservation;

// دسترسی فقط برای کاربران لاگین شده
AuthMiddleware::requireLogin();

$user = Auth::user();
$e = fn($v) => Response::e($v);

// دریافت فیلتر وضعیت در صورت وجود
$statusFilter = $_GET['status'] ?? '';

// لود کردن رزروها با استفاده از متد موجود در مدل شما
$reservationModel = new Reservation();
$myReservations = $reservationModel->findByUser((int)$user['id'], $statusFilter);
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>رزروهای من | سامانه رزرو استادیوم</title>
    <link href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/FontAwesome.Pro.7.1.0/css/all.css">
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/popup.css">
    <link rel="stylesheet" href="../assets/css/user-panel.css">
</head>
<body class="user-panel-page">

<?php include __DIR__ . '/partials/sidebar.php'; ?>

<div class="panel-main" id="panelMain">

    <?php include __DIR__ . '/partials/topbar.php'; ?>

    <div class="panel-content">

        <div class="page-header">
            <h1>تاریخچه رزروهای من</h1>
            <div class="breadcrumb-nav">
                <a href="dashboard.php">داشبورد</a>
                <i class="fas fa-chevron-left"></i>
                <span>رزروها</span>
            </div>
        </div>

        <div class="d-flex gap-2 mb-4 flex-wrap">
            <a href="reservations.php" class="btn btn-sm <?= $statusFilter === '' ? 'btn-neon primary' : 'btn-dark' ?>" style="border-radius: 20px; font-size: 12px; padding: 6px 16px;">همه رزروها</a>
            <a href="reservations.php?status=pending" class="btn btn-sm <?= $statusFilter === 'pending' ? 'btn-neon primary' : 'btn-dark' ?>" style="border-radius: 20px; font-size: 12px; padding: 6px 16px;">منتظر تایید</a>
            <a href="reservations.php?status=approved" class="btn btn-sm <?= $statusFilter === 'approved' ? 'btn-neon primary' : 'btn-dark' ?>" style="border-radius: 20px; font-size: 12px; padding: 6px 16px;">تایید شده</a>
            <a href="reservations.php?status=cancelled" class="btn btn-sm <?= $statusFilter === 'cancelled' ? 'btn-neon primary' : 'btn-dark' ?>" style="border-radius: 20px; font-size: 12px; padding: 6px 16px;">لغو شده</a>
        </div>

        <div class="panel-card">
            <div class="panel-card-header d-flex justify-content-between align-items-center">
                <h6><i class="fas fa-calendar-check ms-2"></i> لیست سانس‌های شما</h6>
                <span class="badge bg-secondary" style="font-size: 11px;"><?= count($myReservations) ?> مورد</span>
            </div>
            <div class="panel-card-body">

                <?php if (empty($myReservations)): ?>
                    <div class="text-center py-5">
                        <div class="mb-3" style="font-size: 48px; color: #39FF14; opacity: 0.4;">
                            <i class="fas fa-calendar-times"></i>
                        </div>
                        <h5 class="text-muted">هیچ رزروی در این وضعیت یافت نشد.</h5>
                    </div>
                <?php else: ?>

                    <div class="table-responsive">
                        <table class="table table-dark table-striped align-middle text-center custom-panel-table" style="background: #0a0e27; border: 1px solid #1a1f3c; font-size: 13px;">
                            <thead>
                            <tr style="border-bottom: 2px solid #39FF14; color: #00F0FF;">
                                <th class="py-3">#</th>
                                <th>مجموعه / استادیوم</th>
                                <th>تاریخ رزرو</th>
                                <th>زمان سانس</th>
                                <th>تعداد نفرات</th>
                                <th>مبلغ کل</th>
                                <th>وضعیت</th>
                                <th>عملیات</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($myReservations as $index => $res): ?>
                                <tr style="border-bottom: 1px solid #1a1f3c;">
                                    <td class="py-3 fw-bold"><?= $index + 1 ?></td>
                                    <td>
                                        <span class="fw-bold" style="color: #fff;"><?= $e($res['location_title']) ?></span>
                                        <div class="text-muted small mt-1" style="font-size: 10px;">
                                            <i class="fas fa-map-marker-alt ms-1"></i><?= $e($res['location_city'] ?? '') ?>، <?= $e($res['location_address'] ?? '') ?>
                                        </div>
                                    </td>
                                    <td class="fw-bold text-white"><?= $e($res['reservation_date']) ?></td>
                                    <td style="color: #00F0FF; direction: ltr;">
                                        <i class="far fa-clock ms-1"></i>
                                        <?= substr($res['start_time'], 0, 5) ?> تا <?= substr($res['end_time'], 0, 5) ?>
                                    </td>
                                    <td><?= $e($res['people_count']) ?> نفر</td>
                                    <td class="text-white fw-bold"><?= number_format((float)$res['total_price']) ?> تومان</td>
                                    <td>
                                        <?php if ($res['status'] === 'approved'): ?>
                                            <span class="badge px-3 py-2" style="background: rgba(57, 255, 20, 0.1); color: #39FF14; border: 1px solid #39FF14;">تایید شده</span>
                                        <?php elseif ($res['status'] === 'pending'): ?>
                                            <span class="badge px-3 py-2" style="background: rgba(255, 193, 7, 0.1); color: #ffc107; border: 1px solid #ffc107;">منتظر تایید</span>
                                        <?php else: ?>
                                            <span class="badge px-3 py-2" style="background: rgba(220, 53, 69, 0.1); color: #dc3545; border: 1px solid #dc3545;">لغو شده</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (in_array($res['status'], ['pending', 'approved'], true)): ?>
                                            <button class="btn btn-sm btn-outline-danger btn-cancel-res" data-id="<?= $res['id'] ?>" style="font-size: 11px; border-radius: 20px;">
                                                <i class="fas fa-times-circle ms-1"></i> لغو رزرو
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-dark disabled" style="font-size: 11px; border-radius: 20px; opacity: 0.3;">
                                                غيرقابل تغییر
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                <?php endif; ?>

            </div>
        </div>

    </div>
</div>

<script src="../assets/js/jquery-3.6.0.min.js"></script>
<script src="../assets/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/popup.js"></script>
<script src="../assets/js/user-panel.js"></script>

<script>
    // دریافت هدر یا توکن مستقیم CSRF مطابق ساختار هماهنگ پروژه
    const CSRF_TOKEN = '<?= CSRF::token() ?>';
    const API_RESERVATION = '../backend/api/reservation.php';

    $(document).on('click', '.btn-cancel-res', function(e) {
        e.preventDefault();
        const btn = $(this);
        const resId = btn.data('id');

        if (!confirm('آیا از لغو این نوبت رزرو اطمینان دارید؟')) {
            return;
        }

        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

        const fd = new FormData();
        fd.append('action', 'cancel_reservation');
        fd.append('reservation_id', resId);
        fd.append('reason', 'لغو توسط کاربر از پنل');
        fd.append('csrf_token', CSRF_TOKEN); // ارسال مستقیم پارامتر برای متد verifyRequest پروژه شما

        fetch(API_RESERVATION, {
            method: 'POST',
            credentials: 'same-origin',
            body: fd
        })
            .then(res => res.json()) // تبدیل مستقیم پاسخ با توجه به خروجی همیشه JSON متدهای شما
            .then(data => {
                if (data.success) {
                    window.Popup?.success('موفقیت‌آمیز', data.message || 'رزرو با موفقیت لغو شد.');
                    setTimeout(() => location.reload(), 1200);
                } else {
                    window.Popup?.error('خطا', data.message || 'مشکلی در لغو رزرو پیش آمد.');
                    btn.prop('disabled', false).html('<i class="fas fa-times-circle ms-1"></i> لغو رزرو');
                }
            })
            .catch(err => {
                console.error("Error cancellation fetch:", err);
                window.Popup?.error('خطای ارتباطی', 'اتصال با سرور برقرار نشد یا خروجی با خطا مواجه شد.');
                btn.prop('disabled', false).html('<i class="fas fa-times-circle ms-1"></i> لغو رزرو');
            });
    });
</script>
</body>
</html>