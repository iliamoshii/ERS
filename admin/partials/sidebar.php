<?php
use Core\Auth;
use Core\Response as R;
$e      = fn($v) => R::e($v);
$admin  = Auth::user();
$cur    = basename($_SERVER['PHP_SELF']);
$active = fn(string $page): string => $cur === $page ? 'active' : '';
?>
<aside class="panel-sidebar" id="panelSidebar">

    <!-- Logo -->
    <div class="sidebar-logo">
        <a href="../index.html">
            <img src="../assets/images/logo.svg" alt="لوگو" class="logo-img">
            <span>پنل مدیریت</span>
        </a>
    </div>

    <!-- Admin Info -->
    <div class="sidebar-user">
        <div class="sidebar-avatar">
            <?php if ($admin['avatar']): ?>
                <img src="../<?= $e($admin['avatar']) ?>" alt="آواتار">
            <?php else: ?>
                <div class="avatar-placeholder">
                    <?= mb_substr($admin['full_name'], 0, 1) ?>
                </div>
            <?php endif; ?>
            <span class="online-dot"></span>
        </div>
        <div class="sidebar-user-info">
            <p class="sidebar-user-name"><?= $e($admin['full_name']) ?></p>
            <span class="sidebar-user-role">مدیر سیستم</span>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="sidebar-nav">
        <p class="nav-label">مدیریت</p>

        <a href="index.php" class="nav-item <?= $active('index.php') ?>">
            <i class="fas fa-tachometer-alt"></i>
            <span>داشبورد</span>
        </a>

        <a href="reservations.php" class="nav-item <?= $active('reservations.php') ?>">
            <i class="fas fa-calendar-check"></i>
            <span>رزروها</span>
        </a>

        <a href="users.php" class="nav-item <?= $active('users.php') ?>">
            <i class="fas fa-users"></i>
            <span>کاربران</span>
        </a>

        <a href="locations.php" class="nav-item <?= $active('locations.php') ?>">
            <i class="fas fa-map-marker-alt"></i>
            <span>مکان‌ها</span>
        </a>

        <a href="slots.php" class="nav-item <?= $active('slots.php') ?>">
            <i class="fas fa-calendar-week"></i>
            <span>سانس‌ها</span>
        </a>

        <a href="categories.php" class="nav-item <?= $active('categories.php') ?>">
            <i class="fas fa-tags"></i>
            <span>دسته‌بندی‌ها</span>
        </a>

        <a href="comments.php" class="nav-item <?= $active('comments.php') ?>">
            <i class="fas fa-comments"></i>
            <span>نظرات</span>
            <?php
            // Show pending count badge — only load the model if not already loaded
            if (!isset($pendingCommentCount)) {
                $pendingCommentCount = (new \Models\Comment())->countPending();
            }
            if ($pendingCommentCount > 0):
                ?>
                <span class="nav-badge"><?= $pendingCommentCount ?></span>
            <?php endif; ?>
        </a>

        <a href="contact.php" class="nav-item <?= $active('contact.php') ?>">
            <i class="fas fa-envelope"></i>
            <span>پیام‌های تماس</span>
            <?php
            if (!isset($unreadContactCount)) {
                $unreadContactCount = (new \Models\ContactMessage())->countUnread();
            }
            if ($unreadContactCount > 0):
                ?>
                <span class="nav-badge"><?= $unreadContactCount ?></span>
            <?php endif; ?>
        </a>

        <p class="nav-label mt-3">دسترسی سریع</p>

        <a href="../user/dashboard.php" class="nav-item">
            <i class="fas fa-user"></i>
            <span>پنل کاربری</span>
        </a>

        <a href="../index.php" class="nav-item">
            <i class="fas fa-home"></i>
            <span>صفحه اصلی</span>
        </a>

        <a href="../backend/api/auth.php?action=logout"
           class="nav-item nav-logout"
           id="logoutLink">
            <i class="fas fa-sign-out-alt"></i>
            <span>خروج</span>
        </a>
    </nav>

</aside>

<!-- Sidebar Overlay (mobile) -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>