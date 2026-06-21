<?php
/**
 * Public API: Contact Form Submission
 * POST /backend/api/contact.php
 * No auth required — public endpoint.
 * Rate-limited: max 5 submissions per IP per hour (checked against contact_messages).
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/backend/bootstrap.php';

use Core\CSRF;
use Core\Response;
use Core\Validator;
use Models\ContactMessage;

header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('روش درخواست پشتیبانی نمی‌شود.', [], 405);
}

CSRF::verifyRequest();

// ── Rate limit: 5 submissions per IP per hour ───────────────
$ip  = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
$ip  = trim(explode(',', $ip)[0]);

$model = new ContactMessage();
if ($model->countByIpLastHour($ip) >= 5) {
    Response::error('تعداد ارسال‌های شما به حداکثر رسیده است. لطفاً یک ساعت دیگر تلاش کنید.');
}

// ── Validate ────────────────────────────────────────────────
$v = new Validator($_POST);
$v->required('name',    'نام و نام خانوادگی')
  ->minLength('name',   2, 'نام و نام خانوادگی')
  ->maxLength('name', 150, 'نام و نام خانوادگی')
  ->required('email',   'ایمیل')
  ->email('email',      'ایمیل')
  ->required('subject', 'موضوع')
  ->minLength('subject',  3, 'موضوع')
  ->maxLength('subject', 300, 'موضوع')
  ->required('message', 'پیام')
  ->minLength('message', 10, 'پیام')
  ->maxLength('message', 2000, 'پیام');

if ($v->fails()) {
    Response::error(implode(' | ', $v->errors()));
}

$phone = trim($_POST['phone'] ?? '');
if ($phone !== '' && !preg_match('/^09[0-9]{9}$/', $phone)) {
    Response::error('فرمت شماره موبایل صحیح نیست.');
}

// ── Save ─────────────────────────────────────────────────────
$id = $model->create([
    'name'       => trim($_POST['name']),
    'email'      => trim($_POST['email']),
    'phone'      => $phone ?: null,
    'subject'    => trim($_POST['subject']),
    'message'    => trim($_POST['message']),
    'ip_address' => $ip,
]);

if (!$id) {
    Response::error('ارسال پیام انجام نشد. لطفاً دوباره تلاش کنید.');
}

Response::success('پیام شما با موفقیت ارسال شد. در اسرع وقت با شما تماس خواهیم گرفت.');
