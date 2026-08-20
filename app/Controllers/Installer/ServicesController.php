<?php

declare(strict_types=1);

namespace App\Controllers\Installer;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\Company;
use App\Models\CompanyService;
use App\Repositories\BusinessCategoryRepository;
use App\Repositories\CompanyServiceRepository;
use App\Repositories\SettingsRepository;
use App\Support\Str;

final class ServicesController
{
    public function __construct(
        private BusinessCategoryRepository $categories,
        private CompanyServiceRepository $companyServices,
        private SettingsRepository $settings
    ) {
    }

    public function show(Request $request): Response
    {
        $company = Company::current();
        if ($company === null || $company->getAttribute('business_category_id') === null) {
            return Response::redirect('/install/business');
        }

        $categoryIds = array_map('intval', (array) $this->settings->get('business_category_ids', []));
        if ($categoryIds === []) {
            $categoryIds[] = (int) $company->getAttribute('business_category_id');
        }
        $suggestedById = [];
        foreach ($categoryIds as $categoryId) {
            foreach ($this->categories->suggestedServicesFor($categoryId) as $service) {
                $suggestedById[(int) $service['id']] = $service;
            }
        }
        $suggested = array_values($suggestedById);
        $existing = [];
        foreach ($this->companyServices->forCompany((int) $company->id()) as $row) {
            if ($row['service_id'] !== null) {
                $existing[(int) $row['service_id']] = $row;
            }
        }

        return Response::html(view_layout('installer.layout', 'installer.services', [
            'stepKey' => 'services',
            'suggested' => $suggested,
            'existing' => $existing,
        ]));
    }

    public function store(Request $request): Response
    {
        $company = Company::current();
        if ($company === null) {
            return Response::redirect('/install/company');
        }

        $servicesInput = (array) $request->input('services', []);
        $customInput = (array) $request->input('custom_services', []);

        foreach (array_merge(array_values($servicesInput), array_values($customInput)) as $fields) {
            $isSelectedSuggested = array_key_exists('selected', $fields);
            $isNamedCustom = trim((string) ($fields['name'] ?? '')) !== '';
            if (!$isSelectedSuggested && !$isNamedCustom) {
                continue;
            }
            if (($fields['content_mode'] ?? 'ai') === 'manual' && trim((string) ($fields['manual_content'] ?? '')) === '') {
                Session::flash('_errors', ['form' => 'Ajoutez le texte manuel de chaque service configuré en « Écrire moi-même ».']);
                return Response::redirect('/install/services');
            }
        }

        $sortOrder = 1;

        foreach ($servicesInput as $serviceId => $fields) {
            if (empty($fields['selected'])) {
                continue;
            }

            $publicName = trim((string) ($fields['public_name'] ?? ''));
            if ($publicName === '') {
                continue;
            }

            $existing = $this->companyServices->findBySlug((int) $company->id(), Str::slug($publicName));
            $model = $existing !== null ? CompanyService::find($existing['id']) : new CompanyService();

            $model->fill([
                'company_id' => $company->id(),
                'service_id' => (int) $serviceId,
                'public_name' => $publicName,
                'slug' => Str::slug($publicName),
                'description' => trim((string) ($fields['description'] ?? '')) ?: null,
                'content_mode' => ($fields['content_mode'] ?? 'ai') === 'manual' ? 'manual' : 'ai',
                'manual_content' => trim((string) ($fields['manual_content'] ?? '')) ?: null,
                'is_custom' => false,
                'show_in_menu' => !empty($fields['show_in_menu']),
                'is_featured' => !empty($fields['is_featured']),
                'is_emergency' => !empty($fields['is_emergency']),
                'show_starting_price' => !empty($fields['show_starting_price']),
                'starting_price' => !empty($fields['starting_price']) ? (float) $fields['starting_price'] : null,
                'sort_order' => $sortOrder++,
                'is_active' => true,
            ]);
            $model->save();
        }

        foreach ($customInput as $custom) {
            $name = trim((string) ($custom['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $existing = $this->companyServices->findBySlug((int) $company->id(), Str::slug($name));
            $model = $existing !== null ? CompanyService::find($existing['id']) : new CompanyService();

            $model->fill([
                'company_id' => $company->id(),
                'service_id' => null,
                'public_name' => $name,
                'slug' => Str::slug($name),
                'description' => trim((string) ($custom['description'] ?? '')) ?: null,
                'content_mode' => ($custom['content_mode'] ?? 'ai') === 'manual' ? 'manual' : 'ai',
                'manual_content' => trim((string) ($custom['manual_content'] ?? '')) ?: null,
                'is_custom' => true,
                'show_in_menu' => true,
                'is_featured' => false,
                'is_emergency' => false,
                'show_starting_price' => false,
                'sort_order' => $sortOrder++,
                'is_active' => true,
            ]);
            $model->save();
        }

        if (CompanyService::count(['company_id' => $company->id()]) === 0) {
            Session::flash('_errors', ['form' => 'Veuillez selectionner au moins un service.']);

            return Response::redirect('/install/services');
        }

        return Response::redirect('/install/locations');
    }
}
