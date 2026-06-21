<?php
/**
 * Admin Dashboard
 */

declare(strict_types=1);

require_once __DIR__ . '/../backend/bootstrap.php';

use Core\Auth;
use Core\CSRF;
use Core\ActivityLogger;
use Core\Response;
use Middleware\AuthMiddleware;
use Models\User;
use Models\Location;
use Models\Reservation;
use Models\Comment;
use Models\ContactMessage;

AuthMiddleware::requireAdmin();

$admin = Auth::user();
$e     = fn($v) => Response::e($v);

$userModel = new User();
$locModel  = new Location();
$resModel  = new Reservation();

$totalUsers       = $userModel->count();
$newUsersThisWeek = $userModel->countNew(7);
$totalLocations   = $locModel->count();
$totalReservations = $resModel->count();
$pendingCount      = $resModel->countByStatus('pending');
$approvedCount     = $resModel->countByStatus('approved');

$pendingList = $resModel->paginate(1, 6, '', 'pending')['data'];
$recentLogs  = ActivityLogger::recent(8);

$commentModel  = new Comment();
$contactModel  = new ContactMessage();
$pendingComments = $commentModel->countPending();
$unreadMessages  = $contactModel->countUnread();

$pageTitle = 'داشبورد مدیریت';
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
    <title><?= $e($pageTitle) ?> | پنل مدیریت</title>
    <link rel="shortcut icon" href="../assets/images/favicon.svg" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/FontAwesome.Pro.7.1.0/css/all.css">
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/popup.css">
    <link rel="stylesheet" href="../assets/css/user-panel.css">
    <link rel="stylesheet" href="../assets/css/admin-panel.css">
</head>
<body class="user-panel-page">
<div class="panel-layout">

    <?php require __DIR__ . '/partials/sidebar.php'; ?>

    <div class="panel-main" id="panelMain">
        <?php require __DIR__ . '/partials/topbar.php'; ?>

        <main class="panel-content">

            <div class="welcome-banner">
                <h2>سلام <?= $e($admin['full_name']) ?> 👋</h2>
                <p class="welcome-text">خلاصه‌ای از وضعیت سامانه در یک نگاه.</p>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3">
                    <div class="stat-card stat-users">
                        <div class="stat-icon"><i class="fas fa-users"></i></div>
                        <div class="stat-info">
                            <span class="stat-num"><?= $totalUsers ?></span>
                            <span class="stat-lbl">کاربران</span>
                            <?php if ($newUsersThisWeek > 0): ?>
                                <span class="kpi-delta"><i class="fas fa-arrow-up"></i> +<?= $newUsersThisWeek ?> این هفته</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-card stat-locations">
                        <div class="stat-icon"><i class="fas fa-map-marker-alt"></i></div>
                        <div class="stat-info">
                            <span class="stat-num"><?= $totalLocations ?></span>
                            <span class="stat-lbl">مکان‌ها</span>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-card stat-pending">
                        <div class="stat-icon"><i class="fas fa-clock"></i></div>
                        <div class="stat-info">
                            <span class="stat-num"><?= $pendingCount ?></span>
                            <span class="stat-lbl">در انتظار تأیید</span>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-card stat-total">
                        <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
                        <div class="stat-info">
                            <span class="stat-num"><?= $totalReservations ?></span>
                            <span class="stat-lbl">کل رزروها</span>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-card stat-pending">
                        <div class="stat-icon"><i class="fas fa-comment-dots"></i></div>
                        <div class="stat-info">
                            <span class="stat-num"><?= $pendingComments ?></span>
                            <span class="stat-lbl">نظرات در انتظار</span>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-card stat-notif">
                        <div class="stat-icon"><i class="fas fa-envelope"></i></div>
                        <div class="stat-info">
                            <span class="stat-num"><?= $unreadMessages ?></span>
                            <span class="stat-lbl">پیام‌های خوانده‌نشده</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3">
                <!-- Pending reservations needing action -->
                <div class="col-lg-7">
                    <div class="panel-card">
                        <div class="panel-card-header">
                            <h6><i class="fas fa-hourglass-half"></i> رزروهای در انتظار تأیید</h6>
                            <a href="reservations.php?status=pending" class="btn-link">
                                مشاهده همه <i class="fas fa-arrow-left"></i>
                            </a>
                        </div>
                        <div class="panel-card-body">
                            <?php if (empty($pendingList)): ?>
                                <div class="empty-state">
                                    <i class="fas fa-check-circle"></i>
                                    <h4>چیزی برای تأیید نیست</h4>
                                    <p>همه رزروها بررسی شده‌اند.</p>
                                </div>
                            <?php else: ?>
                                <div class="res-table-wrap">
                                    <table class="res-table">
                                        <thead>
                                            <tr>
                                                <th>کاربر</th>
                                                <th>مکان</th>
                                                <th>تاریخ</th>
                                                <th>عملیات</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        <?php foreach ($pendingList as $r): ?>
                                            <tr>
                                                <td><?= $e($r['user_name']) ?></td>
                                                <td><?= $e($r['location_title']) ?></td>
                                                <td><?= $e($r['reservation_date']) ?></td>
                                                <td>
                                                    <div class="d-flex gap-1">
                                                        <button class="btn-action"
                                                            data-admin-post="../backend/api/admin_reservations.php"
                                                            data-admin-fields='{"action":"approve","reservation_id":<?= (int)$r['id'] ?>}'
                                                            data-admin-confirm="تأیید شود؟">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                        <button class="btn-action btn-cancel"
                                                            data-admin-post="../backend/api/admin_reservations.php"
                                                            data-admin-fields='{"action":"reject","reservation_id":<?= (int)$r['id'] ?>}'
                                                            data-admin-confirm="رد شود؟">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    </div>
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

                <!-- Recent activity feed -->
                <div class="col-lg-5">
                    <div class="panel-card">
                        <div class="panel-card-header">
                            <h6><i class="fas fa-stream"></i> فعالیت‌های اخیر</h6>
                        </div>
                        <div class="panel-card-body">
                            <?php if (empty($recentLogs)): ?>
                                <div class="empty-state">
                                    <i class="fas fa-stream"></i>
                                    <p>هنوز فعالیتی ثبت نشده است.</p>
                                </div>
                            <?php else: ?>
                                <div class="notif-list">
                                    <?php foreach ($recentLogs as $log): ?>
                                        <div class="notif-item">
                                            <div class="notif-icon"><i class="fas fa-bolt"></i></div>
                                            <div class="notif-body">
                                                <p class="notif-title"><?= $e($log['description']) ?></p>
                                                <span class="notif-msg">
                                                    <?= $e($log['user_name'] ?? 'سیستم') ?>
                                                </span>
                                                <p class="notif-time"><?= $e($log['created_at']) ?></p>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

        </main>
    </div>
</div>

<div class="loading-overlay" id="loadingOverlay">
    <div class="loading-spinner">
        <div class="spinner-ring"></div>
        <div class="spinner-text">در حال پردازش...</div>
    </div>
</div>

<script src="../assets/js/jquery-3.6.0.min.js"></script>
<script src="../assets/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/popup.js"></script>
<script src="../assets/js/user-panel.js"></script>
<script src="../assets/js/admin-panel.js"></script>
<script>
const CSRF_TOKEN = '<?= CSRF::token() ?>';
</script>
</body>
</html>
