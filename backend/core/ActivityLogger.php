<?php
/**
 * ActivityLogger
 * Records all important system events to the activity_logs table.
 */

declare(strict_types=1);

namespace Core;

use Config\Database;
use Core\Auth; // برای اطمینان از دسترسی به کلاس Auth
use Throwable;
use PDO;

final class ActivityLogger
{
    /**
     * Log an action.
     */
    public static function log(
        string  $action,
        string  $description,
        ?string $entityType = null,
        ?int    $entityId   = null,
        ?array  $extra      = null,
        ?int    $userId     = null
    ): void {
        try {
            $uid = $userId ?? Auth::id();

            $pdo  = Database::getInstance();
            $stmt = $pdo->prepare(
                'INSERT INTO activity_logs
                    (user_id, action, description, entity_type, entity_id, ip_address, user_agent, extra_data)
                 VALUES
                    (?, ?, ?, ?, ?, ?, ?, ?)'
            );

            $stmt->execute([
                $uid,
                $action,
                $description,
                $entityType,
                $entityId,
                self::ip(),
                $_SERVER['HTTP_USER_AGENT'] ?? null,
                $extra !== null ? json_encode($extra, JSON_UNESCAPED_UNICODE) : null,
            ]);
        } catch (Throwable) {
            // Never let logging break the application
        }
    }

    /**
     * متد جدید برای واکشی آخرین فعالیت‌ها جهت نمایش در داشبورد
     */
    public static function recent(int $limit = 8): array
    {
        try {
            $pdo = Database::getInstance();

            // اتصال جدول لاگ‌ها به جدول کاربران برای گرفتن نام کاربر
            $stmt = $pdo->prepare(
                'SELECT al.*, u.full_name as user_name 
                 FROM activity_logs al
                 LEFT JOIN users u ON al.user_id = u.id
                 ORDER BY al.id DESC 
                 LIMIT ?'
            );

            // در PDO برای استفاده از LIMIT به صورت عددی باید متغیر به این شکل Bind شود
            $stmt->bindValue(1, $limit, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable) {
            // در صورت بروز هرگونه ارور (مثلاً نبودن جدول)، یک آرایه خالی برمی‌گرداند تا سایت کرش نکند
            return [];
        }
    }

    private static function ip(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}