<?php
declare(strict_types=1);

namespace Models;

use Config\Database;
use PDO;

final class Comment
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function forLocation(int $locationId, string $status = 'approved'): array
    {
        $stmt = $this->db->prepare(
            'SELECT c.*, u.full_name, u.avatar
               FROM comments c
               JOIN users u ON u.id = c.user_id
              WHERE c.location_id = ? AND c.status = ?
              ORDER BY c.created_at DESC'
        );
        $stmt->execute([$locationId, $status]);
        return $stmt->fetchAll();
    }

    public function forUser(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT c.*, l.title AS location_title
               FROM comments c
               JOIN locations l ON l.id = c.location_id
              WHERE c.user_id = ?
              ORDER BY c.created_at DESC'
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM comments WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO comments (user_id, location_id, body, rating, status)
             VALUES (?, ?, ?, ?, "pending")'
        );
        $stmt->execute([
            $data['user_id'],
            $data['location_id'],
            $data['body'],
            $data['rating'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, int $userId, string $body, ?int $rating): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE comments SET body = ?, rating = ?, status = "pending"
              WHERE id = ? AND user_id = ?'
        );
        return $stmt->execute([$body, $rating, $id, $userId]);
    }

    public function delete(int $id, ?int $userId = null): bool
    {
        if ($userId !== null) {
            $stmt = $this->db->prepare('DELETE FROM comments WHERE id = ? AND user_id = ?');
            return $stmt->execute([$id, $userId]) && $stmt->rowCount() > 0;
        }
        return $this->db->prepare('DELETE FROM comments WHERE id = ?')->execute([$id]);
    }

    public function updateStatus(int $id, string $status): bool
    {
        $stmt = $this->db->prepare('UPDATE comments SET status = ? WHERE id = ?');
        return $stmt->execute([$status, $id]);
    }

    public function paginate(int $page, int $perPage, string $search = '', string $status = ''): array
    {
        $offset = ($page - 1) * $perPage;
        $conds  = ['1=1'];
        $params = [];

        if ($search !== '') {
            $conds[]  = '(c.body LIKE ? OR u.full_name LIKE ? OR l.title LIKE ?)';
            $like     = "%{$search}%";
            array_push($params, $like, $like, $like);
        }
        if ($status !== '') {
            $conds[]  = 'c.status = ?';
            $params[] = $status;
        }

        $where = implode(' AND ', $conds);

        $c = $this->db->prepare(
            "SELECT COUNT(*) FROM comments c
               JOIN users u     ON u.id = c.user_id
               JOIN locations l ON l.id = c.location_id
              WHERE {$where}"
        );
        $c->execute($params);
        $total = (int) $c->fetchColumn();

        $params[] = $perPage;
        $params[] = $offset;

        $stmt = $this->db->prepare(
            "SELECT c.*, u.full_name AS user_name, l.title AS location_title
               FROM comments c
               JOIN users u     ON u.id = c.user_id
               JOIN locations l ON l.id = c.location_id
              WHERE {$where}
              ORDER BY c.created_at DESC
              LIMIT ? OFFSET ?"
        );
        $stmt->execute($params);
        return ['data' => $stmt->fetchAll(), 'total' => $total];
    }

    public function count(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM comments')->fetchColumn();
    }

    public function countPending(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM comments WHERE status = "pending"')->fetchColumn();
    }
}