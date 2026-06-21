<?php
/**
 * Admin — Reservations Management
 */

declare(strict_types=1);

require_once __DIR__ . '/../backend/bootstrap.php';

use Core\Auth;
use Core\CSRF;
use Core\Response;
use Middleware\AuthMiddleware;
use Models\Reservation;

AuthMiddleware::requireAdmin();

$admin = Auth::user();
$e     = fn($v) => Response::e($v);

$resModel = new Reservation();

$allowedStatuses = ['', 'pending', 'approved', 'rejected', 'cancelled'];
$status = $_GET['status'] ?? '';
$status = in_array($status, $allowedStatuses, true) ? $status : '';

$search = trim((string) ($_GET['q'] ?? ''));
$page   = max(1, (int) ($_GET['page'] ?? 1));

$result      = $resModel->paginate($page, PER_PAGE_ADMIN, $search, $status);
$reservations = $result['data'];
$total        = $result['total'];
$totalPages   = max(1, (int) ceil($total / PER_PAGE_ADMIN));

$counts = [
    ''          => $resModel->count(),
    'pending'   => $resModel->countByStatus('pending'),
    'approved'  => $resModel->countByStatus('approved'),
    'rejected'  => $resModel->countByStatus('rejected'),
    'cancelled' => $resModel->countByStatus('cancelled'),
];

function resBadge(string $status): array
{
    return match ($status) {
        'pending'   => ['در انتظار', 'badge-warning'],
        'approved'  => ['تأیید شده', 'badge-success'],
        'rejected'  => ['رد شده',    'badge-danger'],
        'cancelled' => ['لغو شده',   'badge-secondary'],
        default     => [$status,     'badge-secondary'],
    };
}

function pageUrl(int $p, string $status, string $search): string
{
    $params = ['page' => $p];
    if ($status !== '') $params['status'] = $status;
    if ($search !== '') $params['q']      = $search;
    return 'reservations.php?' . http_build_query($params);
}

$pageTitle = 'مدیریت رزروها';
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

            <div class="filter-tabs">
                <?php
                $tabs = ['' => 'همه', 'pending' => 'در انتظار', 'approved' => 'تأیید شده', 'rejected' => 'رد شده', 'cancelled' => 'لغو شده'];
                foreach ($tabs as $val => $label): ?>
                    <a href="<?= $e(pageUrl(1, $val, $search)) ?>" class="filter-tab <?= $status === $val ? 'active' : '' ?>">
                        <?= $e($label) ?> <span class="filter-count"><?= $counts[$val] ?></span>
                    </a>
                <?php endforeach; ?>
            </div>

            <div class="panel-card">
                <div class="panel-card-header">
                    <h6><i class="fas fa-calendar-check"></i> رزروها (<?= $total ?>)</h6>
                    <form method="get" class="d-flex gap-2">
                        <?php if ($status !== ''): ?><input type="hidden" name="status" value="<?= $e($status) ?>"><?php endif; ?>
                        <input type="text" name="q" value="<?= $e($search) ?>" placeholder="جستجوی کاربر یا مکان..."
                               class="form-control-panel" style="max-width:220px;">
                        <button type="submit" class="btn-action"><i class="fas fa-search"></i></button>
                    </form>
                </div>
                <div class="panel-card-body">
                    <?php if (empty($reservations)): ?>
                        <div class="empty-state">
                            <i class="fas fa-calendar-times"></i>
                            <h4>رزروی یافت نشد</h4>
                            <p>با این فیلتر هیچ رزروی موجود نیست.</p>
                        </div>
                    <?php else: ?>
                        <div class="res-table-wrap">
                            <table class="res-table">
                                <thead>
                                    <tr>
                                        <th>#</th><th>کاربر</th><th>تلفن</th><th>مکان</th>
                                        <th>تاریخ</th><th>ساعت</th><th>قیمت</th><th>وضعیت</th><th>عملیات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($reservations as $r): [$label, $cls] = resBadge($r['status']); ?>
                                    <tr>
                                        <td>#<?= (int) $r['id'] ?></td>
                                        <td><?= $e($r['user_name']) ?></td>
                                        <td><?= $e($r['user_phone']) ?></td>
                                        <td><?= $e($r['location_title']) ?></td>
                                        <td><?= $e($r['reservation_date']) ?></td>
                                        <td><?= $e(substr($r['start_time'], 0, 5)) ?></td>
                                        <td><?= number_format((float) $r['total_price']) ?> تومان</td>
                                        <td><span class="badge-status <?= $cls ?>"><?= $e($label) ?></span></td>
                                        <td>
                                            <?php if ($r['status'] === 'pending'): ?>
                                                <div class="d-flex gap-1">
                                                    <button class="btn-action"
                                                        data-admin-post="../backend/api/admin_reservations.php"
                                                        data-admin-fields='{"action":"approve","reservation_id":<?= (int) $r['id'] ?>}'
                                                        data-admin-confirm="تأیید؟">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                    <button class="btn-action btn-cancel"
                                                        data-admin-post="../backend/api/admin_reservations.php"
                                                        data-admin-fields='{"action":"reject","reservation_id":<?= (int) $r['id'] ?>}'
                                                        data-admin-confirm="رد؟">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
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
