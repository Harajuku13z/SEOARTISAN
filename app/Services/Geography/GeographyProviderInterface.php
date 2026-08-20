<?php

declare(strict_types=1);

namespace App\Services\Geography;

interface GeographyProviderInterface
{
    /**
     * All communes within a department.
     *
     * @return array<int,array{name:string,insee_code:string,postal_code:?string,latitude:?float,longitude:?float,population:?int,department_code:string}>
     */
    public function communesByDepartment(string $departmentCode): array;

    /**
     * Communes within $radiusKm of the given point (used for the
     * installer's "rayon autour d'une ville" mode).
     *
     * @return array<int,array{name:string,insee_code:string,postal_code:?string,latitude:?float,longitude:?float,population:?int,department_code:string,distance_km:float}>
     */
    public function communesByRadius(float $latitude, float $longitude, int $radiusKm): array;

    /**
     * Looks up communes matching a postal code (used to resolve the
     * "ville principale" the user typed into coordinates + INSEE code).
     *
     * @return array<int,array{name:string,insee_code:string,postal_code:?string,latitude:?float,longitude:?float,population:?int,department_code:string}>
     */
    public function communesByPostalCode(string $postalCode): array;

    /**
     * The 18 French regions - used to seed the `regions` table.
     *
     * @return array<int,array{name:string,code:string}>
     */
    public function regions(): array;

    /**
     * The ~101 French departments - used to seed the `departments` table.
     *
     * @return array<int,array{name:string,code:string,region_code:string}>
     */
    public function departments(): array;
}
