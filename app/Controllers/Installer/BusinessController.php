<?php

declare(strict_types=1);

namespace App\Controllers\Installer;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\Company;
use App\Models\CompanySubcategory;
use App\Repositories\BusinessCategoryRepository;
use App\Repositories\SettingsRepository;

final class BusinessController
{
    public function __construct(
        private BusinessCategoryRepository $categories,
        private Database $db,
        private SettingsRepository $settings
    ) {
    }

    public function show(Request $request): Response
    {
        $company = Company::current();
        $categories = $this->categories->allActive();

        $subcategoriesByCategory = [];
        foreach ($categories as $category) {
            $subcategoriesByCategory[$category['id']] = $this->categories->subcategoriesFor((int) $category['id']);
        }

        $selectedSubcategoryIds = [];
        if ($company !== null) {
            $rows = $this->db->select(
                'SELECT business_subcategory_id FROM ' . $this->db->table('company_subcategories') . ' WHERE company_id = ?',
                [$company->id()]
            );
            $selectedSubcategoryIds = array_map(static fn (array $r) => (int) $r['business_subcategory_id'], $rows);
        }

        $selectedCategoryIds = array_map('intval', (array) $this->settings->get('business_category_ids', []));
        if ($selectedCategoryIds === [] && $company?->getAttribute('business_category_id')) {
            $selectedCategoryIds[] = (int) $company->getAttribute('business_category_id');
        }

        return Response::html(view_layout('installer.layout', 'installer.business', [
            'stepKey' => 'business',
            'categories' => $categories,
            'subcategoriesByCategory' => $subcategoriesByCategory,
            'selectedCategoryIds' => $selectedCategoryIds,
            'selectedSubcategoryIds' => $selectedSubcategoryIds,
        ]));
    }

    public function store(Request $request): Response
    {
        $company = Company::current();
        if ($company === null) {
            return Response::redirect('/install/company');
        }

        $categoryIds = array_values(array_unique(array_filter(array_map(
            'intval',
            (array) $request->input('business_category_ids', [])
        ), static fn (int $id) => $id > 0)));
        if ($categoryIds === []) {
            Session::flash('_errors', ['form' => 'Veuillez selectionner au moins un metier.']);

            return Response::redirect('/install/business');
        }

        $company->setAttribute('business_category_id', $categoryIds[0]);
        $company->save();
        $this->settings->set('business_category_ids', $categoryIds);

        $this->db->execute(
            'DELETE FROM ' . $this->db->table('company_subcategories') . ' WHERE company_id = ?',
            [$company->id()]
        );

        $subcategoryIds = (array) $request->input('subcategory_ids', []);
        foreach ($subcategoryIds as $subcategoryId) {
            CompanySubcategory::create([
                'company_id' => $company->id(),
                'business_subcategory_id' => (int) $subcategoryId,
            ]);
        }

        return Response::redirect('/install/services');
    }
}
