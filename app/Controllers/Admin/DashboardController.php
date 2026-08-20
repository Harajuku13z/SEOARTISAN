<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Models\AiGeneration;
use App\Models\AiProvider;
use App\Models\City;
use App\Models\CompanyService;
use App\Models\Lead;
use App\Models\Page;

final class DashboardController extends AdminController
{
    public function index(Request $request): Response
    {
        $aiProvider = AiProvider::active();

        return $this->render('admin.dashboard', [
            'pageCount' => Page::count(),
            'servicesCount' => CompanyService::count(),
            'citiesCount' => City::count(),
            'recentLeads' => Lead::where([], 'created_at DESC', 5),
            'placeholderPages' => Page::where(['content_is_placeholder' => 1]),
            'aiProvider' => $aiProvider,
            'recentGenerations' => AiGeneration::where([], 'created_at DESC', 5),
            'failedGenerationsCount' => AiGeneration::count(['status' => 'failed']),
            'installedAt' => is_file(storage_path('installed.lock')) ? trim((string) file_get_contents(storage_path('installed.lock'))) : null,
        ], 'dashboard');
    }
}
