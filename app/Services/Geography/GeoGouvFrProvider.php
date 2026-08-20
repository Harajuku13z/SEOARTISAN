<?php

declare(strict_types=1);

namespace App\Services\Geography;

use App\Core\Logger;
use RuntimeException;

/**
 * Real implementation against the free, keyless French government API
 * (geo.api.gouv.fr). prompt.md explicitly allows this layer to stay
 * unfinished for the MVP, but this API is simple/stable enough to wire
 * up for real rather than stub it out.
 */
final class GeoGouvFrProvider implements GeographyProviderInterface
{
    private const BASE_URL = 'https://geo.api.gouv.fr';

    private const FIELDS = 'nom,code,codesPostaux,centre,population,departement,region';

    public function communesByDepartment(string $departmentCode): array
    {
        $rows = $this->request('/departements/' . rawurlencode($departmentCode) . '/communes', [
            'fields' => self::FIELDS,
            'format' => 'json',
        ]);

        return array_map(fn (array $row) => $this->normalize($row), $rows);
    }

    /**
     * geo.api.gouv.fr has no native radius query. Detect every department
     * crossed by the search circle by sampling its centre, middle ring and
     * perimeter, then merge their communes and apply an exact haversine
     * filter. This deliberately crosses administrative boundaries.
     */
    public function communesByRadius(float $latitude, float $longitude, int $radiusKm): array
    {
        $departmentCodes = [];
        $points = [[$latitude, $longitude]];

        // Eight middle-ring points catch narrow departments which may lie
        // between the centre and the edge. Sixteen perimeter points catch
        // every direction of a circle crossing a departmental boundary.
        foreach ([[0.55, 8], [1.0, 16]] as [$ratio, $steps]) {
            for ($step = 0; $step < $steps; $step++) {
                $points[] = $this->destinationPoint(
                    $latitude,
                    $longitude,
                    $radiusKm * $ratio,
                    360.0 * $step / $steps
                );
            }
        }

        foreach ($this->departmentsAtPoints($points) as $code) {
            if ($code !== '') {
                $departmentCodes[$code] = true;
            }
        }

        if ($departmentCodes === []) {
            return [];
        }

        $communesByInsee = [];
        foreach (array_keys($departmentCodes) as $departmentCode) {
            foreach ($this->communesByDepartment((string) $departmentCode) as $commune) {
                $communesByInsee[$commune['insee_code']] = $commune;
            }
        }

        $withDistance = array_map(function (array $row) use ($latitude, $longitude) {
            $row['distance_km'] = $this->haversineKm(
                $latitude,
                $longitude,
                $row['latitude'] ?? $latitude,
                $row['longitude'] ?? $longitude
            );

            return $row;
        }, array_values($communesByInsee));

        $withinRadius = array_values(array_filter(
            $withDistance,
            static fn (array $row) => $row['distance_km'] <= $radiusKm
        ));

        usort($withinRadius, static fn (array $a, array $b) => $a['distance_km'] <=> $b['distance_km']);

        return $withinRadius;
    }

    /**
     * Resolve all sampled points concurrently. Shared hosting otherwise
     * performs 25 sequential HTTPS calls and can exceed its request timeout.
     *
     * @param array<int,array{0:float,1:float}> $points
     * @return array<int,string>
     */
    private function departmentsAtPoints(array $points): array
    {
        $multi = curl_multi_init();
        $handles = [];
        foreach ($points as [$latitude, $longitude]) {
            $url = self::BASE_URL . '/communes?' . http_build_query([
                'lat' => $latitude,
                'lon' => $longitude,
                'fields' => 'departement',
                'format' => 'json',
            ]);
            $handle = curl_init($url);
            curl_setopt_array($handle, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 8,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_HTTPHEADER => ['Accept: application/json'],
                CURLOPT_USERAGENT => 'ArtisanIAPro/1.0',
            ]);
            curl_multi_add_handle($multi, $handle);
            $handles[] = $handle;
        }

