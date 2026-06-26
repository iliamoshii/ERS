<?php
declare(strict_types=1);

namespace Models;

use Config\Database;
use PDO;

final class Reservation
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT r.*, l.title AS location_title, l.address AS location_address,
                    l.price_per_session, u.full_name AS user_name, u.phone AS user_phone
               FROM reservations r
               JOIN locations l ON l.id = r.location_id
               JOIN users u     ON u.id = r.user_id
              WHERE r.id = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }


    /**
     * Flat list of a user's reservations, optionally filtered by status
     * and capped at $limit (0 = no limit). Used by dashboard.php
     * (recent list + per-status counts) and reservations.php (filtered table).
     */
    public function findByUser(int $userId, string $status = '', int $limit = 0): array
    {
        $conditions = ['r.user_id = ?'];
        $params     = [$userId];

        if ($status !== '') {
            $conditions[] = 'r.status = ?';
            $params[]     = $status;
        }

        $where = implode(' AND ', $conditions);
        $sql   = "SELECT r.*, l.title AS location_title, l.address AS location_address, l.city AS location_city
                     FROM reservations r
                     JOIN locations l ON l.id = r.location_id
                    WHERE {$where}
                    ORDER BY r.reservation_date DESC, r.start_time DESC";

        if ($limit > 0) {
            $sql .= ' LIMIT ?';
            $stmt = $this->db->prepare($sql);
            foreach (array_values($params) as $i => $p) {
                $stmt->bindValue($i + 1, $p);
            }
            $stmt->bindValue(count($params) + 1, $limit, PDO::PARAM_INT);
        } else {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        }

        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function forUser(int $userId, int $page = 1, int $perPage = 10): array
    {
        $offset = ($page - 1) * $perPage;

        $count = $this->db->prepare('SELECT COUNT(*) FROM reservations WHERE user_id = ?');
        $count->execute([$userId]);
        $total = (int) $count->fetchColumn();

        $stmt = $this->db->prepare(
            'SELECT r.*, l.title AS location_title, l.address AS location_address
               FROM reservations r
               JOIN locations l ON l.id = r.location_id
              WHERE r.user_id = ?
              ORDER BY r.created_at DESC
              LIMIT ? OFFSET ?'
        );
        $stmt->execute([$userId, $perPage, $offset]);

        return ['data' => $stmt->fetchAll(), 'total' => $total];
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO reservations
                (user_id, location_id, slot_id, reservation_date, start_time, end_time,
                 people_count, notes, total_price, status)
             VALUES
                (:user_id, :location_id, :slot_id, :reservation_date, :start_time, :end_time,
                 :people_count, :notes, :total_price, "pending")'
        );
        $stmt->execute([
            ':user_id'          => $data['user_id'],
            ':location_id'      => $data['location_id'],
            ':slot_id'          => $data['slot_id'] ?? null,
            ':reservation_date' => $data['reservation_date'],
            ':start_time'       => $data['start_time'],
            ':end_time'         => $data['end_time'],
            ':people_count'     => $data['people_count'],
            ':notes'            => $data['notes'] ?? null,
            ':total_price'      => $data['total_price'],
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function cancel(int $id, int $userId, string $reason = ''): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE reservations
                SET status = "cancelled", cancelled_at = NOW(), cancel_reason = ?
              WHERE id = ? AND user_id = ? AND status IN ("pending","approved")'
        );
        return $stmt->execute([$reason, $id, $userId]) && $stmt->rowCount() > 0;
    }

    public function isDuplicate(int $locationId, string $date, string $startTime): bool
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM reservations
              WHERE location_id = ? AND reservation_date = ? AND start_time = ?
                AND status IN ("pending","approved")'
        );
        $stmt->execute([$locationId, $date, $startTime]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function paginate(int $page, int $perPage, string $search = '', string $status = ''): array
    {
        $offset = ($page - 1) * $perPage;
        $conds  = ['1=1'];
        $params = [];

        if ($search !== '') {
            $conds[]  = '(l.title LIKE ? OR u.full_name LIKE ? OR u.phone LIKE ?)';
            $like     = "%{$search}%";
            array_push($params, $like, $like, $like);
        }
        if ($status !== '') {
            $conds[]  = 'r.status = ?';
            $params[] = $status;
        }

        $where = implode(' AND ', $conds);

        $c = $this->db->prepare(
            "SELECT COUNT(*) FROM reservations r
               JOIN locations l ON l.id = r.location_id
               JOIN users u     ON u.id = r.user_id
              WHERE {$where}"
        );
        $c->execute($params);
        $total = (int) $c->fetchColumn();

        $params[] = $perPage;
        $params[] = $offset;

        $stmt = $this->db->prepare(
            "SELECT r.*, l.title AS location_title, u.full_name AS user_name, u.phone AS user_phone
               FROM reservations r
               JOIN locations l ON l.id = r.location_id
               JOIN users u     ON u.id = r.user_id
              WHERE {$where}
              ORDER BY r.created_at DESC
              LIMIT ? OFFSET ?"
        );
        $stmt->execute($params);
        return ['data' => $stmt->fetchAll(), 'total' => $total];
    }

    public function updateStatus(int $id, string $status, int $adminId): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE reservations
                SET status = ?, approved_by = ?, approved_at = NOW()
              WHERE id = ?'
        );
        return $stmt->execute([$status, $adminId, $id]);
    }

    public function count(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM reservations')->fetchColumn();
    }

    public function countByStatus(string $status): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM reservations WHERE status = ?');
        $stmt->execute([$status]);
        return (int) $stmt->fetchColumn();
    }

    public function latest(int $limit = 5): array
    {
        $stmt = $this->db->prepare(
            'SELECT r.*, l.title AS location_title, u.full_name AS user_name
               FROM reservations r
               JOIN locations l ON l.id = r.location_id
               JOIN users u     ON u.id = r.user_id
              ORDER BY r.created_at DESC LIMIT ?'
        );
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
}
