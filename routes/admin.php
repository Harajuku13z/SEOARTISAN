<?php

declare(strict_types=1);

/**
 * Admin routes, grouped under /admin. Included by public/index.php with
 * $router already in scope (loaded BEFORE routes/web.php - see index.php).
 *
 * @var \App\Core\Router $router
 */

use App\Controllers\Admin\ActivityLogController;
use App\Controllers\Admin\AiController;
use App\Controllers\Admin\AuthController;
use App\Controllers\Admin\BusinessCategoriesController;
use App\Controllers\Admin\BlogController;
use App\Controllers\Admin\CompanyController;
use App\Controllers\Admin\DashboardController;
use App\Controllers\Admin\LeadsController;
use App\Controllers\Admin\LocationsController;
use App\Controllers\Admin\LocalPagesController;
use App\Controllers\Admin\MediaController;
use App\Controllers\Admin\MenuController;
use App\Controllers\Admin\PagesController;
use App\Controllers\Admin\ProjectsController;
use App\Controllers\Admin\ServicesController;
use App\Controllers\Admin\SettingsController;
use App\Controllers\Admin\SitemapController;
use App\Controllers\Admin\TestimonialsController;
use App\Controllers\Admin\UsersController;
use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Middleware\RoleGuardMiddleware;

$router->get('/admin/login', [AuthController::class, 'showLogin']);

// Import ponctuel des realisations - protege par jeton secret (pas par session admin).
// A supprimer avec la methode ProjectsController::importRealisations() une fois utilise.
$router->get('/admin/projects/import-realisations', [ProjectsController::class, 'importRealisations']);
$router->get('/admin/projects/import-debug', [ProjectsController::class, 'importDebug']);

$router->group(['prefix' => 'admin', 'middleware' => [CsrfMiddleware::class]], function ($router) {
    $router->post('/login', [AuthController::class, 'login'])->name('admin.login.submit');
});

