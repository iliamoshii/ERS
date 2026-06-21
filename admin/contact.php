<?php
/**
 * Admin — Contact Messages Inbox
 */

declare(strict_types=1);

require_once __DIR__ . '/../backend/bootstrap.php';

use Core\Auth;
use Core\CSRF;
use Core\Response;
use Middleware\AuthMiddleware;
use Models\ContactMessage;

AuthMiddleware::requireAdmin();

$admin = Auth::user();
$e     = fn($v) => Response::e($v);

$model = new ContactMessage();

$allowedFilters = ['', 'unread', 'read'];
$filter = $_GET['filter'] ?? '';
$filter = in_array($filter, $allowedFilters, true) ? $filter : '';

$search = trim((string) ($_GET['q'] ?? ''));
$page   = max(1, (int) ($_GET['page'] ?? 1));

$result   = $model->paginate($page, PER_PAGE_ADMIN, $search, $filter);
$messages = $result['data'];
$total    = $result['total'];
$totalPages = max(1, (int) ceil($total / PER_PAGE_ADMIN));

$totalCount  = $model->count();
$unreadCount = $model->countUnread();
$readCount   = $totalCount - $unreadCount;

function pageUrl(int $p, string $filter, string $search): string
{
    $params = ['page' => $p];
    if ($filter !== '') $params['filter'] = $filter;
    if ($search !== '') $params['q']      = $search;
    return 'contact.php?' . http_build_query($params);
}

