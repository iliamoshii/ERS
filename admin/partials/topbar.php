<?php
use Core\Auth;
use Core\Response as R;

$e     = fn($v) => R::e($v);
$admin = Auth::user();
?>
<header class="panel-topbar">

    <!-- Mobile sidebar toggle -->
    <button class="sidebar-toggle" id="sidebarToggle" aria-label="منو">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Page title (set per page via $pageTitle) -->
    <h5 class="topbar-title"><?= isset($pageTitle) ? $e($pageTitle) : 'داشبورد مدیریت' ?></h5>

    <!-- Right actions -->
    <div class="topbar-actions">

        <!-- User Menu -->
        <div class="topbar-user" id="topbarUserMenu">
            <button class="topbar-user-btn" id="userMenuBtn">
                <?php if ($admin['avatar']): ?>
                    <img src="../<?= $e($admin['avatar']) ?>" alt="آواتار" class="topbar-avatar">
                <?php else: ?>
                    <div class="topbar-avatar-placeholder">
                        <?= mb_substr($admin['full_name'], 0, 1) ?>
                    </div>
                <?php endif; ?>
                <span class="topbar-username d-none d-md-inline">
                    <?= $e($admin['full_name']) ?>
                </span>
                <i class="fas fa-chevron-down topbar-arrow"></i>
            </button>

            <div class="user-dropdown" id="userDropdown">
                <a href="../user/profile.php" class="dropdown-item">
                    <i class="fas fa-user-edit"></i> پروفایل من
                </a>
                <div class="dropdown-divider"></div>
                <a href="../backend/api/auth.php?action=logout" class="dropdown-item text-danger">
                    <i class="fas fa-sign-out-alt"></i> خروج
                </a>
            </div>
        </div>

    </div>
</header>