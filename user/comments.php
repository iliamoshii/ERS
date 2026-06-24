<?php
/**
 * User Panel — My Comments
 */

require_once dirname(__DIR__) . '/backend/bootstrap.php';

use Core\Auth;
use Core\CSRF;
use Core\Response;
use Middleware\AuthMiddleware;
use Models\Comment;

// بررسی وضعیت ورود کاربر
AuthMiddleware::requireLogin();

$user = Auth::user();
$commentModel = new Comment();

// استفاده از متد بومی موجود در مدل شما برای دریافت نظرات کاربر
$myComments = $commentModel->forUser($user['id']);

// هلپر برای Escape کردن امن خروجی‌ها
use Core\Response as R;
$e = fn($v) => R::e($v);

// هلپرهای استایل‌دهی وضعیت نظر
function statusLabel(string $s): string {
    return match($s) {
        'pending'  => 'در انتظار تأیید',
        'approved' => 'منتشر شده',
        'rejected' => 'رد شده',
        default    => $s,
    };
}
function statusClass(string $s): string {
    return match($s) {
        'pending'  => 'warning',
        'approved' => 'success',
        'rejected' => 'danger',
        default    => 'secondary',
    };
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>نظرات من | سامانه رزرو استادیوم</title>
    <link href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/FontAwesome.Pro.7.1.0/css/all.css">
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/popup.css">
    <link rel="stylesheet" href="../assets/css/user-panel.css">
</head>
<body class="user-panel-page">

<?php include __DIR__ . '/partials/sidebar.php'; ?>

<div class="panel-main" id="panelMain">

    <?php include __DIR__ . '/partials/topbar.php'; ?>

    <div class="panel-content">

        <div class="page-header">
            <h1>نظرات من</h1>
            <div class="breadcrumb-nav">
                <a href="dashboard.php">داشبورد</a>
                <i class="fas fa-chevron-left"></i>
                <span>نظرات من</span>
            </div>
        </div>

        <div class="panel-card">
            <div class="panel-card-body">
                <?php if (empty($myComments)): ?>
                    <div class="empty-state">
                        <i class="fas fa-comment-slash"></i>
                        <h4>هیچ نظری یافت نشد</h4>
                        <p>شما تاکنون هیچ نظری برای مجموعه‌های ورزشی ثبت نکرده‌اید.</p>
                        <a href="../service.html" class="theme-btn btn-sm mt-3">مشاهده زمین‌ها</a>
                    </div>
                <?php else: ?>
                    <div class="comments-list">
                        <?php foreach ($myComments as $c): ?>
                            <div class="comment-item" id="comment-card-<?= $c['id'] ?>">
                                <div class="comment-meta">
                                    <a href="../location.php?id=<?= $c['location_id'] ?>" class="comment-location">
                                        <i class="fas fa-map-marker-alt ms-1"></i> <?= $e($c['location_title']) ?>
                                    </a>
                                    <span class="comment-date"><?= $e(substr($c['created_at'], 0, 10)) ?></span>
                                    <span class="status-badge badge-<?= statusClass($c['status']) ?>">
                                        <?= statusLabel($c['status']) ?>
                                    </span>
                                </div>

                                <?php if (!empty($c['rating'])): ?>
                                    <div class="star-rating mb-2">
                                        <?php for ($s = 1; $s <= 5; $s++): ?>
                                            <i class="fas fa-star<?= $s > (int)$c['rating'] ? ' empty' : '' ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                <?php endif; ?>

                                <p class="comment-body" id="comment-body-<?= $c['id'] ?>"><?= nl2br($e($c['body'])) ?></p>

                                <div class="comment-actions mt-3 d-flex gap-2">
                                    <button type="button" class="btn-neon ghost sm"
                                            onclick="openEditModal(<?= $c['id'] ?>)"
                                            data-raw-body="<?= $e($c['body']) ?>"
                                            data-raw-rating="<?= (int)$c['rating'] ?>"
                                            id="edit-data-<?= $c['id'] ?>">
                                        <i class="fas fa-pen"></i> ویرایش
                                    </button>
                                    <button type="button" class="btn-neon danger sm"
                                            onclick="openDeleteModal(<?= $c['id'] ?>)">
                                        <i class="fas fa-trash"></i> حذف
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div></div><div class="modal-overlay" id="editModal">
    <div class="modal-box">
        <div class="modal-icon" style="background: rgba(57,255,20,0.12); color: #39FF14;">
            <i class="fas fa-pen"></i>
        </div>
        <h5>ویرایش نظر</h5>
        <form id="editForm">
            <input type="hidden" name="comment_id" id="editCommentId">
            <input type="hidden" name="rating" id="editCommentRating">
            <div class="field-group mt-3" style="text-align: right;">
                <label class="field-label">متن نظر</label>
                <textarea name="body" id="editCommentBody" class="field-input" rows="4" required></textarea>
            </div>
            <p class="mt-2 text-warning" style="font-size: 11px; text-align: right;">
                <i class="fas fa-info-circle"></i> پس از ویرایش، نظر مجدداً جهت بررسی به وضعیت «در انتظار تأیید» منتقل می‌شود.
            </p>
            <div class="modal-actions mt-4">
                <button type="button" class="btn-outline" onclick="closeModals()">انصراف</button>
                <button type="submit" class="theme-btn btn-neon primary w-100" id="editSubmitBtn">
                    ذخیره تغییرات
                </button>
            </div>
        </form>
    </div>
</div>

<div class="modal-overlay" id="deleteModal">
    <div class="modal-box">
        <div class="modal-icon">
            <i class="fas fa-trash"></i>
        </div>
        <h5>حذف نظر</h5>
        <p>آیا از حذف این نظر اطمینان دارید؟ این عمل غیرقابل بازگشت است.</p>
        <div class="modal-actions mt-4">
            <button type="button" class="btn-outline" onclick="closeModals()">انصراف</button>
            <button type="button" class="theme-btn btn-neon danger w-100" id="confirmDeleteBtn">
                بله، حذف شود
            </button>
        </div>
    </div>
</div>

<script src="../assets/js/jquery-3.6.0.min.js"></script>
<script src="../assets/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/popup.js"></script>
<script src="../assets/js/user-panel.js"></script>
<script>
    const CSRF_TOKEN = '<?= CSRF::token() ?>';
    let activeCommentId = null;

    function openEditModal(id) {
        activeCommentId = id;
        const btnData = document.getElementById(`edit-data-${id}`);
        const rawBody = btnData.getAttribute('data-raw-body');
        const rawRating = btnData.getAttribute('data-raw-rating');

        document.getElementById('editCommentId').value = id;
        document.getElementById('editCommentRating').value = rawRating;
        document.getElementById('editCommentBody').value = rawBody;
        document.getElementById('editModal').classList.add('show');
    }

    function openDeleteModal(id) {
        activeCommentId = id;
        document.getElementById('deleteModal').classList.add('show');
    }

    function closeModals() {
        activeCommentId = null;
        document.getElementById('editModal').classList.remove('show');
        document.getElementById('deleteModal').classList.remove('show');
    }

    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) closeModals();
        });
    });

    // ارسال درخواست ویرایش به API
    document.getElementById('editForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = document.getElementById('editSubmitBtn');
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

        const fd = new FormData(e.target);
        fd.append('action', 'update');
        fd.append('csrf_token', CSRF_TOKEN);

        try {
            const res = await fetch('../backend/api/user_comments.php', {
                method: 'POST',
                credentials: 'same-origin',
                body: fd
            });
            const data = await res.json();

            if (data.success) {
                // اعمال تغییرات در DOM (آپدیت فرانت بدون رفرش)
                const updatedText = document.getElementById('editCommentBody').value;
                document.getElementById(`comment-body-${activeCommentId}`).innerText = updatedText;
                document.getElementById(`edit-data-${activeCommentId}`).setAttribute('data-raw-body', updatedText);

                closeModals();
                window.Popup?.success('موفق', 'نظر شما ویرایش شد و در انتظار تایید مجدد قرار گرفت.');
                setTimeout(() => location.reload(), 1200); // به دلیل تغییر وضعیت استاتوس به در انتظار تأیید رفرش اختیاری است
            } else {
                window.Popup?.error('خطا', data.message || 'مشکلی پیش آمد.');
            }
        } catch (err) {
            window.Popup?.error('خطا', 'ارتباط با سرور برقرار نشد.');
        } finally {
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    });

    // ارسال درخواست حذف به API
    document.getElementById('confirmDeleteBtn').addEventListener('click', async () => {
        const btn = document.getElementById('confirmDeleteBtn');
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

        const fd = new FormData();
        fd.append('action', 'delete');
        fd.append('comment_id', activeCommentId);
        fd.append('csrf_token', CSRF_TOKEN);

        try {
            const res = await fetch('../backend/api/user_comments.php', {
                method: 'POST',
                credentials: 'same-origin',
                body: fd
            });
            const data = await res.json();

            if (data.success) {
                const card = document.getElementById(`comment-card-${activeCommentId}`);
                card.style.opacity = '0';
                setTimeout(() => {
                    card.remove();
                    if (!document.querySelectorAll('.comment-item').length) {
                        location.reload();
                    }
                }, 300);

                closeModals();
                window.Popup?.success('موفق', 'نظر شما با موفقیت حذف شد.');
            } else {
                window.Popup?.error('خطا', data.message || 'امکان حذف نظر وجود ندارد.');
            }
        } catch (err) {
            window.Popup?.error('خطا', 'ارتباط با سرور برقرار نشد.');
        } finally {
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    });
</script>
</body>
</html>