$pageTitle = 'پیام‌های تماس';
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

            <!-- Stats row -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-4">
                    <div class="stat-card stat-total">
                        <div class="stat-icon"><i class="fas fa-envelope"></i></div>
                        <div class="stat-info">
                            <span class="stat-num"><?= $totalCount ?></span>
                            <span class="stat-lbl">کل پیام‌ها</span>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-4">
                    <div class="stat-card stat-pending">
                        <div class="stat-icon"><i class="fas fa-envelope-open"></i></div>
                        <div class="stat-info">
                            <span class="stat-num"><?= $unreadCount ?></span>
                            <span class="stat-lbl">خوانده‌نشده</span>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-4">
                    <div class="stat-card stat-approved">
                        <div class="stat-icon"><i class="fas fa-envelope-open-text"></i></div>
                        <div class="stat-info">
                            <span class="stat-num"><?= $readCount ?></span>
                            <span class="stat-lbl">خوانده‌شده</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter + search -->
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <div class="filter-tabs mb-0">
                    <?php
                    $tabs = ['' => 'همه', 'unread' => 'خوانده‌نشده', 'read' => 'خوانده‌شده'];
                    foreach ($tabs as $val => $label):
                    ?>
                        <a href="<?= $e(pageUrl(1, $val, $search)) ?>"
                           class="filter-tab <?= $filter === $val ? 'active' : '' ?>">
                            <?= $e($label) ?>
                            <?php if ($val === 'unread' && $unreadCount > 0): ?>
                                <span class="filter-count"><?= $unreadCount ?></span>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>

                <form method="get" class="d-flex gap-2">
                    <?php if ($filter !== ''): ?>
                        <input type="hidden" name="filter" value="<?= $e($filter) ?>">
                    <?php endif; ?>
                    <input type="text" name="q" value="<?= $e($search) ?>"
                           placeholder="نام، ایمیل، موضوع یا متن..."
                           class="form-control-panel" style="max-width:260px;">
                    <button type="submit" class="btn-action"><i class="fas fa-search"></i></button>
                    <?php if ($search !== ''): ?>
                        <a href="<?= $e(pageUrl(1, $filter, '')) ?>" class="btn-action">
                            <i class="fas fa-times"></i>
                        </a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Messages -->
            <div class="panel-card">
                <div class="panel-card-header">
                    <h6><i class="fas fa-inbox"></i> صندوق پیام‌ها (<?= $total ?>)</h6>
                </div>
                <div class="panel-card-body">
                    <?php if (empty($messages)): ?>
                        <div class="empty-state">
                            <i class="fas fa-inbox"></i>
                            <h4>پیامی یافت نشد</h4>
                            <p>صندوق پیام‌ها خالی است.</p>
                        </div>
                    <?php else: ?>
                        <div class="contact-list">
                            <?php foreach ($messages as $m): ?>
                                <div class="contact-item <?= !$m['is_read'] ? 'unread' : '' ?>"
                                     id="msg-<?= (int) $m['id'] ?>">

                                    <!-- Message header (always visible) -->
                                    <div class="contact-header" onclick="toggleMessage(<?= (int) $m['id'] ?>)">
                                        <div class="contact-meta">
                                            <span class="contact-unread-dot <?= !$m['is_read'] ? 'visible' : '' ?>"></span>
                                            <div class="contact-sender">
                                                <span class="contact-name"><?= $e($m['name']) ?></span>
                                                <span class="contact-email"><?= $e($m['email']) ?></span>
                                            </div>
                                        </div>
                                        <div class="contact-subject-wrap">
                                            <span class="contact-subject"><?= $e($m['subject']) ?></span>
                                            <span class="contact-preview"><?= $e(mb_substr($m['message'], 0, 70)) ?>…</span>
                                        </div>
                                        <div class="contact-right">
                                            <span class="contact-date"><?= $e(substr($m['created_at'], 0, 10)) ?></span>
                                            <i class="fas fa-chevron-down contact-arrow" id="arrow-<?= (int) $m['id'] ?>"></i>
                                        </div>
                                    </div>

                                    <!-- Expanded body -->
                                    <div class="contact-body" id="body-<?= (int) $m['id'] ?>" style="display:none;">
                                        <div class="contact-body-meta">
                                            <span><i class="fas fa-user"></i> <?= $e($m['name']) ?></span>
                                            <span><i class="fas fa-envelope"></i> <?= $e($m['email']) ?></span>
                                            <?php if ($m['phone']): ?>
                                                <span><i class="fas fa-phone"></i> <?= $e($m['phone']) ?></span>
                                            <?php endif; ?>
                                            <span><i class="fas fa-clock"></i> <?= $e($m['created_at']) ?></span>
                                            <?php if ($m['ip_address']): ?>
                                                <span><i class="fas fa-network-wired"></i> <?= $e($m['ip_address']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <p class="contact-body-text"><?= nl2br($e($m['message'])) ?></p>

                                        <div class="contact-actions">
                                            <?php if (!$m['is_read']): ?>
                                                <button class="btn-action"
                                                    data-admin-post="../backend/api/admin_contact.php"
                                                    data-admin-fields='{"action":"mark_read","message_id":<?= (int) $m['id'] ?>}'>
                                                    <i class="fas fa-envelope-open"></i> علامت خوانده‌شده
                                                </button>
                                            <?php else: ?>
                                                <button class="btn-action"
                                                    data-admin-post="../backend/api/admin_contact.php"
                                                    data-admin-fields='{"action":"mark_unread","message_id":<?= (int) $m['id'] ?>}'>
                                                    <i class="fas fa-envelope"></i> علامت خوانده‌نشده
                                                </button>
                                            <?php endif; ?>
                                            <button class="btn-action btn-cancel"
                                                data-admin-post="../backend/api/admin_contact.php"
                                                data-admin-fields='{"action":"delete","message_id":<?= (int) $m['id'] ?>}'
                                                data-admin-confirm="پیام حذف شود؟">
                                                <i class="fas fa-trash"></i> حذف
                                            </button>
                                        </div>
                                    </div>

                                </div>
                            <?php endforeach; ?>
                        </div>

                        <?php if ($totalPages > 1): ?>
                            <div class="pagination-wrap mt-3">
                                <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                                    <a href="<?= $e(pageUrl($p, $filter, $search)) ?>"
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

/** Toggle expand/collapse of an individual message row */
function toggleMessage(id) {
    const body  = document.getElementById('body-' + id);
    const arrow = document.getElementById('arrow-' + id);
    if (!body) return;
    const isOpen = body.style.display !== 'none';
    body.style.display  = isOpen ? 'none' : 'block';
    arrow.style.transform = isOpen ? '' : 'rotate(180deg)';

    // Auto-mark as read when first opened
    const item = document.getElementById('msg-' + id);
    if (!isOpen && item && item.classList.contains('unread')) {
        const fd = new FormData();
        fd.append('action',     'mark_read');
        fd.append('message_id', id);
        fd.append('csrf_token', CSRF_TOKEN);
        fetch('../backend/api/admin_contact.php', { method: 'POST', credentials: 'same-origin', body: fd })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    item.classList.remove('unread');
                    const dot = item.querySelector('.contact-unread-dot');
                    if (dot) dot.classList.remove('visible');
                }
            });
    }
}
</script>
</body>
</html>
