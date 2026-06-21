<?php
/**
 * Admin — Locations Management (list)
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

$locModel = new Location();

$allowedStatuses = ['', 'active', 'inactive'];
$status = $_GET['status'] ?? '';
$status = in_array($status, $allowedStatuses, true) ? $status : '';

$search = trim((string) ($_GET['q'] ?? ''));
$page   = max(1, (int) ($_GET['page'] ?? 1));

$result     = $locModel->paginate($page, PER_PAGE_ADMIN, $search, $status);
$locations  = $result['data'];
$total      = $result['total'];
$totalPages = max(1, (int) ceil($total / PER_PAGE_ADMIN));

function pageUrl(int $p, string $status, string $search): string
{
    $params = ['page' => $p];
    if ($status !== '') $params['status'] = $status;
    if ($search !== '') $params['q']      = $search;
    return 'locations.php?' . http_build_query($params);
}

$pageTitle = 'مدیریت مکان‌ها';
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

            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <div class="filter-tabs mb-0">
                    <?php $tabs = ['' => 'همه', 'active' => 'فعال', 'inactive' => 'غیرفعال']; ?>
                    <?php foreach ($tabs as $val => $label): ?>
                        <a href="<?= $e(pageUrl(1, $val, $search)) ?>" class="filter-tab <?= $status === $val ? 'active' : '' ?>">
                            <?= $e($label) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
                <a href="location-form.php" class="theme-btn submit-btn" style="white-space:nowrap;">
                    <span class="btn-text"><i class="fas fa-plus"></i> افزودن مکان جدید</span>
                </a>
            </div>

            <div class="panel-card">
                <div class="panel-card-header">
                    <h6><i class="fas fa-map-marker-alt"></i> مکان‌ها (<?= $total ?>)</h6>
                    <form method="get" class="d-flex gap-2">
                        <?php if ($status !== ''): ?><input type="hidden" name="status" value="<?= $e($status) ?>"><?php endif; ?>
                        <input type="text" name="q" value="<?= $e($search) ?>" placeholder="عنوان یا آدرس..."
                               class="form-control-panel" style="max-width:220px;">
                        <button type="submit" class="btn-action"><i class="fas fa-search"></i></button>
                    </form>
                </div>
                <div class="panel-card-body">
                    <?php if (empty($locations)): ?>
                        <div class="empty-state">
                            <i class="fas fa-map-marker-alt"></i>
                            <h4>مکانی یافت نشد</h4>
                        </div>
                    <?php else: ?>
                        <div class="res-table-wrap">
                            <table class="res-table">
                                <thead>
                                    <tr>
                                        <th>مکان</th><th>دسته‌بندی</th><th>شهر</th>
                                        <th>قیمت</th><th>ظرفیت</th><th>امتیاز</th><th>وضعیت</th><th>عملیات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($locations as $l): ?>
                                    <tr>
                                        <td>
                                            <div class="loc-title-cell">
                                                <div class="loc-thumb">
                                                    <?php if ($l['primary_image']): ?>
                                                        <img src="../assets/images/uploads/<?= $e($l['primary_image']) ?>" alt="">
                                                    <?php else: ?>
                                                        <i class="fas fa-image"></i>
                                                    <?php endif; ?>
                                                </div>
                                                <div>
                                                    <p class="loc-name"><?= $e($l['title']) ?></p>
                                                    <p class="loc-sub"><?= $e(mb_substr($l['address'], 0, 30)) ?></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?= $e($l['category_name']) ?></td>
                                        <td><?= $e($l['city']) ?></td>
                                        <td><?= number_format((float) $l['price_per_session']) ?> تومان</td>
                                        <td><?= (int) $l['capacity'] ?> نفر</td>
                                        <td><i class="fas fa-star" style="color:#ffaa00;"></i> <?= number_format((float) $l['rating_avg'], 1) ?></td>
                                        <td>
                                            <span class="badge-status <?= $l['status'] === 'active' ? 'badge-success' : 'badge-secondary' ?>">
                                                <?= $l['status'] === 'active' ? 'فعال' : 'غیرفعال' ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-1">
                                                <a href="location-form.php?id=<?= (int) $l['id'] ?>" class="btn-action">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button class="btn-action btn-cancel"
                                                    data-admin-post="../backend/api/admin_locations.php"
                                                    data-admin-fields='{"action":"delete","location_id":<?= (int) $l['id'] ?>}'
                                                    data-admin-confirm="حذف قطعی؟">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <?php if ($totalPages > 1): ?>
                            <div class="pagination-wrap">
                                <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                                    <a href="<?= $e(pageUrl($p, $status, $search)) ?>"
                                       class="page-btn <?= $p === $page ? 'active' : '' ?>"><?= $p ?></a>
                                <?php endfor; ?>
                            </div>
                        <?php endif; ?>
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
</script>
</body>
</html>
