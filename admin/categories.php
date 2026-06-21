<?php
/**
 * Admin — Categories Management
 */

declare(strict_types=1);

require_once __DIR__ . '/../backend/bootstrap.php';

use Core\Auth;
use Core\CSRF;
use Core\Response;
use Middleware\AuthMiddleware;
use Models\Category;

AuthMiddleware::requireAdmin();

$admin = Auth::user();
$e     = fn($v) => Response::e($v);

$catModel   = new Category();
$categories = $catModel->all();

$pageTitle = 'مدیریت دسته‌بندی‌ها';
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

            <div class="panel-card">
                <div class="panel-card-header">
                    <h6 id="catFormHeading"><i class="fas fa-plus-circle"></i> دسته‌بندی جدید</h6>
                </div>
                <div class="panel-card-body">
                    <form id="categoryForm" data-mode="create">
                        <input type="hidden" name="action" id="catAction" value="create">
                        <input type="hidden" name="category_id" id="catId" value="">
                        <input type="hidden" name="csrf_token" value="<?= $e(CSRF::token()) ?>">
                        <div class="inline-form-row">
                            <div class="field-col">
                                <label class="form-label-panel"><i class="fas fa-tag"></i> نام دسته‌بندی</label>
                                <input type="text" name="name" id="catName" class="form-control-panel" required minlength="2">
                            </div>
                            <div class="field-col icon-col">
                                <label class="form-label-panel"><i class="fas fa-icons"></i> آیکون</label>
                                <input type="text" name="icon" id="catIcon" class="form-control-panel" placeholder="fas fa-futbol">
                            </div>
                            <div class="field-col" style="flex:0 0 auto; display:flex; gap:8px;">
                                <button type="submit" class="theme-btn submit-btn" style="white-space:nowrap;">
                                    <span class="btn-text">ذخیره</span>
                                </button>
                                <button type="button" id="catCancelEdit" class="btn-outline" style="display:none;">انصراف</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <?php if (empty($categories)): ?>
                <div class="empty-state">
                    <i class="fas fa-tags"></i>
                    <h4>هنوز دسته‌بندی‌ای ثبت نشده</h4>
                    <p>از فرم بالا برای افزودن دسته‌بندی استفاده کنید.</p>
                </div>
            <?php else: ?>
                <div class="category-grid">
                    <?php foreach ($categories as $c): ?>
                        <div class="category-card">
                            <div class="category-card-icon">
                                <i class="<?= $e($c['icon'] ?: 'fas fa-tag') ?>"></i>
                            </div>
                            <div class="category-card-body">
                                <p class="cat-name"><?= $e($c['name']) ?></p>
                                <p class="cat-count"><?= (int) $c['location_count'] ?> مکان</p>
                            </div>
                            <div class="category-card-actions">
                                <button class="btn-action"
                                    data-cat-edit
                                    data-id="<?= (int) $c['id'] ?>"
                                    data-name="<?= $e($c['name']) ?>"
                                    data-icon="<?= $e($c['icon']) ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn-action btn-cancel"
                                    data-admin-post="../backend/api/admin_categories.php"
                                    data-admin-fields='{"action":"delete","category_id":<?= (int) $c['id'] ?>}'
                                    data-admin-confirm="حذف شود؟">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        </main>
    </div>
</div>

<div class="loading-overlay" id="loadingOverlay">
    <div class="loading-spinner"><div class="spinner-ring"></div><div class="spinner-text">در حال پردازش...</div></div>
</div>

<script src="../assets/js/jquery-3.6.0.min.js"></script>
<script src="../assets/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/popup.js"></script>
<script src="../assets/js/user-panel.js"></script>
<script src="../assets/js/admin-panel.js"></script>
<script>
const CSRF_TOKEN = '<?= CSRF::token() ?>';

// Page-specific: switch the shared form between create/edit modes.
// (admin-panel.js's initCategoryForm() only submits whatever is in the
// form — populating it for edit is this page's own concern.)
document.querySelectorAll('[data-cat-edit]').forEach((btn) => {
    btn.addEventListener('click', () => {
        document.getElementById('catAction').value = 'update';
        document.getElementById('catId').value     = btn.dataset.id;
        document.getElementById('catName').value   = btn.dataset.name;
        document.getElementById('catIcon').value   = btn.dataset.icon || '';
        document.getElementById('catFormHeading').innerHTML = '<i class="fas fa-edit"></i> ویرایش دسته‌بندی';
        document.getElementById('catCancelEdit').style.display = 'inline-block';
        document.getElementById('catName').focus();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
});

document.getElementById('catCancelEdit')?.addEventListener('click', () => {
    const form = document.getElementById('categoryForm');
    form.reset();
    document.getElementById('catAction').value = 'create';
    document.getElementById('catId').value     = '';
    document.getElementById('catFormHeading').innerHTML = '<i class="fas fa-plus-circle"></i> دسته‌بندی جدید';
    document.getElementById('catCancelEdit').style.display = 'none';
});
</script>
</body>
</html>
