<?php
/**
 * Admin — Location Create / Edit Form
 * No ?id= → create mode. ?id=X → edit mode (also shows image manager).
 */

declare(strict_types=1);

require_once __DIR__ . '/../backend/bootstrap.php';

use Core\Auth;
use Core\CSRF;
use Core\Response;
use Middleware\AuthMiddleware;
use Models\Location;

AuthMiddleware::requireAdmin();

$admin = Auth::user();
$e     = fn($v) => Response::e($v);

$locModel   = new Location();
$categories = $locModel->categories();

$id   = (int) ($_GET['id'] ?? 0);
$mode = $id > 0 ? 'edit' : 'create';

$location = null;
$images   = [];

if ($mode === 'edit') {
    $location = $locModel->findById($id);
    if (!$location) {
        Response::redirect('locations.php');
    }
    $images = $locModel->images($id);
}

// Defaults for create mode / fallback for edit mode
$v = fn(string $key, $default = '') => $e($location[$key] ?? $default);

$surfaceLabels = [
    'artificial' => 'چمن مصنوعی', 'natural' => 'چمن طبیعی',
    'indoor'     => 'سرپوشیده',   'outdoor' => 'روباز',
];

$pageTitle = $mode === 'edit' ? 'ویرایش مکان' : 'افزودن مکان جدید';
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
<body class="user-panel-page" <?= $mode === 'edit' ? 'data-location-id="' . $id . '"' : '' ?>>
<div class="panel-layout">

    <?php require __DIR__ . '/partials/sidebar.php'; ?>

    <div class="panel-main" id="panelMain">
        <?php require __DIR__ . '/partials/topbar.php'; ?>

        <main class="panel-content">

            <a href="locations.php" class="btn-link mb-3 d-inline-flex">
                <i class="fas fa-arrow-right"></i> بازگشت به فهرست مکان‌ها
            </a>

            <div class="panel-card">
                <div class="panel-card-header">
                    <h6>
                        <i class="fas <?= $mode === 'edit' ? 'fa-edit' : 'fa-plus-circle' ?>"></i>
                        <?= $e($pageTitle) ?>
                    </h6>
                </div>
                <div class="panel-card-body">
                    <form id="locationForm" data-mode="<?= $mode ?>">
                        <input type="hidden" name="csrf_token" value="<?= $e(CSRF::token()) ?>">
                        <?php if ($mode === 'edit'): ?>
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="location_id" value="<?= $id ?>">
                        <?php else: ?>
                            <input type="hidden" name="action" value="create">
                        <?php endif; ?>

                        <div class="form-grid mb-3">
                            <div class="span-2">
                                <label class="form-label-panel"><i class="fas fa-heading"></i> عنوان مکان</label>
                                <input type="text" name="title" class="form-control-panel" required minlength="3"
                                       value="<?= $v('title') ?>" placeholder="مثال: زمین چمن آزادی">
                            </div>

                            <div>
                                <label class="form-label-panel"><i class="fas fa-tags"></i> دسته‌بندی</label>
                                <select name="category_id" class="form-control-panel" required>
                                    <option value="">انتخاب کنید...</option>
                                    <?php foreach ($categories as $c): ?>
                                        <option value="<?= (int) $c['id'] ?>"
                                            <?= ($location['category_id'] ?? null) == $c['id'] ? 'selected' : '' ?>>
                                            <?= $e($c['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div>
                                <label class="form-label-panel"><i class="fas fa-layer-group"></i> نوع سطح</label>
                                <select name="surface_type" class="form-control-panel">
                                    <?php foreach ($surfaceLabels as $val => $label): ?>
                                        <option value="<?= $val ?>" <?= ($location['surface_type'] ?? 'artificial') === $val ? 'selected' : '' ?>>
                                            <?= $e($label) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="span-2">
                                <label class="form-label-panel"><i class="fas fa-map-marker-alt"></i> آدرس</label>
                                <input type="text" name="address" class="form-control-panel" required
                                       value="<?= $v('address') ?>" placeholder="آدرس کامل مکان">
                            </div>

                            <div>
                                <label class="form-label-panel"><i class="fas fa-city"></i> شهر</label>
                                <input type="text" name="city" class="form-control-panel" value="<?= $v('city', 'تهران') ?>">
                            </div>

                            <div>
                                <label class="form-label-panel"><i class="fas fa-phone"></i> تلفن مکان</label>
                                <input type="text" name="phone" class="form-control-panel" value="<?= $v('phone') ?>">
                            </div>

                            <div>
                                <label class="form-label-panel"><i class="fas fa-money-bill-wave"></i> قیمت هر جلسه (تومان)</label>
                                <input type="number" name="price_per_session" class="form-control-panel" required min="1"
                                       value="<?= $v('price_per_session', '0') ?>">
                            </div>

                            <div>
                                <label class="form-label-panel"><i class="fas fa-users"></i> ظرفیت (نفر)</label>
                                <input type="number" name="capacity" class="form-control-panel" required min="1"
                                       value="<?= $v('capacity', '10') ?>">
                            </div>

                            <div>
                                <label class="form-label-panel"><i class="fas fa-toggle-on"></i> وضعیت</label>
                                <select name="status" class="form-control-panel">
                                    <option value="active"   <?= ($location['status'] ?? 'active') === 'active'   ? 'selected' : '' ?>>فعال</option>
                                    <option value="inactive" <?= ($location['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>غیرفعال</option>
                                </select>
                            </div>

                            <div class="span-2">
                                <label class="form-label-panel"><i class="fas fa-align-right"></i> توضیحات</label>
                                <textarea name="description" class="form-control-panel" rows="3"><?= $v('description') ?></textarea>
                            </div>

                            <div class="span-2">
                                <label class="form-label-panel"><i class="fas fa-clipboard-list"></i> قوانین مکان</label>
                                <textarea name="rules" class="form-control-panel" rows="3"><?= $v('rules') ?></textarea>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-success">
                            <span class="btn-text">ذخیره مکان</span>
                            <span class="btn-icon"><i class="fas fa-save"></i></span>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Image gallery panel: always visible. In create mode it is shown disabled with instructions. -->
            <div class="panel-card">
                <div class="panel-card-header">
                    <h6><i class="fas fa-images"></i> گالری تصاویر</h6>
                </div>
                <div class="panel-card-body">
                    <?php if ($mode === 'edit'): ?>
                        <!-- In edit mode: label references the hidden-by-CSS file input. Clicking the label opens the file picker. -->
                        <label class="image-upload-zone" for="imageUploadInput">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <p>برای آپلود تصویر جدید کلیک کنید (JPG، PNG یا WebP — حداکثر ۵ مگابایت)</p>
                        </label>
                        <!-- visually hidden (display:none) input — DO NOT use the HTML 'hidden' attribute -->
                        <input type="file" id="imageUploadInput" accept="image/jpeg,image/png,image/webp" style="display:none;">

                        <div id="imageGrid" class="mt-3">
                            <?php if (empty($images)): ?>
                                <div class="empty-state">
                                    <i class="fas fa-images"></i>
                                    <p>هنوز تصویری برای این مکان آپلود نشده است.</p>
                                </div>
                            <?php else: ?>
                                <div class="image-grid">
                                    <?php foreach ($images as $img): ?>
                                        <div class="image-grid-item <?= $img['is_primary'] ? 'is-primary' : '' ?>">
                                            <img src="<?= UPLOAD_URL . $e($img['filename']) ?>" alt="">
                                            <?php if ($img['is_primary']): ?>
                                                <span class="primary-tag">اصلی</span>
                                            <?php endif; ?>
                                            <div class="image-actions">
                                                <?php if (!$img['is_primary']): ?>
                                                    <button class="btn-action"
                                                            data-admin-post="../backend/api/admin_locations.php"
                                                            data-admin-fields='{"action":"set_primary_image","image_id":<?= (int) $img['id'] ?>,"location_id":<?= $id ?>}'>
                                                        <i class="fas fa-star"></i>
                                                    </button>
                                                <?php endif; ?>
                                                <button class="btn-action"
                                                        data-admin-post="../backend/api/admin_locations.php"
                                                        data-admin-fields='{"action":"remove_image","image_id":<?= (int) $img['id'] ?>,"location_id":<?= $id ?>}'
                                                        data-admin-confirm="حذف؟">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <!-- Create mode: show upload zone but DO NOT reference the input so clicking doesn't try to open picker.
                             The input is present but disabled, and the zone instructs the admin to save first. -->
                        <label class="image-upload-zone disabled" style="cursor:default;">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <p>برای اضافه کردن تصاویر، ابتدا اطلاعات مکان را ذخیره کنید. بعد از ذخیره، صفحه دوباره بارگذاری شده و آپلود تصاویر فعال می‌شود.</p>
                        </label>
                        <input type="file" id="imageUploadInput" accept="image/jpeg,image/png,image/webp" style="display:none;" disabled>

                        <div id="imageGrid" class="mt-3">
                            <div class="empty-state">
                                <i class="fas fa-images"></i>
                                <p>بعد از ذخیرهٔ مکان، می‌توانید تصاویر را اینجا آپلود و مدیریت کنید.</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

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
    // If admin-panel.js binds the click to the label or the #imageUploadInput, this will continue to work.
    // Ensure admin-panel.js listens for change on #imageUploadInput and uploads the selected file.
</script>
</body>
</html>
