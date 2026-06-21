<?php
/**
 * Admin — Comments Management
 */

declare(strict_types=1);

require_once __DIR__ . '/../backend/bootstrap.php';

use Core\Auth;
use Core\CSRF;
use Core\Response;
use Middleware\AuthMiddleware;
use Models\Comment;

AuthMiddleware::requireAdmin();

$admin = Auth::user();
$e     = fn($v) => Response::e($v);

$model = new Comment();

$allowedStatuses = ['', 'pending', 'approved', 'rejected'];
$status = $_GET['status'] ?? '';
$status = in_array($status, $allowedStatuses, true) ? $status : '';

$search = trim((string) ($_GET['q'] ?? ''));
$page   = max(1, (int) ($_GET['page'] ?? 1));

$result   = $model->paginate($page, PER_PAGE_ADMIN, $search, $status);
$comments = $result['data'];
$total    = $result['total'];
$totalPages = max(1, (int) ceil($total / PER_PAGE_ADMIN));

$counts = [
    ''         => $model->count(),
    'pending'  => $model->countPending(),
    'approved' => 0,   // no dedicated countByStatus yet — queried inline below
    'rejected' => 0,
];

// Quick per-status counts using paginate with limit=1 trick
foreach (['approved', 'rejected'] as $s) {
    $counts[$s] = $model->paginate(1, 1, '', $s)['total'];
}

function commentBadge(string $status): array
{
    return match ($status) {
        'pending'  => ['در انتظار', 'badge-warning'],
        'approved' => ['تأیید شده', 'badge-success'],
        'rejected' => ['رد شده',    'badge-danger'],
        default    => [$status,     'badge-secondary'],
    };
}

function pageUrl(int $p, string $status, string $search): string
{
    $params = ['page' => $p];
    if ($status !== '') $params['status'] = $status;
    if ($search !== '') $params['q']      = $search;
    return 'comments.php?' . http_build_query($params);
}

$pageTitle = 'مدیریت نظرات';
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

            <!-- Filter tabs -->
            <div class="filter-tabs">
                <?php
                $tabs = ['' => 'همه', 'pending' => 'در انتظار', 'approved' => 'تأیید شده', 'rejected' => 'رد شده'];
                foreach ($tabs as $val => $label):
                ?>
                    <a href="<?= $e(pageUrl(1, $val, $search)) ?>"
                       class="filter-tab <?= $status === $val ? 'active' : '' ?>">
                        <?= $e($label) ?>
                        <span class="filter-count"><?= $counts[$val] ?></span>
                    </a>
                <?php endforeach; ?>
            </div>

            <div class="panel-card">
                <div class="panel-card-header">
                    <h6><i class="fas fa-comments"></i> نظرات (<?= $total ?>)</h6>
                    <form method="get" class="d-flex gap-2">
                        <?php if ($status !== ''): ?>
                            <input type="hidden" name="status" value="<?= $e($status) ?>">
                        <?php endif; ?>
                        <input type="text" name="q" value="<?= $e($search) ?>"
                               placeholder="جستجو در متن، نام کاربر یا مکان..."
                               class="form-control-panel" style="max-width:260px;">
                        <button type="submit" class="btn-action">
                            <i class="fas fa-search"></i>
                        </button>
                        <?php if ($search !== ''): ?>
                            <a href="<?= $e(pageUrl(1, $status, '')) ?>" class="btn-action">
                                <i class="fas fa-times"></i>
                            </a>
                        <?php endif; ?>
                    </form>
                </div>
                <div class="panel-card-body">
                    <?php if (empty($comments)): ?>
                        <div class="empty-state">
                            <i class="fas fa-comment-slash"></i>
                            <h4>نظری یافت نشد</h4>
                            <p>با این فیلتر هیچ نظری موجود نیست.</p>
                        </div>
                    <?php else: ?>
                        <div class="res-table-wrap">
                            <table class="res-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>کاربر</th>
                                        <th>مکان</th>
                                        <th>امتیاز</th>
                                        <th>متن نظر</th>
                                        <th>تاریخ</th>
                                        <th>وضعیت</th>
                                        <th>عملیات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($comments as $c): [$label, $cls] = commentBadge($c['status']); ?>
                                    <tr>
                                        <td>#<?= (int) $c['id'] ?></td>
                                        <td><?= $e($c['user_name']) ?></td>
                                        <td><?= $e($c['location_title']) ?></td>
                                        <td>
                                            <?php if ($c['rating']): ?>
                                                <span style="color:#ffaa00;">
                                                    <?php for ($s = 1; $s <= 5; $s++): ?>
                                                        <i class="fas fa-star<?= $s > $c['rating'] ? '-half-alt' : '' ?>"
                                                           style="font-size:11px; <?= $s > $c['rating'] ? 'opacity:.3' : '' ?>"></i>
                                                    <?php endfor; ?>
                                                </span>
                                                <small>(<?= (int) $c['rating'] ?>)</small>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span title="<?= $e($c['body']) ?>">
                                                <?= $e(mb_substr($c['body'], 0, 60)) ?><?= mb_strlen($c['body']) > 60 ? '…' : '' ?>
                                            </span>
                                        </td>
                                        <td><?= $e(substr($c['created_at'], 0, 10)) ?></td>
                                        <td>
                                            <span class="badge-status <?= $e($cls) ?>"><?= $e($label) ?></span>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-1">
                                                <?php if ($c['status'] !== 'approved'): ?>
                                                    <button class="btn-action"
                                                        title="تأیید"
                                                        data-admin-post="../backend/api/admin_comments.php"
                                                        data-admin-fields='{"action":"approve","comment_id":<?= (int) $c['id'] ?>}'
                                                        data-admin-confirm="تأیید شود؟">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                <?php endif; ?>
                                                <?php if ($c['status'] !== 'rejected'): ?>
                                                    <button class="btn-action"
                                                        title="رد"
                                                        data-admin-post="../backend/api/admin_comments.php"
                                                        data-admin-fields='{"action":"reject","comment_id":<?= (int) $c['id'] ?>}'
                                                        data-admin-confirm="رد شود؟">
                                                        <i class="fas fa-ban"></i>
                                                    </button>
                                                <?php endif; ?>
                                                <button class="btn-action btn-cancel"
                                                    title="حذف"
                                                    data-admin-post="../backend/api/admin_comments.php"
                                                    data-admin-fields='{"action":"delete","comment_id":<?= (int) $c['id'] ?>}'
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
                            <div class="pagination-wrap mt-3">
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
