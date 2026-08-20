<?php

declare(strict_types=1);

use App\Core\Container;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Core\ServiceBindings;
use App\Core\Session;

/** @var Container $container */
$container = require_once dirname(__DIR__) . '/bootstrap.php';

Session::start((array) config('security.session'));
Session::ageFlashData();

ServiceBindings::register($container);

$router = new Router($container);
$container->instance(Router::class, $router);

$installed = is_file(storage_path('installed.lock'));

$request = Request::capture();
$path = $request->path();

$isInstallerPath = $path === '/install' || str_starts_with($path, '/install/');
$isAssetPath = str_starts_with($path, '/assets/') || str_starts_with($path, '/uploads/');

if (!$installed && !$isInstallerPath && !$isAssetPath) {
    Response::redirect('/install')->send();

    return;
}

// admin.php first: its routes (e.g. bare "/admin") must be registered
// before web.php's catch-all "/{slug}" public page resolver, since the
// router returns the first pattern match in registration order.
require_once dirname(__DIR__) . '/routes/admin.php';
require_once dirname(__DIR__) . '/routes/web.php';

$response = $router->dispatch($request);
$response->send();
