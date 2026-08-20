<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Core\Logger;
use App\Models\Department;
use App\Models\Region;
use App\Services\Geography\GeographyProviderInterface;
use Throwable;

/**
 * Seeds the (stable, rarely-changing) French regions/departments reference
 * data from geo.api.gouv.fr. Non-fatal on failure (e.g. a host with no
 * outbound HTTPS access): logs a warning and leaves the tables empty
 * rather than hard-failing the installer - "Mode B" (ville + rayon) does
 * its own live lookups and does not depend on these being pre-populated.
 */
final class RegionsDepartmentsSeeder
{
    public static function run(GeographyProviderInterface $provider): bool
    {
        try {
            if (Region::count() === 0) {
                foreach ($provider->regions() as $region) {
                    if ($region['code'] === '') {
                        continue;
                    }
                    Region::create(['name' => $region['name'], 'code' => $region['code']]);
                }
            }

            if (Department::count() === 0) {
                $regionIdByCode = [];
                foreach (Region::all() as $region) {
                    $regionIdByCode[$region->getAttribute('code')] = $region->id();
                }

                foreach ($provider->departments() as $department) {
                    if ($department['code'] === '') {
                        continue;
                    }
                    Department::create([
                        'name' => $department['name'],
                        'code' => $department['code'],
                        'region_id' => $regionIdByCode[$department['region_code']] ?? null,
                    ]);
                }
            }

            return true;
        } catch (Throwable $e) {
            Logger::warning('RegionsDepartmentsSeeder: live geography fetch failed, skipping.', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
