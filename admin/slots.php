<?php
/**
 * Admin — Available Slots Management
 * Select a location, manage its weekly recurring slot template.
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

$locModel  = new Location();
$locations = $locModel->allActive();

$selectedId = (int) ($_GET['location_id'] ?? 0);
$selected   = null;
$slots      = [];

if ($selectedId > 0) {
    $selected = $locModel->findById($selectedId);
    if ($selected) {
        $slots = $locModel->allSlots($selectedId);
    }
}

$dayNames = ['یکشنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه', 'پنج‌شنبه', 'جمعه', 'شنبه'];

// Group slots by day for grid display
$slotsByDay = array_fill(0, 7, []);
foreach ($slots as $s) {
    $slotsByDay[(int) $s['day_of_week']][] = $s;
}

$pageTitle = 'مدیریت سانس‌ها';
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

            <!-- Location picker -->
            <div class="panel-card mb-3">
                <div class="panel-card-body">
                    <form method="get" class="d-flex gap-2 flex-wrap align-items-end">
                        <div style="flex:1; min-width:240px;">
                            <label class="form-label-panel"><i class="fas fa-map-marker-alt"></i> انتخاب مکان</label>
                            <select name="location_id" class="form-control-panel" onchange="this.form.submit()">
                                <option value="">— یک مکان را انتخاب کنید —</option>
                                <?php foreach ($locations as $l): ?>
                                    <option value="<?= (int) $l['id'] ?>" <?= $selectedId === (int) $l['id'] ? 'selected' : '' ?>>
                                        <?= $e($l['title']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </form>
                </div>
            </div>

            <?php if (!$selected): ?>
                <div class="empty-state">
                    <i class="fas fa-calendar-week"></i>
                    <h4>یک مکان را انتخاب کنید</h4>
                    <p>برای مدیریت سانس‌های هفتگی، ابتدا مکان مورد نظر را از لیست بالا انتخاب کنید.</p>
                </div>
            <?php else: ?>

                <div class="panel-card mb-3">
                    <div class="panel-card-header">
                        <h6><i class="fas fa-calendar-week"></i> سانس‌های هفتگی — <?= $e($selected['title']) ?></h6>
                        <button class="theme-btn submit-btn" id="addSlotBtn" style="white-space:nowrap;">
                            <span class="btn-text"><i class="fas fa-plus"></i> سانس جدید</span>
                        </button>
                    </div>
                    <div class="panel-card-body">
                        <p style="color:rgba(255,255,255,0.5); font-size:13px; margin-bottom:18px;">
                            قیمت اختصاصی خالی باشد، قیمت پایه مکان (<?= number_format((float) $selected['price_per_session']) ?> تومان) اعمال می‌شود.
                        </p>

                        <div class="row g-3">
                            <?php foreach ($dayNames as $dow => $dayLabel): ?>
                                <div class="col-md-6 col-lg-4">
                                    <div class="panel-card" style="margin:0; background:rgba(255,255,255,0.02);">
                                        <div class="panel-card-header" style="padding:14px 16px;">
                                            <h6 style="font-size:14px;"><?= $e($dayLabel) ?></h6>
                                        </div>
                                        <div class="panel-card-body" style="padding:10px 16px 16px;">
                                            <?php if (empty($slotsByDay[$dow])): ?>
                                                <p style="font-size:12px; color:rgba(255,255,255,0.35); margin:0;">سانسی تعریف نشده</p>
                                            <?php else: ?>
                                                <?php foreach ($slotsByDay[$dow] as $s): ?>
                                                    <div class="d-flex align-items-center justify-content-between mb-2"
                                                         style="padding:8px 10px; border-radius:8px; background:rgba(255,255,255,0.03);">
                                                        <div>
                                                            <span style="font-size:12px; font-weight:600; color:<?= $s['is_active'] ? '#fff' : 'rgba(255,255,255,0.35)' ?>;">
                                                                <?= substr($s['start_time'], 0, 5) ?> - <?= substr($s['end_time'], 0, 5) ?>
                                                            </span>
                                                            <?php if ($s['price_override']): ?>
                                                                <span style="font-size:11px; color:#39FF14; margin-right:4px;">
                                                                    (<?= number_format((float) $s['price_override']) ?>)
                                                                </span>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="d-flex gap-1">
                                                            <button class="btn-action slot-edit-btn"
                                                                data-id="<?= (int) $s['id'] ?>"
                                                                data-day="<?= $dow ?>"
                                                                data-start="<?= substr($s['start_time'], 0, 5) ?>"
                                                                data-end="<?= substr($s['end_time'], 0, 5) ?>"
                                                                data-price="<?= $s['price_override'] ?? '' ?>"
                                                                style="padding:4px 8px; font-size:11px;">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                            <button class="btn-action"
                                                                data-admin-post="../backend/api/admin_slots.php"
                                                                data-admin-fields='{"action":"toggle_active","slot_id":<?= (int) $s['id'] ?>}'
                                                                style="padding:4px 8px; font-size:11px;">
                                                                <i class="fas fa-power-off"></i>
                                                            </button>
                                                            <button class="btn-action btn-cancel"
                                                                data-admin-post="../backend/api/admin_slots.php"
                                                                data-admin-fields='{"action":"delete","slot_id":<?= (int) $s['id'] ?>}'
                                                                data-admin-confirm="حذف شود؟"
                                                                style="padding:4px 8px; font-size:11px;">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Add/Edit Slot Modal -->
                <div class="modal-overlay" id="slotModal">
                    <div class="modal-box" style="text-align:right; max-width:420px;">
                        <h5 id="slotModalTitle" style="margin-bottom:18px;"><i class="fas fa-plus-circle"></i> سانس جدید</h5>
                        <form id="slotForm">
                            <input type="hidden" name="action" id="slotAction" value="create">
                            <input type="hidden" name="slot_id" id="slotId" value="">
                            <input type="hidden" name="location_id" value="<?= $selectedId ?>">
                            <input type="hidden" name="csrf_token" value="<?= $e(CSRF::token()) ?>">

                            <div class="mb-3">
                                <label class="form-label-panel">روز هفته</label>
                                <select name="day_of_week" id="slotDay" class="form-control-panel" required>
                                    <?php foreach ($dayNames as $dow => $dayLabel): ?>
                                        <option value="<?= $dow ?>"><?= $e($dayLabel) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <label class="form-label-panel">ساعت شروع</label>
                                    <input type="time" name="start_time" id="slotStart" class="form-control-panel" required>
                                </div>
                                <div class="col-6">
                                    <label class="form-label-panel">ساعت پایان</label>
                                    <input type="time" name="end_time" id="slotEnd" class="form-control-panel" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label-panel">قیمت اختصاصی (اختیاری)</label>
                                <input type="number" name="price_override" id="slotPrice" class="form-control-panel" placeholder="خالی = قیمت پایه مکان" min="0">
                            </div>
                            <div class="modal-actions">
                                <button type="button" class="btn-outline" id="slotCancelBtn">انصراف</button>
                                <button type="submit" class="theme-btn submit-btn"><span class="btn-text">ذخیره</span></button>
                            </div>
                        </form>
                    </div>
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

const slotModal   = document.getElementById('slotModal');
const slotForm    = document.getElementById('slotForm');

function openSlotModal(mode, data = {}) {
    document.getElementById('slotAction').value = mode === 'edit' ? 'update' : 'create';
    document.getElementById('slotId').value     = data.id    || '';
    document.getElementById('slotDay').value    = data.day   ?? 0;
    document.getElementById('slotStart').value  = data.start || '';
    document.getElementById('slotEnd').value    = data.end   || '';
    document.getElementById('slotPrice').value  = data.price || '';
    document.getElementById('slotModalTitle').innerHTML = mode === 'edit'
        ? '<i class="fas fa-edit"></i> ویرایش سانس'
        : '<i class="fas fa-plus-circle"></i> سانس جدید';
    slotModal?.classList.add('show');
}

document.getElementById('addSlotBtn')?.addEventListener('click', () => openSlotModal('create'));
document.getElementById('slotCancelBtn')?.addEventListener('click', () => slotModal.classList.remove('show'));

document.querySelectorAll('.slot-edit-btn').forEach((btn) => {
    btn.addEventListener('click', () => {
        openSlotModal('edit', {
            id: btn.dataset.id, day: btn.dataset.day,
            start: btn.dataset.start, end: btn.dataset.end, price: btn.dataset.price,
        });
    });
});

slotForm?.addEventListener('submit', async (e) => {
    e.preventDefault();
    document.getElementById('loadingOverlay').classList.add('active');

    try {
        const fd  = new FormData(slotForm);
        const res = await fetch('../backend/api/admin_slots.php', {
            method: 'POST', credentials: 'same-origin', body: fd,
        });
        const data = await res.json();

        if (data.success) {
            Popup.success('موفق', data.message);
            setTimeout(() => window.location.reload(), 900);
        } else {
            Popup.error('خطا', data.message || 'عملیات ناموفق بود.');
        }
    } catch (err) {
        Popup.error('خطای ارتباطی', 'ارتباط با سرور برقرار نشد.');
    } finally {
        document.getElementById('loadingOverlay').classList.remove('active');
    }
});
</script>
</body>
</html>
