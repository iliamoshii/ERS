<?php
/**
 * API: Admin Available-Slots Management
 * POST /backend/api/admin_slots.php
 * Actions: create | update | delete | toggle_active
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/backend/bootstrap.php';

use Core\Auth;
use Core\CSRF;
use Core\Response;
use Core\Validator;
use Core\ActivityLogger;
use Middleware\AuthMiddleware;
use Models\Location;

header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('روش درخواست پشتیبانی نمی‌شود.', [], 405);
}

AuthMiddleware::requireAdmin(true);
CSRF::verifyRequest();

$admin  = Auth::user();
$model  = new Location();
$action = trim($_POST['action'] ?? '');

match ($action) {
    'create'        => createSlot($admin, $model),
    'update'        => updateSlot($admin, $model),
    'delete'        => deleteSlot($admin, $model),
    'toggle_active' => toggleActive($admin, $model),
    default         => Response::error('اکشن نامعتبر است.', [], 400),
};

// ── Shared field validation ───────────────────────────────────
function validateFields(): array
{
    $v = new Validator($_POST);
    $v->required('location_id', 'مکان')
      ->numeric('location_id', 'مکان')
      ->required('day_of_week', 'روز هفته')
      ->required('start_time',  'ساعت شروع')
      ->required('end_time',    'ساعت پایان');

    if ($v->fails()) {
        Response::error($v->firstError());
    }

    $dayOfWeek = (int) $_POST['day_of_week'];
    if ($dayOfWeek < 0 || $dayOfWeek > 6) {
        Response::error('روز هفته نامعتبر است.');
    }

    $startTime = trim($_POST['start_time']);
    $endTime   = trim($_POST['end_time']);
    if (!preg_match('/^\d{2}:\d{2}$/', $startTime) || !preg_match('/^\d{2}:\d{2}$/', $endTime)) {
        Response::error('فرمت ساعت نامعتبر است.');
    }
    if ($startTime >= $endTime) {
        Response::error('ساعت پایان باید بعد از ساعت شروع باشد.');
    }

    $priceOverride = null;
    if (isset($_POST['price_override']) && trim((string) $_POST['price_override']) !== '') {
        $priceOverride = (int) $_POST['price_override'];
        if ($priceOverride < 0) {
            Response::error('قیمت اختصاصی نمی‌تواند منفی باشد.');
        }
    }

    return [
        'location_id'     => (int) $_POST['location_id'],
        'day_of_week'     => $dayOfWeek,
        'start_time'      => $startTime . ':00',
        'end_time'        => $endTime . ':00',
        'price_override'  => $priceOverride,
        'is_active'       => 1,
    ];
}

// ── Create ──────────────────────────────────────────────────
function createSlot(array $admin, Location $model): never
{
    $data = validateFields();

    $location = $model->findById($data['location_id']);
    if (!$location) {
        Response::error('مکان یافت نشد.', [], 404);
    }

    $id = $model->createSlot($data);

    ActivityLogger::log(
        'slot_created',
        "سانس جدید برای مکان «{$location['title']}» ایجاد شد",
        'available_slot',
        $id,
        null,
        $admin['id']
    );

    Response::success('سانس با موفقیت ایجاد شد.', ['id' => $id]);
}

// ── Update ──────────────────────────────────────────────────
function updateSlot(array $admin, Location $model): never
{
    $slotId = (int) ($_POST['slot_id'] ?? 0);
    if ($slotId <= 0) {
        Response::error('شناسه سانس نامعتبر است.');
    }

    $existing = $model->findSlotById($slotId);
    if (!$existing) {
        Response::error('سانس یافت نشد.', [], 404);
    }

    $data = validateFields();
    $ok   = $model->updateSlot($slotId, $data);

    if (!$ok) {
        Response::error('بروزرسانی سانس انجام نشد.');
    }

    ActivityLogger::log(
        'slot_updated',
        "سانس شماره {$slotId} ویرایش شد",
        'available_slot',
        $slotId,
        null,
        $admin['id']
    );

    Response::success('سانس با موفقیت بروزرسانی شد.');
}

// ── Delete ──────────────────────────────────────────────────
function deleteSlot(array $admin, Location $model): never
{
    $slotId = (int) ($_POST['slot_id'] ?? 0);
    if ($slotId <= 0) {
        Response::error('شناسه سانس نامعتبر است.');
    }

    if (!$model->findSlotById($slotId)) {
        Response::error('سانس یافت نشد.', [], 404);
    }

    $model->deleteSlot($slotId);

    ActivityLogger::log(
        'slot_deleted',
        "سانس شماره {$slotId} حذف شد",
        'available_slot',
        $slotId,
        null,
        $admin['id']
    );

    Response::success('سانس با موفقیت حذف شد.');
}

// ── Toggle active/inactive ────────────────────────────────────
function toggleActive(array $admin, Location $model): never
{
    $slotId = (int) ($_POST['slot_id'] ?? 0);
    $slot   = $model->findSlotById($slotId);

    if (!$slot) {
        Response::error('سانس یافت نشد.', [], 404);
    }

    $newState = $slot['is_active'] ? 0 : 1;
    $model->updateSlot($slotId, [
        'day_of_week'    => $slot['day_of_week'],
        'start_time'     => $slot['start_time'],
        'end_time'       => $slot['end_time'],
        'price_override' => $slot['price_override'],
        'is_active'      => $newState,
    ]);

    ActivityLogger::log(
        'slot_toggled',
        "وضعیت سانس شماره {$slotId} تغییر کرد",
        'available_slot',
        $slotId,
        null,
        $admin['id']
    );

    Response::success($newState ? 'سانس فعال شد.' : 'سانس غیرفعال شد.');
}
