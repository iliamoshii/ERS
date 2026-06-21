<?php
/**
 * User Model
 * All database operations for the users table.
 */

declare(strict_types=1);

namespace Models;

use Config\Database;
use PDO;
use PDOException;

final class User
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // ── Finders ───────────────────────────────────────────────

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT u.*, r.name AS role_name
               FROM users u
               JOIN roles r ON r.id = u.role_id
              WHERE u.id = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function findByIdentifier(string $identifier): ?array
    {
        // identifier can be username, email, or phone
        $stmt = $this->db->prepare(
            'SELECT u.*, r.name AS role_name
               FROM users u
               JOIN roles r ON r.id = u.role_id
              WHERE u.username = ?
                 OR (u.email IS NOT NULL AND u.email = ?)
                 OR u.phone = ?
              LIMIT 1'
        );
        $stmt->execute([$identifier, $identifier, $identifier]);
        return $stmt->fetch() ?: null;
    }

    public function findByUsername(string $username): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        return $stmt->fetch() ?: null;
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        return $stmt->fetch() ?: null;
    }

    public function findByPhone(string $phone): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE phone = ? LIMIT 1');
        $stmt->execute([$phone]);
        return $stmt->fetch() ?: null;
    }

    // ── CRUD ──────────────────────────────────────────────────

    /**
     * Create a new user. Returns new user ID or throws on duplicate.
     */
    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO users
                (role_id, full_name, username, email, phone, password_hash, status)
             VALUES
                (:role_id, :full_name, :username, :email, :phone, :password_hash, :status)'
        );

        $stmt->execute([
            ':role_id'       => $data['role_id']       ?? 2,
            ':full_name'     => $data['full_name'],
            ':username'      => $data['username'],
            ':email'         => $data['email']         ?? null,
            ':phone'         => $data['phone'],
            ':password_hash' => $data['password_hash'],
            ':status'        => $data['status']        ?? 'active',
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $allowed = ['full_name', 'email', 'phone', 'avatar', 'status', 'password_hash'];
        $sets    = [];
        $values  = [];

        foreach ($data as $col => $val) {
            if (in_array($col, $allowed, true)) {
                $sets[]        = "`{$col}` = ?";
                $values[]      = $val;
            }
        }

        if (empty($sets)) {
            return false;
        }

        $values[] = $id;

        $stmt = $this->db->prepare(
            'UPDATE users SET ' . implode(', ', $sets) . ' WHERE id = ?'
        );

        return $stmt->execute($values);
    }

    public function delete(int $id): bool
    {
        return $this->db->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
    }

    // ── Login attempt / brute force ───────────────────────────

    public function incrementLoginAttempts(int $id): void
    {
        $this->db->prepare(
            'UPDATE users
                SET login_attempts = login_attempts + 1,
                    locked_until   = IF(login_attempts + 1 >= ?, DATE_ADD(NOW(), INTERVAL ? MINUTE), locked_until)
              WHERE id = ?'
        )->execute([MAX_LOGIN_ATTEMPTS, LOCKOUT_MINUTES, $id]);
    }

    public function resetLoginAttempts(int $id, string $ip): void
    {
        $this->db->prepare(
            'UPDATE users
                SET login_attempts = 0,
                    locked_until   = NULL,
                    last_login_at  = NOW(),
                    last_login_ip  = ?
              WHERE id = ?'
        )->execute([$ip, $id]);
    }

    public function isLocked(array $user): bool
    {
        if ($user['locked_until'] === null) {
            return false;
        }
        return strtotime($user['locked_until']) > time();
    }

    // ── Listing for admin ─────────────────────────────────────

    public function paginate(int $page, int $perPage, string $search = '', string $status = ''): array
    {
        $offset     = ($page - 1) * $perPage;
        $conditions = ['1=1'];
        $params     = [];

        if ($search !== '') {
            $conditions[] = '(u.full_name LIKE ? OR u.username LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)';
            $like         = "%{$search}%";
            array_push($params, $like, $like, $like, $like);
        }

        if ($status !== '') {
            $conditions[] = 'u.status = ?';
            $params[]     = $status;
        }

        $where = implode(' AND ', $conditions);

        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM users u WHERE {$where}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $params[] = $perPage;
        $params[] = $offset;

        $stmt = $this->db->prepare(
            "SELECT u.*, r.name AS role_name
               FROM users u
               JOIN roles r ON r.id = u.role_id
              WHERE {$where}
              ORDER BY u.created_at DESC
              LIMIT ? OFFSET ?"
        );
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        return ['data' => $rows, 'total' => $total];
    }

    // ── Counters ──────────────────────────────────────────────

    public function count(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM users')->fetchColumn();
    }

    public function countNew(int $days = 7): int
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)'
        );
        $stmt->execute([$days]);
        return (int) $stmt->fetchColumn();
    }
}
