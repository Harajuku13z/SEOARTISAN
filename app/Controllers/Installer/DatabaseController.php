<?php

declare(strict_types=1);

namespace App\Controllers\Installer;

use App\Core\Database;
use App\Core\Migrator;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\Geography\GeoGouvFrProvider;
use App\Support\Crypto;
use App\Support\Env;
use App\Support\EnvWriter;
use Database\Seeders\BusinessCategoriesSeeder;
use Database\Seeders\RegionsDepartmentsSeeder;
use Throwable;

final class DatabaseController
{
    public function show(Request $request): Response
    {
        return Response::html(view_layout('installer.layout', 'installer.database', [
            'stepKey' => 'database',
            'values' => [
                'host' => Env::get('DB_HOST', '127.0.0.1'),
                'port' => Env::get('DB_PORT', '3306'),
                'database' => Env::get('DB_DATABASE', ''),
                'username' => Env::get('DB_USERNAME', ''),
                'prefix' => Env::get('DB_PREFIX', ''),
            ],
        ]));
    }

    public function test(Request $request): Response
    {
        [$ok, $error] = Database::test($this->configFromRequest($request));

        return Response::json($ok
            ? ['ok' => true, 'message' => 'Connexion reussie.']
            : ['ok' => false, 'message' => $error]);
    }

    public function store(Request $request): Response
    {
        $config = $this->configFromRequest($request);
        [$ok, $error] = Database::test($config);

        if (!$ok) {
            Session::flash('_errors', ['form' => "Connexion impossible : {$error}"]);

            return Response::redirect('/install/database');
        }

        $wantsReset = $request->input('reset_database') !== null;
        if ($wantsReset && trim((string) $request->input('reset_confirmation', '')) !== 'SUPPRIMER') {
            Session::flash('_errors', ['form' => 'Pour reinitialiser la base, saisissez exactement SUPPRIMER.']);

            return Response::redirect('/install/database');
        }

        $envValues = [
            'DB_HOST' => $config['host'],
            'DB_PORT' => (string) $config['port'],
            'DB_DATABASE' => $config['database'],
            'DB_USERNAME' => $config['username'],
            'DB_PASSWORD' => $config['password'],
            'DB_PREFIX' => $config['prefix'],
        ];

        if (config('app.key') === '' || config('app.key') === null) {
            $envValues['APP_KEY'] = Crypto::generateKey();
        }

        try {
            EnvWriter::update(base_path('.env'), $envValues);

            Database::configure($config);
            $migrator = new Migrator(Database::instance(), database_path('migrations'));
            if ($wantsReset) {
                $migrator->resetApplicationTables();
            }
            $migrator->run();

            BusinessCategoriesSeeder::run();
            RegionsDepartmentsSeeder::run(new GeoGouvFrProvider());
        } catch (Throwable $e) {
            Session::flash('_errors', ['form' => 'Installation impossible : ' . $e->getMessage()]);

            return Response::redirect('/install/database');
        }

        return Response::redirect('/install/admin-account');
    }

    /** @return array<string,mixed> */
    private function configFromRequest(Request $request): array
    {
        return [
            'host' => trim((string) $request->input('host', '127.0.0.1')),
            'port' => (int) $request->input('port', 3306),
            'database' => trim((string) $request->input('database', '')),
            'username' => trim((string) $request->input('username', '')),
            'password' => (string) $request->input('password', ''),
            'prefix' => trim((string) $request->input('prefix', '')),
            'charset' => 'utf8mb4',
        ];
    }
}
