<?php
/**
 * Admin — Users Management
 */

declare(strict_types=1);

require_once __DIR__ . '/../backend/bootstrap.php';

use Core\Auth;
use Core\CSRF;
use Core\Response;
use Middleware\AuthMiddleware;
use Models\User;

AuthMiddleware::requireAdmin();

$admin = Auth::user();
$e     = fn($v) => Response::e($v);

$userModel = new User();

$allowedStatuses = ['', 'active', 'suspended'];
$status = $_GET['status'] ?? '';
$status = in_array($status, $allowedStatuses, true) ? $status : '';

$search = trim((string) ($_GET['q'] ?? ''));
$page   = max(1, (int) ($_GET['page'] ?? 1));

$result     = $userModel->paginate($page, PER_PAGE_ADMIN, $search, $status);
$users      = $result['data'];
$total      = $result['total'];
$totalPages = max(1, (int) ceil($total / PER_PAGE_ADMIN));

function pageUrl(int $p, string $status, string $search): string
{
    $params = ['page' => $p];
    if ($status !== '') $params['status'] = $status;
    if ($search !== '') $params['q']      = $search;
    return 'users.php?' . http_build_query($params);
}

$pageTitle = 'مدیریت کاربران';
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
                <?php $tabs = ['' => 'همه', 'active' => 'فعال', 'suspended' => 'مسدود']; ?>
                <?php foreach ($tabs as $val => $label): ?>
                    <a href="<?= $e(pageUrl(1, $val, $search)) ?>" class="filter-tab <?= $status === $val ? 'active' : '' ?>">
                        <?= $e($label) ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <div class="panel-card">
                <div class="panel-card-header">
                    <h6><i class="fas fa-users"></i> کاربران (<?= $total ?>)</h6>
                    <form method="get" class="d-flex gap-2">
                        <?php if ($status !== ''): ?><input type="hidden" name="status" value="<?= $e($status) ?>"><?php endif; ?>
                        <input type="text" name="q" value="<?= $e($search) ?>" placeholder="نام، یوزرنیم، ایمیل یا تلفن..."
                               class="form-control-panel" style="max-width:240px;">
                        <button type="submit" class="btn-action"><i class="fas fa-search"></i></button>
                    </form>
                </div>
                <div class="panel-card-body">
                    <?php if (empty($users)): ?>
                        <div class="empty-state">
                            <i class="fas fa-user-slash"></i>
                            <h4>کاربری یافت نشد</h4>
                        </div>
                    <?php else: ?>
                        <div class="res-table-wrap">
                            <table class="res-table">
                                <thead>
                                    <tr>
                                        <th>#</th><th>نام</th><th>یوزرنیم</th><th>تلفن</th>
                                        <th>نقش</th><th>وضعیت</th><th>عضویت</th><th>عملیات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($users as $u): $isSelf = (int) $u['id'] === (int) $admin['id']; ?>
                                    <tr>
                                        <td>#<?= (int) $u['id'] ?></td>
                                        <td><?= $e($u['full_name']) ?></td>
                                        <td>@<?= $e($u['username']) ?></td>
                                        <td><?= $e($u['phone']) ?></td>
                                        <td>
                                            <span class="role-badge <?= (int) $u['role_id'] === 1 ? 'role-admin' : 'role-user' ?>">
                                                <?= $e($u['role_name']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge-status <?= $u['status'] === 'active' ? 'badge-success' : 'badge-danger' ?>">
                                                <?= $u['status'] === 'active' ? 'فعال' : 'مسدود' ?>
                                            </span>
                                        </td>
                                        <td><?= $e(substr($u['created_at'], 0, 10)) ?></td>
                                        <td>
                                            <?php if ($isSelf): ?>
                                                <span class="text-muted">حساب شما</span>
                                            <?php else: ?>
                                                <div class="d-flex gap-1">
                                                    <?php if ((int) $u['role_id'] === 1): ?>
                                                        <button class="btn-action"
                                                            data-admin-post="../backend/api/admin_users.php"
                                                            data-admin-fields='{"action":"update_role","user_id":<?= (int) $u['id'] ?>,"role_id":2}'
                                                            data-admin-confirm="تنزل به کاربر عادی؟">
                                                            <i class="fas fa-arrow-down"></i>
                                                        </button>
                                                    <?php else: ?>
                                                        <button class="btn-action"
                                                            data-admin-post="../backend/api/admin_users.php"
                                                            data-admin-fields='{"action":"update_role","user_id":<?= (int) $u['id'] ?>,"role_id":1}'
                                                            data-admin-confirm="ارتقا به مدیر؟">
                                                            <i class="fas fa-arrow-up"></i>
                                                        </button>
                                                    <?php endif; ?>

                                                    <?php if ($u['status'] === 'active'): ?>
                                                        <button class="btn-action btn-cancel"
                                                            data-admin-post="../backend/api/admin_users.php"
                                                            data-admin-fields='{"action":"update_status","user_id":<?= (int) $u['id'] ?>,"status":"suspended"}'
                                                            data-admin-confirm="مسدود شود؟">
                                                            <i class="fas fa-ban"></i>
                                                        </button>
                                                    <?php else: ?>
                                                        <button class="btn-action"
                                                            data-admin-post="../backend/api/admin_users.php"
                                                            data-admin-fields='{"action":"update_status","user_id":<?= (int) $u['id'] ?>,"status":"active"}'
                                                            data-admin-confirm="فعال شود؟">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
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
