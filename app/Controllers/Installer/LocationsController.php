<?php

declare(strict_types=1);

namespace App\Controllers\Installer;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\Company;
use App\Models\CompanyLocation;
use App\Models\Department;
use App\Repositories\CityRepository;
use App\Services\Geography\GeographyService;
use Throwable;

final class LocationsController
{
    public function __construct(
        private GeographyService $geography,
        private CityRepository $cities
    ) {
    }

    public function show(Request $request): Response
    {
        $company = Company::current();

        return Response::html(view_layout('installer.layout', 'installer.locations', [
            'stepKey' => 'locations',
            'departments' => Department::all('name ASC'),
            'existingLocations' => $company !== null ? $this->cities->forCompany((int) $company->id()) : [],
        ]));
    }

    public function departmentCities(Request $request): Response
    {
        $departmentId = (int) $request->input('department_id', 0);
        $department = Department::find($departmentId);
        if ($department === null) {
            return Response::json(['ok' => false, 'message' => 'Departement introuvable.'], 422);
        }

        try {
            $cities = $this->geography->importDepartment((string) $department->getAttribute('code'));
        } catch (Throwable $e) {
            return Response::json(['ok' => false, 'message' => $e->getMessage()], 502);
        }

        usort($cities, static fn ($a, $b) => ((int) $b->getAttribute('population')) <=> ((int) $a->getAttribute('population')));

        return Response::json(['ok' => true, 'cities' => array_map(static fn ($c) => [
            'name' => $c->getAttribute('name'),
            'insee_code' => $c->getAttribute('insee_code'),
            'postal_code' => $c->getAttribute('postal_code'),
            'latitude' => $c->getAttribute('latitude'),
            'longitude' => $c->getAttribute('longitude'),
            'population' => $c->getAttribute('population'),
            'department_code' => $department->getAttribute('code'),
            'distance_km' => null,
        ], array_slice($cities, 0, 150))]);
    }

    public function radiusSearch(Request $request): Response
    {
        $postalCode = trim((string) $request->input('postal_code', ''));
        $radiusKm = (int) $request->input('radius_km', 30);

        if ($postalCode === '') {
            return Response::json(['ok' => false, 'message' => 'Code postal requis.'], 422);
        }

        try {
            $matches = $this->geography->findByPostalCode($postalCode);
            if ($matches === []) {
                return Response::json(['ok' => false, 'message' => 'Aucune commune trouvee pour ce code postal.'], 404);
            }

            $centerInsee = trim((string) $request->input('center_insee', ''));
            $center = $matches[0];
            foreach ($matches as $match) {
                if ((string) ($match['insee_code'] ?? '') === $centerInsee) {
                    $center = $match;
                    break;
                }
            }
            $nearby = $this->geography->searchByRadius((float) $center['latitude'], (float) $center['longitude'], $radiusKm);
        } catch (Throwable $e) {
            return Response::json(['ok' => false, 'message' => $e->getMessage()], 502);
        }

        $totalCount = count($nearby);
        $departmentCodes = array_values(array_unique(array_filter(array_column($nearby, 'department_code'))));
        sort($departmentCodes);

        return Response::json([
            'ok' => true,
            'center' => $center,
            'cities' => $nearby,
            'total_count' => $totalCount,
            'department_codes' => $departmentCodes,
        ]);
    }

    public function postalSearch(Request $request): Response
    {
        $postalCode = trim((string) $request->input('postal_code', ''));
        if ($postalCode === '') {
            return Response::json(['ok' => false, 'message' => 'Code postal requis.'], 422);
        }

        try {
            $matches = $this->geography->findByPostalCode($postalCode);
        } catch (Throwable $e) {
            return Response::json(['ok' => false, 'message' => $e->getMessage()], 502);
        }

        if ($matches === []) {
            return Response::json(['ok' => false, 'message' => 'Aucune commune trouvee pour ce code postal.'], 404);
        }

        return Response::json(['ok' => true, 'cities' => $matches]);
    }

    public function store(Request $request): Response
    {
        $company = Company::current();
        if ($company === null) {
            return Response::redirect('/install/company');
        }

        $citiesInput = (array) $request->input('cities', []);
        $payload = json_decode((string) $request->input('cities_payload', ''), true);
        $selectedInsee = array_map('strval', (array) $request->input('selected_insee', []));
        $primaryInsee = (string) $request->input('primary_insee', '');
        if (is_array($payload)) {
            $citiesInput = $payload;
        }
        $added = 0;

        foreach ($citiesInput as $index => $data) {
            $inseeCode = (string) ($data['insee_code'] ?? '');
            $isSelected = is_array($payload)
                ? in_array($inseeCode, $selectedInsee, true)
                : !empty($data['selected']);
            if (!$isSelected || $inseeCode === '') {
                continue;
            }

            $city = $this->cities->findOrCreateByInsee($inseeCode, [
                'name' => (string) $data['name'],
                'slug' => \App\Support\Str::slug((string) $data['name']),
                'postal_code' => $data['postal_code'] ?? null,
                'latitude' => !empty($data['latitude']) ? (float) $data['latitude'] : null,
                'longitude' => !empty($data['longitude']) ? (float) $data['longitude'] : null,
                'population' => !empty($data['population']) ? (int) $data['population'] : null,
                'department_id' => Department::first(['code' => $data['department_code'] ?? ''])?->id(),
            ]);

            $existingLocation = \App\Models\CompanyLocation::first(['company_id' => $company->id(), 'city_id' => $city->id()]);
            $location = $existingLocation ?? new CompanyLocation();
            $isPrimary = is_array($payload)
                ? $inseeCode === $primaryInsee
                : (string) $index === (string) $request->input('primary_index');
            $location->fill([
                'company_id' => $company->id(),
                'city_id' => $city->id(),
                'is_primary' => $isPrimary,
                'distance_km' => !empty($data['distance_km']) ? (float) $data['distance_km'] : null,
                'is_active' => true,
                'seo_priority' => $isPrimary ? 10 : 5,
            ]);
            $location->save();
            $added++;
        }

        if ($added === 0) {
            Session::flash('_errors', ['form' => 'Veuillez selectionner au moins une ville.']);

            return Response::redirect('/install/locations');
        }

        return Response::redirect('/install/ai');
    }

}
