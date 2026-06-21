<?php
use Core\Auth;
use Core\Response as R;
use Models\Notification;

$e           = fn($v) => R::e($v);
$user        = Auth::user();
$notifModel  = new Notification();
$unread      = $notifModel->unreadCount($user['id']);
?>
<header class="panel-topbar">

    <!-- Mobile sidebar toggle -->
    <button class="sidebar-toggle" id="sidebarToggle" aria-label="منو">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Page title (set per page via $pageTitle) -->
    <h5 class="topbar-title"><?= isset($pageTitle) ? $e($pageTitle) : 'داشبورد' ?></h5>

    <!-- Right actions -->
    <div class="topbar-actions">

        <!-- Notifications Bell -->
        <div class="topbar-notif dropdown" id="notifDropdown">
            <button class="topbar-icon-btn" id="notifBtn">
                <i class="fas fa-bell"></i>
                <?php if ($unread > 0): ?>
                    <span class="notif-badge"><?= $unread > 9 ? '۹+' : $unread ?></span>
                <?php endif; ?>
            </button>
        </div>

        <!-- User Menu -->
        <div class="topbar-user" id="topbarUserMenu">
            <button class="topbar-user-btn" id="userMenuBtn">
                <?php if ($user['avatar']): ?>
                    <img src="../<?= $e($user['avatar']) ?>" alt="آواتار" class="topbar-avatar">
                <?php else: ?>
                    <div class="topbar-avatar-placeholder">
                        <?= mb_substr($user['full_name'], 0, 1) ?>
                    </div>
                <?php endif; ?>
                <span class="topbar-username d-none d-md-inline">
                    <?= $e($user['full_name']) ?>
                </span>
                <i class="fas fa-chevron-down topbar-arrow"></i>
            </button>

            <div class="user-dropdown" id="userDropdown">
                <a href="profile.php" class="dropdown-item">
                    <i class="fas fa-user-edit"></i> ویرایش پروفایل
                </a>
                <a href="reservations.php" class="dropdown-item">
                    <i class="fas fa-calendar-check"></i> رزروهای من
                </a>
                <div class="dropdown-divider"></div>
                <a href="../backend/api/auth.php?action=logout" class="dropdown-item text-danger">
                    <i class="fas fa-sign-out-alt"></i> خروج
                </a>
            </div>
        </div>

    </div>
</header>