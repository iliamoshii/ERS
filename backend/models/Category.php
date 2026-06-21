<?php
/**
 * Category Model
 * CRUD for sport/venue categories (football, basketball, ...).
 */

declare(strict_types=1);

namespace Models;

use Config\Database;
use PDO;

final class Category
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function all(): array
    {
        return $this->db->query(
            'SELECT c.*, (SELECT COUNT(*) FROM locations l WHERE l.category_id = c.id) AS location_count
               FROM categories c
              ORDER BY c.name'
        )->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM categories WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function findBySlug(string $slug): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM categories WHERE slug = ? LIMIT 1');
        $stmt->execute([$slug]);
        return $stmt->fetch() ?: null;
    }

    public function create(string $name, string $slug, ?string $icon = null): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO categories (name, slug, icon) VALUES (?, ?, ?)'
        );
        $stmt->execute([$name, $slug, $icon]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, string $name, string $slug, ?string $icon = null): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE categories SET name = ?, slug = ?, icon = ? WHERE id = ?'
        );
        return $stmt->execute([$name, $slug, $icon, $id]);
    }

    /** Returns false (without deleting) if locations still reference this category. */
    public function delete(int $id): bool
    {
        if ($this->locationCount($id) > 0) {
            return false;
        }
        return $this->db->prepare('DELETE FROM categories WHERE id = ?')->execute([$id]);
    }

    public function locationCount(int $id): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM locations WHERE category_id = ?');
        $stmt->execute([$id]);
        return (int) $stmt->fetchColumn();
    }

    public function slugExists(string $slug, int $excludeId = 0): bool
    {
        $stmt = $this->db->prepare('SELECT id FROM categories WHERE slug = ? AND id != ?');
        $stmt->execute([$slug, $excludeId]);
        return (bool) $stmt->fetch();
    }

    public function generateSlug(string $name): string
    {
        $slug = preg_replace('/\s+/', '-', trim($name));
        $slug = preg_replace('/[^a-zA-Z0-9\-\x{0600}-\x{06FF}]/u', '', $slug);
        $slug = strtolower(trim($slug, '-')) ?: 'category';

        $final = $slug;
        $i     = 1;
        while ($this->slugExists($final)) {
            $final = $slug . '-' . $i++;
        }
        return $final;
    }
}