<?php

declare(strict_types=1);

/**
 * Public + installer routes. Included by public/index.php with $router
 * already in scope.
 *
 * @var \App\Core\Router $router
 */

use App\Controllers\Installer\AdminAccountController;
use App\Controllers\Installer\AiController;
use App\Controllers\Installer\BrandingController;
use App\Controllers\Installer\BusinessController;
use App\Controllers\Installer\CompanyController;
use App\Controllers\Installer\DatabaseController;
use App\Controllers\Installer\EditorialController;
use App\Controllers\Installer\GenerateController;
use App\Controllers\Installer\LocationsController;
use App\Controllers\Installer\ServicesController;
use App\Controllers\Installer\TechCheckController;
use App\Controllers\Public\FormController;
use App\Controllers\Public\BlogController;
use App\Controllers\Public\PageController;
use App\Controllers\Public\RobotsController;
use App\Controllers\Public\TrackingController;
use App\Middleware\CsrfMiddleware;
use App\Middleware\InstallerLockMiddleware;
use App\Middleware\PageCacheMiddleware;
use App\Middleware\RateLimitMiddleware;

$router->group(['prefix' => 'install', 'middleware' => [InstallerLockMiddleware::class]], function ($router) {
    $router->get('/', [TechCheckController::class, 'show']);

    $router->group(['middleware' => [CsrfMiddleware::class]], function ($router) {
        $router->get('/database', [DatabaseController::class, 'show']);
        $router->post('/database/test', [DatabaseController::class, 'test']);
        $router->post('/database', [DatabaseController::class, 'store']);

        $router->get('/admin-account', [AdminAccountController::class, 'show']);
        $router->post('/admin-account', [AdminAccountController::class, 'store']);

        $router->get('/company', [CompanyController::class, 'show']);
        $router->post('/company', [CompanyController::class, 'store']);

        $router->get('/branding', [BrandingController::class, 'show']);
        $router->post('/branding', [BrandingController::class, 'store']);

        $router->get('/business', [BusinessController::class, 'show']);
        $router->post('/business', [BusinessController::class, 'store']);

        $router->get('/services', [ServicesController::class, 'show']);
        $router->post('/services', [ServicesController::class, 'store']);

        $router->get('/locations', [LocationsController::class, 'show']);
        $router->post('/locations/department-cities', [LocationsController::class, 'departmentCities']);
        $router->post('/locations/postal-search', [LocationsController::class, 'postalSearch']);
        $router->post('/locations/radius-search', [LocationsController::class, 'radiusSearch']);
        $router->post('/locations', [LocationsController::class, 'store']);

        $router->get('/ai', [AiController::class, 'show']);
        $router->post('/ai/test', [AiController::class, 'test']);
        $router->post('/ai', [AiController::class, 'store']);

        $router->get('/editorial', [EditorialController::class, 'show']);
        $router->post('/editorial', [EditorialController::class, 'store']);

        $router->get('/generate', [GenerateController::class, 'show']);
        $router->post('/generate/next', [GenerateController::class, 'next']);
        $router->post('/generate/finish', [GenerateController::class, 'finish']);
    });
});

$router->group(['middleware' => [PageCacheMiddleware::class]], function ($router) {
    $router->get('/', [PageController::class, 'home']);
    $router->get('/avis-clients', [PageController::class, 'reviews']);
    $router->get('/succes', [PageController::class, 'success']);
    $router->get('/merci', [PageController::class, 'success']);
    $router->get('/simulateur-de-devis', [PageController::class, 'quoteSimulator']);
    // $router->get('/simulateur-aides', [PageController::class, 'aidesSimulator']);
    $router->get('/blog', [BlogController::class, 'index']);
    $router->get('/blog/{slug}', [BlogController::class, 'show']);

    $router->get('/robots.txt', [RobotsController::class, 'index']);



    // Catch-all public page resolver - MUST stay last (see public/index.php
    // for why admin.php's routes load before this file, and why robots.txt
    // is registered above this since it would otherwise match
    // the single-segment "/{slug}" pattern too).
    $router->get('/{slug}', [PageController::class, 'bySlug']);
});

$router->group(['middleware' => [CsrfMiddleware::class, RateLimitMiddleware::class . ':forms,20,60']], function ($router) {
    $router->post('/devis/brouillon', [FormController::class, 'draft']);
    $router->post('/devis', [FormController::class, 'quote']);
    $router->post('/contact', [FormController::class, 'contact']);
});
$router->group(['middleware' => [CsrfMiddleware::class, RateLimitMiddleware::class . ':tracking,120,60']], function ($router) {
    $router->post('/track/event', [TrackingController::class, 'event']);
});