$router->group(['prefix' => 'admin', 'middleware' => [AuthMiddleware::class, CsrfMiddleware::class]], function ($router) {
    $router->post('/logout', [AuthController::class, 'logout']);
    $router->get('', [DashboardController::class, 'index']);

    $router->get('/company', [CompanyController::class, 'show']);
    $router->post('/company', [CompanyController::class, 'update']);
    $router->post('/company/test-email', [CompanyController::class, 'testEmail']);
    $router->get('/blog', [BlogController::class, 'show']);
    $router->post('/blog', [BlogController::class, 'save']);
    $router->post('/blog/test', [BlogController::class, 'test']);

    $router->get('/business-categories', [BusinessCategoriesController::class, 'index']);
    $router->post('/business-categories', [BusinessCategoriesController::class, 'store']);
    $router->get('/business-categories/{id}', [BusinessCategoriesController::class, 'edit']);
    $router->post('/business-categories/{id}', [BusinessCategoriesController::class, 'update']);

    $router->get('/services', [ServicesController::class, 'index']);
    $router->get('/services/create', [ServicesController::class, 'create']);
    $router->post('/services', [ServicesController::class, 'store']);
    $router->post('/services/group-image', [ServicesController::class, 'updateGroupImage']);
    $router->get('/services/{id}', [ServicesController::class, 'edit']);
    $router->post('/services/{id}', [ServicesController::class, 'update']);
    $router->post('/services/{id}/regenerate', [ServicesController::class, 'regenerate']);
    $router->post('/services/{id}/faq', [ServicesController::class, 'addFaq']);
    $router->post('/services/faq/{faqId}/delete', [ServicesController::class, 'deleteFaq']);

    $router->get('/pages', [PagesController::class, 'index']);
    $router->get('/pages/{id}', [PagesController::class, 'edit']);
    $router->post('/pages/{id}', [PagesController::class, 'update']);
    $router->post('/pages/{id}/blocks', [PagesController::class, 'addBlock']);
    $router->post('/pages/block/{blockId}', [PagesController::class, 'updateBlock']);
    $router->post('/pages/block/{blockId}/delete', [PagesController::class, 'deleteBlock']);
    $router->post('/pages/block/{blockId}/move', [PagesController::class, 'moveBlock']);

    $router->get('/locations', [LocationsController::class, 'index']);
    $router->post('/locations/department-cities', [LocationsController::class, 'departmentCities']);
    $router->post('/locations/radius-search', [LocationsController::class, 'radiusSearch']);
    $router->post('/locations', [LocationsController::class, 'store']);
    $router->post('/locations/{id}', [LocationsController::class, 'update']);
    $router->post('/locations/{id}/delete', [LocationsController::class, 'destroy']);

    $router->get('/local-pages', [LocalPagesController::class, 'index']);
    $router->post('/local-pages', [LocalPagesController::class, 'create']);
    $router->get('/local-pages/export.json', [LocalPagesController::class, 'exportJson']);
    $router->post('/local-pages/import-json', [LocalPagesController::class, 'importJson']);
    $router->post('/local-pages/{id}', [LocalPagesController::class, 'update']);
    $router->post('/local-pages/{id}/generate', [LocalPagesController::class, 'generate']);

    $router->get('/ai', [AiController::class, 'show']);
    $router->post('/ai/test', [AiController::class, 'test']);
    $router->post('/ai/retry-failed', [AiController::class, 'retryFailed']);
    $router->post('/ai/retry/{id}', [AiController::class, 'retryOne']);
    $router->post('/ai', [AiController::class, 'update']);

    $router->get('/leads', [LeadsController::class, 'index']);
    $router->get('/leads/export.csv', [LeadsController::class, 'exportCsv']);
    $router->get('/leads/{id}', [LeadsController::class, 'show']);
    $router->post('/leads/{id}/status', [LeadsController::class, 'updateStatus']);
    $router->post('/leads/{id}/notes', [LeadsController::class, 'addNote']);

    $router->get('/media', [MediaController::class, 'index']);
    $router->post('/media', [MediaController::class, 'store']);
    $router->post('/media/{id}/delete', [MediaController::class, 'destroy']);

    $router->get('/menu', [MenuController::class, 'index']);
    $router->post('/menu', [MenuController::class, 'store']);
    $router->post('/menu/domain', [MenuController::class, 'storeDomain']);
    $router->post('/menu/auto-organize', [MenuController::class, 'autoOrganize']);
    $router->post('/menu/selection', [MenuController::class, 'storeSelection']);
    $router->post('/menu/reorder', [MenuController::class, 'reorder']);
    $router->post('/menu/update', [MenuController::class, 'update']);
    $router->post('/menu/{id}/create-page', [MenuController::class, 'createPage']);
    $router->post('/menu/{id}/delete', [MenuController::class, 'destroy']);

    $router->get('/projects', [ProjectsController::class, 'index']);
    $router->get('/projects/create', [ProjectsController::class, 'create']);
    $router->post('/projects', [ProjectsController::class, 'store']);
    $router->post('/projects/videos', [ProjectsController::class, 'storeVideo']);
    $router->post('/projects/videos/{id}/delete', [ProjectsController::class, 'deleteVideo']);
    $router->get('/projects/{id}', [ProjectsController::class, 'edit']);
    $router->post('/projects/{id}', [ProjectsController::class, 'update']);
    $router->post('/projects/{id}/delete', [ProjectsController::class, 'destroy']);

    $router->get('/testimonials', [TestimonialsController::class, 'index']);
    $router->post('/testimonials', [TestimonialsController::class, 'store']);
    $router->post('/testimonials/serpapi', [TestimonialsController::class, 'saveSerpApi']);
    $router->post('/testimonials/serpapi/test', [TestimonialsController::class, 'testSerpApi']);
    $router->post('/testimonials/google-sync', [TestimonialsController::class, 'syncGoogle']);
    $router->post('/testimonials/{id}/delete', [TestimonialsController::class, 'destroy']);

    $router->get('/settings', [SettingsController::class, 'show']);
    $router->post('/settings', [SettingsController::class, 'update']);
    $router->post('/settings/redirects', [SettingsController::class, 'storeRedirect']);
    $router->post('/settings/redirects/{id}/delete', [SettingsController::class, 'destroyRedirect']);
    $router->post('/settings/purge-cache', [SettingsController::class, 'purgeCache']);

    $router->get('/sitemap', [SitemapController::class, 'show']);
    $router->post('/sitemap/generate', [SitemapController::class, 'generate']);

    $router->get('/activity-log', [ActivityLogController::class, 'index']);

    $router->group(['middleware' => [RoleGuardMiddleware::class . ':super_admin']], function ($router) {
        $router->get('/users', [UsersController::class, 'index']);
        $router->post('/users', [UsersController::class, 'store']);
        $router->post('/users/{id}/toggle', [UsersController::class, 'toggleActive']);
    });
});
