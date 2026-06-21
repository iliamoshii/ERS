<?php
/**
 * API: Admin Location Management
 * POST /backend/api/admin_locations.php
 * Actions: create | update | delete | add_image | remove_image | set_primary_image
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

$admin    = Auth::user();
$locModel = new Location();
$action   = trim($_POST['action'] ?? '');

match ($action) {
    'create'             => createLocation($admin, $locModel),
    'update'              => updateLocation($admin, $locModel),
    'delete'              => deleteLocation($admin, $locModel),
    'add_image'           => addImage($admin, $locModel),
    'remove_image'        => removeImage($admin, $locModel),
    'set_primary_image'   => setPrimaryImage($admin, $locModel),
    default               => Response::error('اکشن نامعتبر.', [], 400),
};

// ── Validate the shared create/update field set ─────────────────
function validateFields(): array
{
    $data = [
        'category_id'       => (int)   ($_POST['category_id']       ?? 0),
        'title'             => trim((string) ($_POST['title']             ?? '')),
        'description'       => trim((string) ($_POST['description']       ?? '')),
        'address'           => trim((string) ($_POST['address']           ?? '')),
        'city'              => trim((string) ($_POST['city']              ?? 'تهران')),
        'price_per_session' => (float) ($_POST['price_per_session'] ?? 0),
        'capacity'          => (int)   ($_POST['capacity']          ?? 0),
        'surface_type'      => trim((string) ($_POST['surface_type']      ?? 'artificial')),
        'rules'             => trim((string) ($_POST['rules']             ?? '')),
        'phone'             => trim((string) ($_POST['phone']             ?? '')),
        'status'            => trim((string) ($_POST['status']            ?? 'active')),
    ];

    $v = (new Validator($data))
        ->required('title', 'عنوان')
        ->minLength('title', 3, 'عنوان')
        ->maxLength('title', 200, 'عنوان')
        ->required('address', 'آدرس');

    if ($v->fails()) {
        Response::error($v->firstError());
    }

    if ($data['category_id'] <= 0) {
        Response::error('لطفاً یک دسته‌بندی انتخاب کنید.');
    }
    if ($data['price_per_session'] <= 0) {
        Response::error('قیمت هر جلسه باید بیشتر از صفر باشد.');
    }
    if ($data['capacity'] <= 0) {
        Response::error('ظرفیت باید بیشتر از صفر باشد.');
    }
    if (!in_array($data['status'], ['active', 'inactive'], true)) {
        $data['status'] = 'active';
    }

    return $data;
}

// ── Create ────────────────────────────────────────────────────
function createLocation(array $admin, Location $locModel): never
{
    $data         = validateFields();
    $data['slug'] = $locModel->generateSlug($data['title']);

    $id = $locModel->create([
        'category_id'       => $data['category_id'],
        'created_by'        => $admin['id'],
        'title'             => $data['title'],
        'slug'              => $data['slug'],
        'description'       => $data['description'] ?: null,
        'address'           => $data['address'],
        'city'              => $data['city'],
        'price_per_session' => $data['price_per_session'],
        'capacity'          => $data['capacity'],
        'surface_type'      => $data['surface_type'],
        'rules'             => $data['rules'] ?: null,
        'phone'             => $data['phone'] ?: null,
        'status'            => $data['status'],
    ]);

    ActivityLogger::log('location_created', "ایجاد مکان «{$data['title']}»", 'location', $id, null, $admin['id']);

    Response::success('مکان جدید با موفقیت ایجاد شد.', ['id' => $id, 'slug' => $data['slug']]);
}

// ── Update ────────────────────────────────────────────────────
function updateLocation(array $admin, Location $locModel): never
{
    $id = (int) ($_POST['location_id'] ?? 0);
    if ($id <= 0 || !$locModel->findById($id)) {
        Response::error('مکان یافت نشد.', [], 404);
    }

    $data = validateFields();

    $locModel->update($id, [
        'category_id'       => $data['category_id'],
        'title'             => $data['title'],
        'description'       => $data['description'] ?: null,
        'address'           => $data['address'],
        'city'              => $data['city'],
        'price_per_session' => $data['price_per_session'],
        'capacity'          => $data['capacity'],
        'surface_type'      => $data['surface_type'],
        'rules'             => $data['rules'] ?: null,
        'phone'             => $data['phone'] ?: null,
        'status'            => $data['status'],
    ]);

    ActivityLogger::log('location_updated', "ویرایش مکان «{$data['title']}»", 'location', $id, null, $admin['id']);

    Response::success('تغییرات با موفقیت ذخیره شد.');
}

// ── Delete ────────────────────────────────────────────────────
function deleteLocation(array $admin, Location $locModel): never
{
    $id       = (int) ($_POST['location_id'] ?? 0);
    $location = $locModel->findById($id);

    if (!$location) {
        Response::error('مکان یافت نشد.', [], 404);
    }

    // Clean up physical image files before removing the DB rows
    foreach ($locModel->images($id) as $img) {
        $path = UPLOAD_DIR . $img['filename'];
        if (is_file($path)) {
            @unlink($path);
        }
    }

    $locModel->delete($id);

    ActivityLogger::log('location_deleted', "حذف مکان «{$location['title']}»", 'location', $id, null, $admin['id']);

    Response::success('مکان با موفقیت حذف شد.');
}

// ── Add image ─────────────────────────────────────────────────
function addImage(array $admin, Location $locModel): never
{
    $locationId = (int) ($_POST['location_id'] ?? 0);
    if ($locationId <= 0 || !$locModel->findById($locationId)) {
        Response::error('مکان یافت نشد.', [], 404);
    }

    if (empty($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        Response::error('لطفاً یک تصویر معتبر انتخاب کنید.');
    }

    $file = $_FILES['image'];

    if ($file['size'] > MAX_FILE_SIZE) {
        Response::error('حجم فایل نباید بیشتر از ۵ مگابایت باشد.');
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, ALLOWED_IMAGE_TYPES, true)) {
        Response::error('فرمت فایل باید JPG، PNG یا WebP باشد.');
    }

    $ext = match ($mime) {
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        default      => 'jpg',
    };

    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }

    $filename    = 'loc_' . $locationId . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
    $destination = UPLOAD_DIR . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        Response::error('خطا در ذخیره فایل. لطفاً دوباره تلاش کنید.', [], 500);
    }

    // First image uploaded for a location automatically becomes primary
    $isFirst = empty($locModel->images($locationId));
    $locModel->addImage($locationId, $filename, $isFirst);

    ActivityLogger::log('location_image_added', "افزودن تصویر برای مکان شماره {$locationId}", 'location', $locationId, null, $admin['id']);

    Response::success('تصویر با موفقیت اضافه شد.', [
        'filename'   => $filename,
        'image_url'  => UPLOAD_URL . $filename,
        'is_primary' => $isFirst,
    ]);
}

// ── Remove image ──────────────────────────────────────────────
function removeImage(array $admin, Location $locModel): never
{
    $imageId    = (int) ($_POST['image_id']    ?? 0);
    $locationId = (int) ($_POST['location_id'] ?? 0);

    if ($imageId <= 0 || $locationId <= 0) {
        Response::error('درخواست نامعتبر است.');
    }

    $filename = $locModel->removeImage($imageId, $locationId);
    if ($filename === null) {
        Response::error('تصویر یافت نشد.', [], 404);
    }

    $path = UPLOAD_DIR . $filename;
    if (is_file($path)) {
        @unlink($path);
    }

    ActivityLogger::log('location_image_removed', "حذف تصویر مکان شماره {$locationId}", 'location', $locationId, null, $admin['id']);

    Response::success('تصویر حذف شد.');
}

// ── Set primary image ─────────────────────────────────────────
function setPrimaryImage(array $admin, Location $locModel): never
{
    $imageId    = (int) ($_POST['image_id']    ?? 0);
    $locationId = (int) ($_POST['location_id'] ?? 0);

    if ($imageId <= 0 || $locationId <= 0) {
        Response::error('درخواست نامعتبر است.');
    }

    if (!$locModel->setPrimaryImage($imageId, $locationId)) {
        Response::error('خطا در تنظیم تصویر اصلی.');
    }

    Response::success('تصویر اصلی تغییر یافت.');
}