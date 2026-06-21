<?php
/**
 * Public API: Comments
 * GET  /backend/api/comments.php?action=list&location_id=N   — approved comments
 * POST /backend/api/comments.php?action=submit               — submit a comment (auth)
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/backend/bootstrap.php';

use Core\Auth;
use Core\CSRF;
use Core\Response;
use Core\Validator;
use Middleware\AuthMiddleware;
use Models\Comment;
use Models\Location;

header('X-Content-Type-Options: nosniff');

$method = $_SERVER['REQUEST_METHOD'];
$action = trim($_GET['action'] ?? ($_POST['action'] ?? 'list'));

if ($method === 'GET' && $action === 'list') {
    listComments();
} elseif ($method === 'POST' && $action === 'submit') {
    submitComment();
} else {
    Response::error('درخواست نامعتبر است.', [], 400);
}

// ── GET: list approved comments for a location ───────────────
function listComments(): never
{
    $locationId = (int) ($_GET['location_id'] ?? 0);
    if ($locationId <= 0) {
        Response::error('شناسه مکان نامعتبر است.');
    }

    $model    = new Comment();
    $comments = $model->forLocation($locationId, 'approved');

    // Strip sensitive fields
    foreach ($comments as &$c) {
        unset($c['user_id']);
    }
    unset($c);

    Response::json(['success' => true, 'data' => $comments]);
}

// ── POST: submit a new comment (must be logged in) ───────────
function submitComment(): never
{
    AuthMiddleware::requireLogin(true);
    CSRF::verifyRequest();

    $user = Auth::user();

    $locationId = (int) ($_POST['location_id'] ?? 0);
    if ($locationId <= 0) {
        Response::error('شناسه مکان نامعتبر است.');
    }

    // Confirm location exists
    $locModel = new Location();
    $location = $locModel->findById($locationId);
    if (!$location || $location['status'] !== 'active') {
        Response::error('مکان یافت نشد.', [], 404);
    }

    $v = new Validator($_POST);
    $v->required('body', 'متن نظر')
      ->minLength('body', 5,   'متن نظر')
      ->maxLength('body', 1000, 'متن نظر');

    if ($v->fails()) {
        Response::error(implode(' | ', $v->errors()));
    }

    $rating = isset($_POST['rating']) && $_POST['rating'] !== ''
        ? (int) $_POST['rating']
        : null;

    if ($rating !== null && ($rating < 1 || $rating > 5)) {
        Response::error('امتیاز باید بین ۱ تا ۵ باشد.');
    }

    $model = new Comment();

    // One comment per user per location (prevent spam)
    $existing = $model->forUser($user['id']);
    foreach ($existing as $c) {
        if ((int) $c['location_id'] === $locationId) {
            Response::error('شما قبلاً برای این مکان نظر ثبت کرده‌اید.');
        }
    }

    $id = $model->create([
        'user_id'     => $user['id'],
        'location_id' => $locationId,
        'body'        => trim($_POST['body']),
        'rating'      => $rating,
    ]);

    if (!$id) {
        Response::error('ثبت نظر انجام نشد.');
    }

    Response::success('نظر شما ثبت شد و پس از تأیید نمایش داده می‌شود.');
}