        do {
            $status = curl_multi_exec($multi, $running);
            if ($running > 0) {
                curl_multi_select($multi, 1.0);
            }
        } while ($running > 0 && $status === CURLM_OK);

        $codes = [];
        foreach ($handles as $handle) {
            $body = curl_multi_getcontent($handle);
            $httpStatus = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
            if ($httpStatus >= 200 && $httpStatus < 300 && is_string($body)) {
                $rows = json_decode($body, true);
                $code = (string) ($rows[0]['departement']['code'] ?? '');
                if ($code !== '') {
                    $codes[] = $code;
                }
            }
            curl_multi_remove_handle($multi, $handle);
            curl_close($handle);
        }
        curl_multi_close($multi);

        return $codes;
    }

    /** @return array{0:float,1:float} */
    private function destinationPoint(float $latitude, float $longitude, float $distanceKm, float $bearingDegrees): array
    {
        $angularDistance = $distanceKm / 6371.0;
        $bearing = deg2rad($bearingDegrees);
        $lat1 = deg2rad($latitude);
        $lon1 = deg2rad($longitude);
        $lat2 = asin(sin($lat1) * cos($angularDistance) + cos($lat1) * sin($angularDistance) * cos($bearing));
        $lon2 = $lon1 + atan2(
            sin($bearing) * sin($angularDistance) * cos($lat1),
            cos($angularDistance) - sin($lat1) * sin($lat2)
        );

        return [rad2deg($lat2), rad2deg($lon2)];
    }

    public function communesByPostalCode(string $postalCode): array
    {
        $rows = $this->request('/communes', [
            'codePostal' => $postalCode,
            'fields' => self::FIELDS,
            'format' => 'json',
        ]);

        return array_map(fn (array $row) => $this->normalize($row), $rows);
    }

    public function regions(): array
    {
        $rows = $this->request('/regions', ['fields' => 'nom,code', 'format' => 'json']);

        return array_map(static fn (array $row) => [
            'name' => (string) ($row['nom'] ?? ''),
            'code' => (string) ($row['code'] ?? ''),
        ], $rows);
    }

    public function departments(): array
    {
        $rows = $this->request('/departements', ['fields' => 'nom,code,codeRegion', 'format' => 'json']);

        return array_map(static fn (array $row) => [
            'name' => (string) ($row['nom'] ?? ''),
            'code' => (string) ($row['code'] ?? ''),
            'region_code' => (string) ($row['codeRegion'] ?? ''),
        ], $rows);
    }

    /** @param array<string,mixed> $query */
    private function request(string $path, array $query): array
    {
        $url = self::BASE_URL . $path . '?' . http_build_query($query);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
            CURLOPT_USERAGENT => 'ArtisanIAPro/1.0',
        ]);

        $body = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errno !== 0) {
            Logger::warning('GeoGouvFrProvider request failed', ['url' => $url, 'error' => $error]);
            throw new RuntimeException("Impossible de contacter l'API geographique : {$error}");
        }

        if ($status < 200 || $status >= 300 || !is_string($body)) {
            throw new RuntimeException("L'API geographique a repondu avec le statut {$status}.");
        }

        $decoded = json_decode($body, true);
        if (!is_array($decoded)) {
            throw new RuntimeException("Reponse invalide de l'API geographique.");
        }

        return $decoded;
    }

    /** @param array<string,mixed> $row */
    private function normalize(array $row): array
    {
        $center = $row['centre']['coordinates'] ?? null;

        return [
            'name' => (string) ($row['nom'] ?? ''),
            'insee_code' => (string) ($row['code'] ?? ''),
            'postal_code' => isset($row['codesPostaux'][0]) ? (string) $row['codesPostaux'][0] : null,
            'longitude' => is_array($center) ? (float) ($center[0] ?? 0) : null,
            'latitude' => is_array($center) ? (float) ($center[1] ?? 0) : null,
            'population' => isset($row['population']) ? (int) $row['population'] : null,
            'department_code' => (string) ($row['departement']['code'] ?? ''),
        ];
    }

    private function haversineKm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadiusKm = 6371.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($earthRadiusKm * $c, 2);
    }
}
