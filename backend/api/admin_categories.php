<?php
/**
 * API: Admin Category Management
 * POST /backend/api/admin_categories.php
 * Actions: create | update | delete
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/backend/bootstrap.php';

use Core\Auth;
use Core\CSRF;
use Core\Response;
use Core\ActivityLogger;
use Middleware\AuthMiddleware;
use Models\Category;

header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('روش درخواست پشتیبانی نمی‌شود.', [], 405);
}

AuthMiddleware::requireAdmin(true);
CSRF::verifyRequest();

$admin    = Auth::user();
$catModel = new Category();
$action   = trim($_POST['action'] ?? '');

match ($action) {
    'create' => createCategory($admin, $catModel),
    'update'  => updateCategory($admin, $catModel),
    'delete'  => deleteCategory($admin, $catModel),
    default   => Response::error('اکشن نامعتبر.', [], 400),
};

function createCategory(array $admin, Category $catModel): never
{
    $name = trim($_POST['name'] ?? '');
    $icon = trim($_POST['icon'] ?? '');

    if ($name === '' || mb_strlen($name) < 2) {
        Response::error('نام دسته‌بندی باید حداقل ۲ کاراکتر باشد.');
    }

    $slug = $catModel->generateSlug($name);
    $id   = $catModel->create($name, $slug, $icon ?: null);

    ActivityLogger::log('category_created', "ایجاد دسته‌بندی «{$name}»", 'category', $id, null, $admin['id']);

    Response::success('دسته‌بندی ایجاد شد.', ['id' => $id, 'slug' => $slug]);
}

function updateCategory(array $admin, Category $catModel): never
{
    $id   = (int) ($_POST['category_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $icon = trim($_POST['icon'] ?? '');

    if ($id <= 0 || !$catModel->findById($id)) {
        Response::error('دسته‌بندی یافت نشد.', [], 404);
    }
    if ($name === '' || mb_strlen($name) < 2) {
        Response::error('نام دسته‌بندی باید حداقل ۲ کاراکتر باشد.');
    }

    $existing = $catModel->findById($id);
    $slug     = $existing['name'] === $name ? $existing['slug'] : $catModel->generateSlug($name);

    $catModel->update($id, $name, $slug, $icon ?: null);

    ActivityLogger::log('category_updated', "ویرایش دسته‌بندی «{$name}»", 'category', $id, null, $admin['id']);

    Response::success('دسته‌بندی بروزرسانی شد.');
}

function deleteCategory(array $admin, Category $catModel): never
{
    $id = (int) ($_POST['category_id'] ?? 0);
    $category = $catModel->findById($id);

    if (!$category) {
        Response::error('دسته‌بندی یافت نشد.', [], 404);
    }

    $count = $catModel->locationCount($id);
    if ($count > 0) {
        Response::error("این دسته‌بندی به {$count} مکان متصل است و قابل حذف نیست.");
    }

    $catModel->delete($id);

    ActivityLogger::log('category_deleted', "حذف دسته‌بندی «{$category['name']}»", 'category', $id, null, $admin['id']);

    Response::success('دسته‌بندی حذف شد.');
}