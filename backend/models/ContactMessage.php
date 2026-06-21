<?php
/**
 * ContactMessage Model
 * Wraps the contact_messages table.
 */

declare(strict_types=1);

namespace Models;

use Config\Database;
use PDO;

final class ContactMessage
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /** Paginated list with optional search and read-filter. */
    public function paginate(
        int    $page,
        int    $perPage,
        string $search  = '',
        string $filter  = ''          // '' | 'read' | 'unread'
    ): array {
        $offset = ($page - 1) * $perPage;
        $conds  = ['1=1'];
        $params = [];

        if ($search !== '') {
            $conds[]  = '(name LIKE ? OR email LIKE ? OR subject LIKE ? OR message LIKE ?)';
            $like     = "%{$search}%";
            array_push($params, $like, $like, $like, $like);
        }

        if ($filter === 'unread') {
            $conds[]  = 'is_read = 0';
        } elseif ($filter === 'read') {
            $conds[]  = 'is_read = 1';
        }

        $where = implode(' AND ', $conds);

        $c = $this->db->prepare("SELECT COUNT(*) FROM contact_messages WHERE {$where}");
        $c->execute($params);
        $total = (int) $c->fetchColumn();

        $params[] = $perPage;
        $params[] = $offset;

        $stmt = $this->db->prepare(
            "SELECT * FROM contact_messages
              WHERE {$where}
              ORDER BY created_at DESC
              LIMIT ? OFFSET ?"
        );
        $stmt->execute($params);

        return ['data' => $stmt->fetchAll(), 'total' => $total];
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM contact_messages WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /** Insert a new message (used by public contact form). */
    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO contact_messages (name, email, phone, subject, message, ip_address)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['name'],
            $data['email'],
            $data['phone']      ?? null,
            $data['subject'],
            $data['message'],
            $data['ip_address'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function markRead(int $id): bool
    {
        return $this->db->prepare('UPDATE contact_messages SET is_read = 1 WHERE id = ?')
                        ->execute([$id]);
    }

    public function markUnread(int $id): bool
    {
        return $this->db->prepare('UPDATE contact_messages SET is_read = 0 WHERE id = ?')
                        ->execute([$id]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM contact_messages WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    public function count(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM contact_messages')->fetchColumn();
    }

    public function countUnread(): int
    {
        return (int) $this->db->query(
            'SELECT COUNT(*) FROM contact_messages WHERE is_read = 0'
        )->fetchColumn();
    }
}
