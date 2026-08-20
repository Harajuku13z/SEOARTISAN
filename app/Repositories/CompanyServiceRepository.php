<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

final class CompanyServiceRepository
{
    public function __construct(private Database $db)
    {
    }

    /** @return array<int,array<string,mixed>> */
    public function forCompany(int $companyId, bool $onlyActive = false): array
    {
        $table = $this->db->table('company_services');
        $media = $this->db->table('media');

        $sql = "
            SELECT cs.*, m.url AS image_url, m.alt_text AS image_alt
            FROM {$table} cs
            LEFT JOIN {$media} m ON m.id = cs.image_media_id
            WHERE cs.company_id = ?
        ";
        if ($onlyActive) {
            $sql .= ' AND cs.is_active = 1';
        }
        $sql .= ' ORDER BY cs.sort_order ASC, cs.public_name ASC';

        return $this->db->select($sql, [$companyId]);
    }

    /** @return array<string,mixed>|null */
    public function findBySlug(int $companyId, string $slug): ?array
    {
        $table = $this->db->table('company_services');

        return $this->db->selectOne(
            "SELECT * FROM {$table} WHERE company_id = ? AND slug = ? LIMIT 1",
            [$companyId, $slug]
        );
    }

    /** @return array<int,array<string,mixed>> */
    public function featured(int $companyId): array
    {
        $table = $this->db->table('company_services');

        return $this->db->select(
            "SELECT * FROM {$table} WHERE company_id = ? AND is_featured = 1 AND is_active = 1 ORDER BY sort_order ASC",
            [$companyId]
        );
    }

    /** @return array<int,array<string,mixed>> */
    public function inMenu(int $companyId): array
    {
        $table = $this->db->table('company_services');

        return $this->db->select(
            "SELECT * FROM {$table} WHERE company_id = ? AND show_in_menu = 1 AND is_active = 1 ORDER BY sort_order ASC",
            [$companyId]
        );
    }
}
