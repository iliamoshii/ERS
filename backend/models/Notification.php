<?php
declare(strict_types=1);

namespace Models;

use Config\Database;
use PDO;

final class Notification
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function forUser(int $userId, int $limit = 20): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM notifications WHERE user_id = ?
              ORDER BY created_at DESC LIMIT ?'
        );
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll();
    }

    public function unreadCount(int $userId): int
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0'
        );
        $stmt->execute([$userId]);
        return (int) $stmt->fetchColumn();
    }

    public function markAllRead(int $userId): void
    {
        $this->db->prepare(
            'UPDATE notifications SET is_read = 1 WHERE user_id = ?'
        )->execute([$userId]);
    }

    public function markRead(int $id, int $userId): void
    {
        $this->db->prepare(
            'UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?'
        )->execute([$id, $userId]);
    }

    public function create(int $userId, string $type, string $title, string $message, ?string $link = null): void
    {
        $this->db->prepare(
            'INSERT INTO notifications (user_id, type, title, message, link)
             VALUES (?, ?, ?, ?, ?)'
        )->execute([$userId, $type, $title, $message, $link]);
    }

    public function delete(int $id, int $userId): void
    {
        $this->db->prepare(
            'DELETE FROM notifications WHERE id = ? AND user_id = ?'
        )->execute([$id, $userId]);
    }
}