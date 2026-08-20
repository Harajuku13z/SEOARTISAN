<?php

declare(strict_types=1);

namespace App\Services\Geography;

use App\Core\Cache;
use App\Models\City;
use App\Models\Department;
use App\Repositories\CityRepository;
use App\Support\Str;

/**
 * Persists results from a GeographyProviderInterface into the cities
 * table (findOrCreate by INSEE code) so they can be linked to
 * company_locations. Department/commune lists are stable, so responses
 * are cached for a day to stay polite to the free public API.
 */
final class GeographyService
{
    public function __construct(
        private GeographyProviderInterface $provider,
        private CityRepository $cities
    ) {
    }

    /** @return array<int,City> */
    public function importDepartment(string $departmentCode): array
    {
        $rows = Cache::remember(
            "geo:department:{$departmentCode}",
            86400,
            fn () => $this->provider->communesByDepartment($departmentCode)
        );

        return array_map(fn (array $row) => $this->persist($row), $rows);
    }

    /** @return array<int,array<string,mixed>> raw rows with distance_km, not persisted (caller decides which to keep) */
    public function searchByRadius(float $latitude, float $longitude, int $radiusKm): array
    {
        $key = "geo:radius:v2:{$latitude}:{$longitude}:{$radiusKm}";

        return Cache::remember($key, 3600, fn () => $this->provider->communesByRadius($latitude, $longitude, $radiusKm));
    }

    /** @return array<int,array<string,mixed>> */
    public function findByPostalCode(string $postalCode): array
    {
        return Cache::remember(
            "geo:postal:{$postalCode}",
            86400,
            fn () => $this->provider->communesByPostalCode($postalCode)
        );
    }

    /** @param array<string,mixed> $row */
    public function persist(array $row): City
    {
        $departmentId = null;
        if (($row['department_code'] ?? '') !== '') {
            $department = Department::first(['code' => $row['department_code']]);
            $departmentId = $department?->id();
        }

        return $this->cities->findOrCreateByInsee((string) $row['insee_code'], [
            'department_id' => $departmentId,
            'name' => $row['name'],
            'slug' => Str::slug($row['name']),
            'postal_code' => $row['postal_code'],
            'latitude' => $row['latitude'],
            'longitude' => $row['longitude'],
            'population' => $row['population'],
        ]);
    }
}
