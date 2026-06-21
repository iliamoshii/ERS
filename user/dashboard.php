<?php
require_once dirname(__DIR__) . '/backend/bootstrap.php';

use Core\Auth;
use Core\CSRF;
use Core\Response;
use Middleware\AuthMiddleware;
use Models\Reservation;
use Models\Notification;

// Require login
AuthMiddleware::requireLogin();

$user          = Auth::user();
$reserveModel  = new Reservation();
$notifModel    = new Notification();

$myReservations  = $reserveModel->findByUser($user['id'], '', 5);
$notifications   = $notifModel->forUser($user['id'], 8);
$unreadCount     = $notifModel->unreadCount($user['id']);
$totalRes        = count($reserveModel->findByUser($user['id']));
$pendingRes      = count($reserveModel->findByUser($user['id'], 'pending'));
$approvedRes     = count($reserveModel->findByUser($user['id'], 'approved'));

// Status helpers
function statusLabel(string $s): string {
    return match($s) {
        'pending'   => 'در انتظار',
        'approved'  => 'تأیید شده',
        'rejected'  => 'رد شده',
        'cancelled' => 'لغو شده',
        default     => $s,
    };
}
function statusClass(string $s): string {
    return match($s) {
        'pending'   => 'warning',
        'approved'  => 'success',
        'rejected'  => 'danger',
        'cancelled' => 'secondary',
        default     => 'secondary',
    };
}
function notifIcon(string $type): string {
    return match(true) {
        str_contains($type, 'approved') => 'fas fa-check-circle text-success',
        str_contains($type, 'rejected') => 'fas fa-times-circle text-danger',
        str_contains($type, 'pending')  => 'fas fa-clock text-warning',
        default                         => 'fas fa-bell text-info',
    };
}

use Core\Response as R;
$e = fn($v) => R::e($v);
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>داشبورد کاربری | سامانه رزرو استادیوم</title>
    <link href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/FontAwesome.Pro.7.1.0/css/all.css">
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/popup.css">
    <link rel="stylesheet" href="../assets/css/user-panel.css">
</head>
<body class="user-panel-page">

<!-- ── Sidebar ───────────────────────────────────────────── -->
<?php include __DIR__ . '/partials/sidebar.php'; ?>

