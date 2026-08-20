<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

final class BusinessCategoryRepository
{
    public function __construct(private Database $db)
    {
    }

    /** @return array<int,array<string,mixed>> */
    public function allActive(): array
    {
        $table = $this->db->table('business_categories');

        return $this->db->select("SELECT * FROM {$table} WHERE is_active = 1 ORDER BY sort_order ASC, name ASC");
    }

    /** @return array<int,array<string,mixed>> */
    public function subcategoriesFor(int $categoryId): array
    {
        $table = $this->db->table('business_subcategories');

        return $this->db->select(
            "SELECT * FROM {$table} WHERE business_category_id = ? AND is_active = 1 ORDER BY sort_order ASC, name ASC",
            [$categoryId]
        );
    }

    /**
     * The suggested service catalog for a métier, used by the installer's
     * "services" step to pre-check the relevant boxes.
     *
     * @return array<int,array<string,mixed>>
     */
    public function suggestedServicesFor(int $categoryId): array
    {
        $services = $this->db->table('services');
        $pivot = $this->db->table('category_services');

        return $this->db->select("
            SELECT s.*, cs.sort_order AS suggested_sort_order
            FROM {$pivot} cs
            INNER JOIN {$services} s ON s.id = cs.service_id
            WHERE cs.business_category_id = ? AND s.is_active = 1
            ORDER BY cs.sort_order ASC, s.name ASC
        ", [$categoryId]);
    }

    /** @return array<string,mixed>|null */
    public function findBySlug(string $slug): ?array
    {
        $table = $this->db->table('business_categories');

        return $this->db->selectOne("SELECT * FROM {$table} WHERE slug = ? LIMIT 1", [$slug]);
    }
}
