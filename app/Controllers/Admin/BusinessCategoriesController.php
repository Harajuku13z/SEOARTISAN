<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\BusinessCategory;
use App\Models\CategoryService;
use App\Models\Service;
use App\Support\Str;

final class BusinessCategoriesController extends AdminController
{
    public function index(Request $request): Response
    {
        return $this->render('admin.business_categories.index', [
            'categories' => BusinessCategory::all('sort_order ASC, name ASC'),
        ], 'categories');
    }

    public function edit(Request $request, array $params): Response
    {
        $category = BusinessCategory::find((int) $params['id']);
        if ($category === null) {
            return Response::redirect('/admin/business-categories');
        }

        $serviceIds = array_map(
            static fn ($cs) => (int) $cs->getAttribute('service_id'),
            CategoryService::where(['business_category_id' => $category->id()])
        );

        return $this->render('admin.business_categories.edit', [
            'category' => $category,
            'allServices' => Service::all('name ASC'),
            'linkedServiceIds' => $serviceIds,
        ], 'categories');
    }

    public function update(Request $request, array $params): Response
    {
        $category = BusinessCategory::find((int) $params['id']);
        if ($category === null) {
            return Response::redirect('/admin/business-categories');
        }

        $category->fill([
            'name' => trim((string) $request->input('name', $category->getAttribute('name'))),
            'slug' => trim((string) $request->input('slug', '')) ?: $category->getAttribute('slug'),
            'schema_org_type' => trim((string) $request->input('schema_org_type', '')) ?: null,
            'description' => trim((string) $request->input('description', '')) ?: null,
            'is_active' => $request->input('is_active') !== null,
        ]);
        $category->save();

        foreach (CategoryService::where(['business_category_id' => $category->id()]) as $link) {
            $link->delete();
        }
        foreach ((array) $request->input('service_ids', []) as $serviceId) {
            CategoryService::create(['business_category_id' => $category->id(), 'service_id' => (int) $serviceId]);
        }

        $this->log('business_category.update', 'BusinessCategory', $category->id());
        Session::flash('success', 'Metier mis a jour.');

        return Response::redirect('/admin/business-categories/' . $category->id());
    }

    public function store(Request $request): Response
    {
        $name = trim((string) $request->input('name', ''));
        if ($name === '') {
            return Response::redirect('/admin/business-categories');
        }

        $category = BusinessCategory::create([
            'name' => $name,
            'slug' => Str::slug($name),
            'is_active' => true,
            'sort_order' => BusinessCategory::count() + 1,
        ]);

        $this->log('business_category.create', 'BusinessCategory', $category->id());

        return Response::redirect('/admin/business-categories/' . $category->id());
    }
}
