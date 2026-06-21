<?php
use Core\Auth;
use Core\Response as R;
$e    = fn($v) => R::e($v);
$user = Auth::user();
$cur  = basename($_SERVER['PHP_SELF']);
$active = fn(string $page): string => $cur === $page ? 'active' : '';
?>
<aside class="panel-sidebar" id="panelSidebar">

    <!-- Logo -->
    <div class="sidebar-logo">
        <a href="../index.html">
            <img src="../assets/images/logo.svg" alt="لوگو" class="logo-img">
            <span>رزرو استادیوم</span>
        </a>
    </div>

    <!-- User Info -->
    <div class="sidebar-user">
        <div class="sidebar-avatar">
            <?php if ($user['avatar']): ?>
                <img src="../<?= $e($user['avatar']) ?>" alt="آواتار">
            <?php else: ?>
                <div class="avatar-placeholder">
                    <?= mb_substr($user['full_name'], 0, 1) ?>
                </div>
            <?php endif; ?>
            <span class="online-dot"></span>
        </div>
        <div class="sidebar-user-info">
            <p class="sidebar-user-name"><?= $e($user['full_name']) ?></p>
            <span class="sidebar-user-role">کاربر</span>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="sidebar-nav">
        <p class="nav-label">منوی اصلی</p>

        <a href="dashboard.php" class="nav-item <?= $active('dashboard.php') ?>">
            <i class="fas fa-tachometer-alt"></i>
            <span>داشبورد</span>
        </a>

        <a href="reservations.php" class="nav-item <?= $active('reservations.php') ?>">
            <i class="fas fa-calendar-check"></i>
            <span>رزروهای من</span>
        </a>

        <a href="profile.php" class="nav-item <?= $active('profile.php') ?>">
            <i class="fas fa-user-edit"></i>
            <span>پروفایل</span>
        </a>

        <a href="comments.php" class="nav-item <?= $active('comments.php') ?>">
            <i class="fas fa-comments"></i>
            <span>نظرات من</span>
        </a>

        <p class="nav-label mt-3">دسترسی سریع</p>

        <a href="../service.html" class="nav-item">
            <i class="fas fa-search-location"></i>
            <span>جستجوی زمین</span>
        </a>

        <a href="../index.html" class="nav-item">
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