<?php
/**
 * API: Booking (Reservation Creation)
 * POST /backend/api/booking.php?action=create
 * Auth required.
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/backend/bootstrap.php';

use Core\Auth;
use Core\CSRF;
use Core\Response;
use Core\ActivityLogger;
use Middleware\AuthMiddleware;
use Models\Location;
use Models\Reservation;
use Config\Database;

header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('روش درخواست پشتیبانی نمی‌شود.', [], 405);
}

AuthMiddleware::requireLogin(true);
CSRF::verifyRequest();

$action = trim($_POST['action'] ?? 'create');

if ($action !== 'create') {
    Response::error('اکشن نامعتبر است.', [], 400);
}

$user = Auth::user();

$locationId = (int) ($_POST['location_id'] ?? 0);
$date       = trim($_POST['reservation_date'] ?? '');
$startTime  = trim($_POST['start_time']       ?? '');

if ($locationId <= 0 || $date === '' || $startTime === '') {
    Response::error('اطلاعات رزرو ناقص است.');
}

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    Response::error('فرمت تاریخ نامعتبر است.');
}
if ($date < date('Y-m-d')) {
    Response::error('تاریخ انتخابی گذشته است.');
}
if (!preg_match('/^\d{2}:\d{2}$/', $startTime)) {
    Response::error('فرمت ساعت نامعتبر است.');
}

$locModel = new Location();
$location = $locModel->findById($locationId);
if (!$location || $location['status'] !== 'active') {
    Response::error('مکان یافت نشد.', [], 404);
}

// Find the matching slot template for this day/time to get end_time + price
$dayOfWeek = (int) date('w', strtotime($date));
$db   = Database::getInstance();
$stmt = $db->prepare(
    'SELECT * FROM available_slots
      WHERE location_id = ? AND day_of_week = ? AND start_time = ? AND is_active = 1
      LIMIT 1'
);
$stmt->execute([$locationId, $dayOfWeek, $startTime . ':00']);
$slot = $stmt->fetch();

if (!$slot) {
    Response::error('این سانس برای این مکان در این روز تعریف نشده است.');
}

$endTime = $slot['end_time'];
$price   = $slot['price_override'] ?? $location['price_per_session'];

$resModel = new Reservation();

// Prevent double-booking the exact same slot
if ($resModel->isDuplicate($locationId, $date, $startTime . ':00')) {
    Response::error('این سانس قبلاً رزرو شده است. لطفاً سانس دیگری انتخاب کنید.');
}

$reservationId = $resModel->create([
    'user_id'           => $user['id'],
    'location_id'       => $locationId,
    'slot_id'           => (int) $slot['id'],
    'reservation_date'  => $date,
    'start_time'        => $startTime . ':00',
    'end_time'          => $endTime,
    'people_count'      => (int) ($_POST['people_count'] ?? 1),
    'notes'             => trim($_POST['notes'] ?? '') ?: null,
    'total_price'       => $price,
]);

if (!$reservationId) {
    Response::error('ثبت رزرو انجام نشد. لطفاً دوباره تلاش کنید.');
}

ActivityLogger::log(
    'reservation_created',
    "رزرو جدید برای «{$location['title']}» در {$date} ساعت {$startTime}",
    'reservation',
    $reservationId,
    null,
    $user['id']
);

Response::success('رزرو شما با موفقیت ثبت شد و در انتظار تأیید مدیر است.', [
    'reservation_id' => $reservationId,
]);
