<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\Company;
use App\Models\CompanyLocation;
use App\Models\Department;
use App\Repositories\CityRepository;
use App\Services\Auth\AuthService;
use App\Services\Geography\GeographyService;
use App\Support\Str;
use Throwable;

final class LocationsController extends AdminController
{
    public function __construct(
        AuthService $auth,
        private GeographyService $geography,
        private CityRepository $cities
    ) {
        parent::__construct($auth);
    }

    public function index(Request $request): Response
    {
        $company = Company::current();

        return $this->render('admin.locations.index', [
            'locations' => $company !== null ? $this->cities->forCompany((int) $company->id()) : [],
            'departments' => Department::all('name ASC'),
        ], 'locations');
    }

    public function departmentCities(Request $request): Response
    {
        $department = Department::find((int) $request->input('department_id', 0));
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
            'name' => $c->getAttribute('name'), 'insee_code' => $c->getAttribute('insee_code'),
            'postal_code' => $c->getAttribute('postal_code'), 'latitude' => $c->getAttribute('latitude'),
            'longitude' => $c->getAttribute('longitude'), 'population' => $c->getAttribute('population'),
            'department_code' => $department->getAttribute('code'), 'distance_km' => null,
        ], array_slice($cities, 0, 150))]);
    }

    public function radiusSearch(Request $request): Response
    {
        $postalCode = trim((string) $request->input('postal_code', ''));
        $radiusKm = (int) $request->input('radius_km', 30);

        try {
            $matches = $this->geography->findByPostalCode($postalCode);
            if ($matches === []) {
                return Response::json(['ok' => false, 'message' => 'Aucune commune trouvee.'], 404);
            }
            $center = $matches[0];
            $nearby = $this->geography->searchByRadius((float) $center['latitude'], (float) $center['longitude'], $radiusKm);
        } catch (Throwable $e) {
            return Response::json(['ok' => false, 'message' => $e->getMessage()], 502);
        }

        return Response::json(['ok' => true, 'cities' => $nearby]);
    }

    public function store(Request $request): Response
    {
        $company = Company::current();
        if ($company === null) {
            return Response::redirect('/admin/locations');
        }

        $citiesInput = (array) $request->input('cities', []);
        foreach ($citiesInput as $data) {
            if (empty($data['selected']) || empty($data['insee_code'])) {
                continue;
            }
            $city = $this->cities->findOrCreateByInsee((string) $data['insee_code'], [
                'name' => (string) $data['name'],
                'slug' => Str::slug((string) $data['name']),
                'postal_code' => $data['postal_code'] ?? null,
                'latitude' => !empty($data['latitude']) ? (float) $data['latitude'] : null,
                'longitude' => !empty($data['longitude']) ? (float) $data['longitude'] : null,
                'population' => !empty($data['population']) ? (int) $data['population'] : null,
                'department_id' => Department::first(['code' => $data['department_code'] ?? ''])?->id(),
            ]);

            $existing = CompanyLocation::first(['company_id' => $company->id(), 'city_id' => $city->id()]);
            $location = $existing ?? new CompanyLocation();
            $location->fill([
                'company_id' => $company->id(),
                'city_id' => $city->id(),
                'is_primary' => $existing?->getAttribute('is_primary') ?? false,
                'distance_km' => !empty($data['distance_km']) ? (float) $data['distance_km'] : null,
                'is_active' => true,
                'seo_priority' => $existing?->getAttribute('seo_priority') ?? 5,
            ]);
            $location->save();
        }

        Session::flash('success', 'Zones mises a jour.');

        return Response::redirect('/admin/locations');
    }

    public function update(Request $request, array $params): Response
    {
        $location = CompanyLocation::find((int) $params['id']);
        if ($location === null) {
            return Response::redirect('/admin/locations');
        }

        if ($request->input('make_primary') !== null) {
            foreach (CompanyLocation::where(['company_id' => $location->getAttribute('company_id')]) as $other) {
                $other->setAttribute('is_primary', $other->id() === $location->id());
                $other->save();
            }
        } else {
            $location->fill([
                'is_active' => $request->input('is_active') !== null,
                'seo_priority' => (int) $request->input('seo_priority', $location->getAttribute('seo_priority')),
            ]);
            $location->save();
        }

        return Response::redirect('/admin/locations');
    }

    public function destroy(Request $request, array $params): Response
    {
        CompanyLocation::find((int) $params['id'])?->delete();

        return Response::redirect('/admin/locations');
    }
}