<!-- ── Main Wrapper ──────────────────────────────────────── -->
<div class="panel-main" id="panelMain">

    <!-- Top Bar -->
    <?php include __DIR__ . '/partials/topbar.php'; ?>

    <!-- Page Content -->
    <div class="panel-content">

        <!-- Welcome Banner -->
        <div class="welcome-banner">
            <div class="welcome-text">
                <h4>سلام، <?= $e($user['full_name']) ?> 👋</h4>
                <p>به پنل کاربری خوش آمدید. رزروهای شما در یک نگاه:</p>
            </div>
            <a href="../service.html" class="theme-btn btn-sm">
                <i class="fas fa-plus ms-2"></i> رزرو جدید
            </a>
        </div>

        <!-- Stats Cards -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-xl-3">
                <div class="stat-card stat-total">
                    <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
                    <div class="stat-info">
                        <span class="stat-num"><?= $totalRes ?></span>
                        <span class="stat-lbl">کل رزروها</span>
                    </div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="stat-card stat-pending">
                    <div class="stat-icon"><i class="fas fa-clock"></i></div>
                    <div class="stat-info">
                        <span class="stat-num"><?= $pendingRes ?></span>
                        <span class="stat-lbl">در انتظار</span>
                    </div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="stat-card stat-approved">
                    <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                    <div class="stat-info">
                        <span class="stat-num"><?= $approvedRes ?></span>
                        <span class="stat-lbl">تأیید شده</span>
                    </div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="stat-card stat-notif">
                    <div class="stat-icon"><i class="fas fa-bell"></i></div>
                    <div class="stat-info">
                        <span class="stat-num"><?= $unreadCount ?></span>
                        <span class="stat-lbl">اعلان‌های خوانده‌نشده</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">

            <!-- Latest Reservations -->
            <div class="col-lg-8">
                <div class="panel-card">
                    <div class="panel-card-header">
                        <h6><i class="fas fa-list-alt ms-2"></i> آخرین رزروها</h6>
                        <a href="reservations.php" class="btn-link">مشاهده همه</a>
                    </div>
                    <div class="panel-card-body p-0">
                        <?php if (empty($myReservations)): ?>
                            <div class="empty-state">
                                <i class="fas fa-calendar-times"></i>
                                <p>هنوز هیچ رزروی ثبت نکرده‌اید.</p>
                                <a href="../service.html" class="theme-btn btn-sm mt-2">رزرو اولین زمین</a>
                            </div>
                        <?php else: ?>
                            <div class="res-table-wrap">
                                <table class="res-table">
                                    <thead>
                                        <tr>
                                            <th>زمین</th>
                                            <th>تاریخ</th>
                                            <th>ساعت</th>
                                            <th>وضعیت</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($myReservations as $r): ?>
                                        <tr>
                                            <td><?= $e($r['location_title']) ?></td>
                                            <td><?= $e($r['reservation_date']) ?></td>
                                            <td><?= substr($r['start_time'], 0, 5) ?>–<?= substr($r['end_time'], 0, 5) ?></td>
                                            <td>
                                                <span class="badge-status badge-<?= statusClass($r['status']) ?>">
                                                    <?= statusLabel($r['status']) ?>
                                                </span>
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

            <!-- Notifications -->
            <div class="col-lg-4">
                <div class="panel-card">
                    <div class="panel-card-header">
                        <h6><i class="fas fa-bell ms-2"></i> اعلان‌ها</h6>
                        <?php if ($unreadCount > 0): ?>
                            <button class="btn-link" id="markAllReadBtn">خواندن همه</button>
                        <?php endif; ?>
                    </div>
                    <div class="panel-card-body notif-list">
                        <?php if (empty($notifications)): ?>
                            <div class="empty-state">
                                <i class="fas fa-bell-slash"></i>
                                <p>اعلانی وجود ندارد.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($notifications as $n): ?>
                            <div class="notif-item <?= $n['is_read'] ? '' : 'unread' ?>"
                                 data-id="<?= $n['id'] ?>">
                                <div class="notif-icon">
                                    <i class="<?= notifIcon($n['type']) ?>"></i>
                                </div>
                                <div class="notif-body">
                                    <p class="notif-title"><?= $e($n['title']) ?></p>
                                    <p class="notif-msg"><?= $e($n['message']) ?></p>
                                    <span class="notif-time">
                                        <?= date('H:i — Y/m/d', strtotime($n['created_at'])) ?>
                                    </span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div>
    </div><!-- /.panel-content -->
</div><!-- /.panel-main -->

<!-- Scripts -->
<script src="../assets/js/jquery-3.6.0.min.js"></script>
<script src="../assets/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/popup.js"></script>
<script src="../assets/js/user-panel.js"></script>
<script>
const CSRF_TOKEN = '<?= CSRF::token() ?>';

// Mark all notifications read
document.getElementById('markAllReadBtn')?.addEventListener('click', async () => {
    const res  = await fetch('../backend/api/notifications.php?action=mark_all_read', {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'csrf_token=' + encodeURIComponent(CSRF_TOKEN),
    });
    const data = await res.json();
    if (data.success) {
        document.querySelectorAll('.notif-item.unread').forEach(el => el.classList.remove('unread'));
        document.querySelectorAll('.notif-badge').forEach(el => el.remove());
        document.getElementById('markAllReadBtn')?.remove();
        Popup.success('اعلان‌ها', 'همه اعلان‌ها خوانده شدند.');
    }
});

// Flash messages from session
<?php
$flash = \Core\Session::getFlash('popup');
if ($flash): ?>
Popup.<?= $e($flash['type']) ?>('<?= $e($flash['title']) ?>', '<?= $e($flash['message']) ?>');
<?php endif; ?>
</script>

</body>
</html>
