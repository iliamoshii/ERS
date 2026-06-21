<?php
/**
 * PasswordReset Model
 * Tokens are never stored raw — only their SHA-256 hash. The raw token
 * is generated here, returned once to the caller (to embed in a reset
 * link), and never persisted anywhere.
 */

declare(strict_types=1);

namespace Models;

use Config\Database;
use PDO;

final class PasswordReset
{
    private PDO $db;
    private const TTL_MINUTES = 60;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Creates a new reset token for the given user, invalidating any
     * previous unused tokens first. Returns the RAW token (only time
     * it's ever available in plaintext).
     */
    public function createToken(int $userId): string
    {
        $this->invalidateAllForUser($userId);

        $raw  = bin2hex(random_bytes(32));
        $hash = hash('sha256', $raw);
        $expiresAt = date('Y-m-d H:i:s', time() + self::TTL_MINUTES * 60);

        $stmt = $this->db->prepare(
            'INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (?, ?, ?)'
        );
        $stmt->execute([$userId, $hash, $expiresAt]);

        return $raw;
    }

    /** Looks up a valid (unused, unexpired) token row by its raw value. */
    public function findValidByToken(string $rawToken): ?array
    {
        $hash = hash('sha256', $rawToken);

        $stmt = $this->db->prepare(
            'SELECT * FROM password_resets
              WHERE token_hash = ? AND used = 0 AND expires_at > NOW()
              LIMIT 1'
        );
        $stmt->execute([$hash]);
        return $stmt->fetch() ?: null;
    }

    public function markUsed(int $id): bool
    {
        $stmt = $this->db->prepare('UPDATE password_resets SET used = 1 WHERE id = ?');
        return $stmt->execute([$id]);
    }

    public function invalidateAllForUser(int $userId): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE password_resets SET used = 1 WHERE user_id = ? AND used = 0'
        );
        return $stmt->execute([$userId]);
    }

    /** Housekeeping — call occasionally to purge old rows (not required for correctness). */
    public function purgeExpired(): int
    {
        $stmt = $this->db->prepare('DELETE FROM password_resets WHERE expires_at < NOW()');
        $stmt->execute();
        return $stmt->rowCount();
    }
}
