<?php
/**
 * Public API: Locations
 * GET  /backend/api/locations.php?action=list[&search=&category=&city=]
 * GET  /backend/api/locations.php?action=detail&id=N
 * GET  /backend/api/locations.php?action=slots&id=N&date=YYYY-MM-DD
 * No auth required — public read-only endpoints.
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/backend/bootstrap.php';

use Core\Response;
use Models\Location;
use Models\Reservation;
use Config\Database;

header('X-Content-Type-Options: nosniff');
header('Cache-Control: public, max-age=60');   // 1-minute cache fine for listings

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::error('این endpoint فقط GET پشتیبانی می‌کند.', [], 405);
}

$action = trim($_GET['action'] ?? 'list');

match ($action) {
    'list'   => listLocations(),
    'detail' => locationDetail(),
    'slots'  => locationSlots(),
    default  => Response::error('اکشن نامعتبر است.', [], 400),
};

// ── List all active locations (with optional filters) ────────
function listLocations(): never
{
    $locModel   = new Location();
    $search     = trim($_GET['search']   ?? '');
    $categoryId = (int) ($_GET['category'] ?? 0);
    $city       = trim($_GET['city']     ?? '');

    $locations = $locModel->allActive($search, $categoryId, $city);

    // Add primary image URL + decode amenities (stored as raw JSON string in DB)
    foreach ($locations as &$loc) {
        $img = $locModel->getPrimaryImage((int) $loc['id']);
        $loc['primary_image_url'] = $img
            ? UPLOAD_URL . $img
            : null;
        $loc['rating_avg'] = (float) $loc['rating_avg'];
        $loc['amenities']  = $loc['amenities'] ? (json_decode($loc['amenities'], true) ?: []) : [];
    }
    unset($loc);

    Response::json(['success' => true, 'data' => $locations]);
}

// ── Single location detail (includes images, slots template, categories) ─
function locationDetail(): never
{
    $id       = (int) ($_GET['id'] ?? 0);
    $locModel = new Location();

    if ($id <= 0) {
        Response::error('شناسه مکان نامعتبر است.');
    }

    $location = $locModel->findById($id);
    if (!$location || $location['status'] !== 'active') {
        Response::error('مکان یافت نشد.', [], 404);
    }

    $images = $locModel->images($id);
    foreach ($images as &$img) {
        $img['url'] = UPLOAD_URL . $img['filename'];
    }
    unset($img);

    $slots = $locModel->slots($id);   // template slots (day_of_week + times)

    $location['images']        = $images;
    $location['slot_template'] = $slots;
    $location['rating_avg']    = (float) $location['rating_avg'];
    $location['amenities']     = $location['amenities'] ? (json_decode($location['amenities'], true) ?: []) : [];

    Response::json(['success' => true, 'data' => $location]);
}

// ── Available slots for a specific date ─────────────────────
function locationSlots(): never
{
    $id   = (int) ($_GET['id']   ?? 0);
    $date = trim($_GET['date'] ?? '');

    if ($id <= 0 || !$date || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        Response::error('شناسه مکان یا تاریخ نامعتبر است.');
    }

    // Validate date is not in the past
    if ($date < date('Y-m-d')) {
        Response::error('تاریخ انتخابی گذشته است.');
    }

    $locModel = new Location();
    $location = $locModel->findById($id);
    if (!$location || $location['status'] !== 'active') {
        Response::error('مکان یافت نشد.', [], 404);
    }

    // day_of_week: 0=Sun…6=Sat (matches PHP's date('w'))
    $dayOfWeek = (int) date('w', strtotime($date));

    // Get template slots for this day of week
    $db    = Database::getInstance();
    $stmt  = $db->prepare(
        'SELECT id, start_time, end_time, price_override, is_active
           FROM available_slots
          WHERE location_id = ? AND day_of_week = ? AND is_active = 1
          ORDER BY start_time'
    );
    $stmt->execute([$id, $dayOfWeek]);
    $templateSlots = $stmt->fetchAll();

    // Get already-reserved start_times for this date
    $reserved = $db->prepare(
        'SELECT start_time FROM reservations
          WHERE location_id = ? AND reservation_date = ?
            AND status IN ("pending","approved")'
    );
    $reserved->execute([$id, $date]);
    $reservedTimes = array_column($reserved->fetchAll(), 'start_time');

    // Build response
    $result = [];
    foreach ($templateSlots as $slot) {
        $startTime = $slot['start_time'];
        $price     = $slot['price_override'] ?? $location['price_per_session'];
        $result[]  = [
            'slot_id'    => (int) $slot['id'],
            'start_time' => substr($startTime, 0, 5),
            'end_time'   => substr($slot['end_time'], 0, 5),
            'price'      => (int) $price,
            'is_reserved'=> in_array($startTime, $reservedTimes, true),
        ];
    }

    if (empty($result)) {
        Response::json([
            'success' => true,
            'data'    => [],
            'message' => 'سانسی برای این روز تعریف نشده است.',
        ]);
    }

    Response::json(['success' => true, 'data' => $result]);
}
