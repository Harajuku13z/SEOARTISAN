<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\City;

final class CityRepository
{
    public function __construct(private Database $db)
    {
    }

    /** @return array<int,array<string,mixed>> */
    public function search(string $query, int $limit = 15): array
    {
        $table = $this->db->table('cities');
        $like = '%' . $query . '%';

        return $this->db->select(
            "SELECT * FROM {$table} WHERE name LIKE ? OR postal_code LIKE ? ORDER BY population DESC LIMIT ?",
            [$like, $like, $limit]
        );
    }

    public function findByInsee(string $inseeCode): ?City
    {
        return City::first(['insee_code' => $inseeCode]);
    }

    /**
     * Finds an existing city by INSEE code, or creates it. Used by
     * GeographyService when importing communes from geo.api.gouv.fr.
     *
     * @param array<string,mixed> $attributes
     */
    public function findOrCreateByInsee(string $inseeCode, array $attributes): City
    {
        $existing = $this->findByInsee($inseeCode);
        if ($existing !== null) {
            return $existing;
        }

        return City::create(array_merge($attributes, ['insee_code' => $inseeCode]));
    }

    /** @return array<int,array<string,mixed>> */
    public function forCompany(int $companyId): array
    {
        $cities = $this->db->table('cities');
        $locations = $this->db->table('company_locations');

        return $this->db->select("
            SELECT c.*, cl.is_primary, cl.distance_km, cl.is_active AS location_is_active, cl.seo_priority, cl.id AS company_location_id
            FROM {$locations} cl
            INNER JOIN {$cities} c ON c.id = cl.city_id
            WHERE cl.company_id = ?
            ORDER BY cl.is_primary DESC, cl.seo_priority DESC, c.name ASC
        ", [$companyId]);
    }
}
