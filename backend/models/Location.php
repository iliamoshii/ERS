<?php
declare(strict_types=1);

namespace Models;

use Config\Database;
use PDO;

final class Location
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT l.*, c.name AS category_name, c.icon AS category_icon,
                    (SELECT filename FROM location_images
                      WHERE location_id = l.id AND is_primary = 1 LIMIT 1) AS primary_image
               FROM locations l
               JOIN categories c ON c.id = l.category_id
              WHERE l.id = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function findBySlug(string $slug): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT l.*, c.name AS category_name, c.icon AS category_icon
               FROM locations l
               JOIN categories c ON c.id = l.category_id
              WHERE l.slug = ? LIMIT 1'
        );
        $stmt->execute([$slug]);
        return $stmt->fetch() ?: null;
    }

    public function allActive(string $search = '', int $categoryId = 0, string $city = ''): array
    {
        $conds  = ['l.status = "active"'];
        $params = [];

        if ($search !== '') {
            $conds[]  = '(l.title LIKE ? OR l.address LIKE ? OR l.description LIKE ?)';
            $like     = "%{$search}%";
            array_push($params, $like, $like, $like);
        }
        if ($categoryId > 0) {
            $conds[]  = 'l.category_id = ?';
            $params[] = $categoryId;
        }
        if ($city !== '') {
            $conds[]  = 'l.city = ?';
            $params[] = $city;
        }

        $where = implode(' AND ', $conds);

        $stmt = $this->db->prepare(
            "SELECT l.*, c.name AS category_name, c.icon AS category_icon,
                    (SELECT filename FROM location_images
                      WHERE location_id = l.id AND is_primary = 1 LIMIT 1) AS primary_image
               FROM locations l
               JOIN categories c ON c.id = l.category_id
              WHERE {$where}
              ORDER BY l.rating_avg DESC, l.created_at DESC"
        );
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function paginate(int $page, int $perPage, string $search = '', string $status = ''): array
    {
        $offset = ($page - 1) * $perPage;
        $conds  = ['1=1'];
        $params = [];

        if ($search !== '') {
            $conds[]  = '(l.title LIKE ? OR l.address LIKE ?)';
            $like     = "%{$search}%";
            array_push($params, $like, $like);
        }
        if ($status !== '') {
            $conds[]  = 'l.status = ?';
            $params[] = $status;
        }

        $where = implode(' AND ', $conds);

        $c = $this->db->prepare(
            "SELECT COUNT(*) FROM locations l WHERE {$where}"
        );
        $c->execute($params);
        $total = (int) $c->fetchColumn();

        $params[] = $perPage;
        $params[] = $offset;

        $stmt = $this->db->prepare(
            "SELECT l.*, c.name AS category_name,
                    (SELECT filename FROM location_images
                      WHERE location_id = l.id AND is_primary = 1 LIMIT 1) AS primary_image
               FROM locations l
               JOIN categories c ON c.id = l.category_id
              WHERE {$where}
              ORDER BY l.created_at DESC
              LIMIT ? OFFSET ?"
        );
        $stmt->execute($params);
        return ['data' => $stmt->fetchAll(), 'total' => $total];
    }

    public function images(int $locationId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM location_images WHERE location_id = ? ORDER BY sort_order ASC'
        );
        $stmt->execute([$locationId]);
        return $stmt->fetchAll();
    }

    /** Returns filename (not full URL) of the primary or first image, or null. */
    public function getPrimaryImage(int $locationId): ?string
    {
        $stmt = $this->db->prepare(
            'SELECT filename FROM location_images
              WHERE location_id = ?
              ORDER BY is_primary DESC, sort_order ASC
              LIMIT 1'
        );
        $stmt->execute([$locationId]);
        $row = $stmt->fetch();
        return $row ? $row['filename'] : null;
    }

    public function slots(int $locationId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM available_slots
              WHERE location_id = ? AND is_active = 1
              ORDER BY day_of_week, start_time'
        );
        $stmt->execute([$locationId]);
        return $stmt->fetchAll();
    }

    /** All slots regardless of active state — for the admin management UI. */
    public function allSlots(int $locationId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM available_slots
              WHERE location_id = ?
              ORDER BY day_of_week, start_time'
        );
        $stmt->execute([$locationId]);
        return $stmt->fetchAll();
    }

    public function findSlotById(int $slotId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM available_slots WHERE id = ? LIMIT 1');
        $stmt->execute([$slotId]);
        return $stmt->fetch() ?: null;
    }

    public function createSlot(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO available_slots (location_id, day_of_week, start_time, end_time, price_override, is_active)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['location_id'],
            $data['day_of_week'],
            $data['start_time'],
            $data['end_time'],
            $data['price_override'] ?? null,
            $data['is_active']      ?? 1,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function updateSlot(int $slotId, array $data): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE available_slots
                SET day_of_week = ?, start_time = ?, end_time = ?, price_override = ?, is_active = ?
              WHERE id = ?'
        );
        return $stmt->execute([
            $data['day_of_week'],
            $data['start_time'],
            $data['end_time'],
            $data['price_override'] ?? null,
            $data['is_active']      ?? 1,
            $slotId,
        ]);
    }

    public function deleteSlot(int $slotId): bool
    {
        $stmt = $this->db->prepare('DELETE FROM available_slots WHERE id = ?');
        $stmt->execute([$slotId]);
        return $stmt->rowCount() > 0;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO locations
                (category_id, created_by, title, slug, description, address, city,
                 price_per_session, capacity, surface_type, amenities, rules, phone, status)
             VALUES
                (:category_id, :created_by, :title, :slug, :description, :address, :city,
                 :price_per_session, :capacity, :surface_type, :amenities, :rules, :phone, :status)'
        );
        $stmt->execute([
            ':category_id'       => $data['category_id'],
            ':created_by'        => $data['created_by'],
            ':title'             => $data['title'],
            ':slug'              => $data['slug'],
            ':description'       => $data['description'] ?? null,
            ':address'           => $data['address'],
            ':city'              => $data['city'] ?? 'تهران',
            ':price_per_session' => $data['price_per_session'],
            ':capacity'          => $data['capacity'],
            ':surface_type'      => $data['surface_type'] ?? 'artificial',
            ':amenities'         => isset($data['amenities']) ? json_encode($data['amenities'], JSON_UNESCAPED_UNICODE) : null,
            ':rules'             => $data['rules'] ?? null,
            ':phone'             => $data['phone'] ?? null,
            ':status'            => $data['status'] ?? 'active',
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $allowed = ['title', 'description', 'address', 'city', 'price_per_session',
            'capacity', 'surface_type', 'amenities', 'rules', 'phone',
            'status', 'category_id'];
        $sets   = [];
        $values = [];

        foreach ($data as $col => $val) {
            if (in_array($col, $allowed, true)) {
                $sets[]   = "`{$col}` = ?";
                $values[] = $val;
            }
        }

        if (empty($sets)) return false;

        $values[] = $id;
        $stmt = $this->db->prepare(
            'UPDATE locations SET ' . implode(', ', $sets) . ' WHERE id = ?'
        );
        return $stmt->execute($values);
    }

    public function delete(int $id): bool
    {
        return $this->db->prepare('DELETE FROM locations WHERE id = ?')->execute([$id]);
    }

    public function addImage(int $locationId, string $filename, bool $isPrimary = false): void
    {
        $this->db->prepare(
            'INSERT INTO location_images (location_id, filename, is_primary) VALUES (?, ?, ?)'
        )->execute([$locationId, $filename, $isPrimary ? 1 : 0]);
    }

    /**
     * Delete an image row (scoped to the given location for safety).
     * Returns the filename on success so the caller can unlink the
     * physical file, or null if no matching row was found.
     */
    public function removeImage(int $imageId, int $locationId): ?string
    {
        $stmt = $this->db->prepare(
            'SELECT filename FROM location_images WHERE id = ? AND location_id = ?'
        );
        $stmt->execute([$imageId, $locationId]);
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        $this->db->prepare('DELETE FROM location_images WHERE id = ?')->execute([$imageId]);
        return $row['filename'];
    }

    /** Mark one image as primary, unsetting any previous primary for the location. */
    public function setPrimaryImage(int $imageId, int $locationId): bool
    {
        $this->db->beginTransaction();
        try {
            $this->db->prepare('UPDATE location_images SET is_primary = 0 WHERE location_id = ?')
                ->execute([$locationId]);

            $stmt = $this->db->prepare(
                'UPDATE location_images SET is_primary = 1 WHERE id = ? AND location_id = ?'
            );
            $stmt->execute([$imageId, $locationId]);
            $ok = $stmt->rowCount() > 0;

            $this->db->commit();
            return $ok;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function count(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM locations')->fetchColumn();
    }

    public function categories(): array
    {
        return $this->db->query('SELECT * FROM categories ORDER BY name')->fetchAll();
    }

    public function generateSlug(string $title): string
    {
        // Simple slug from Persian transliteration fallback + timestamp
        $slug = preg_replace('/\s+/', '-', trim($title));
        $slug = preg_replace('/[^a-zA-Z0-9\-\x{0600}-\x{06FF}]/u', '', $slug);
        $slug = strtolower($slug);
        $slug = trim($slug, '-');

        // Ensure uniqueness
        $base  = $slug ?: 'location';
        $final = $base;
        $i     = 1;
        while ($this->findBySlug($final)) {
            $final = $base . '-' . $i++;
        }
        return $final;
    }

    public function updateRating(int $locationId): void
    {
        $this->db->prepare(
            'UPDATE locations l
                SET rating_avg   = (SELECT COALESCE(AVG(rating), 0)
                                      FROM comments
                                     WHERE location_id = ? AND status = "approved" AND rating IS NOT NULL),
                    rating_count = (SELECT COUNT(*)
                                      FROM comments
                                     WHERE location_id = ? AND status = "approved")
              WHERE l.id = ?'
        )->execute([$locationId, $locationId, $locationId]);
    }
}