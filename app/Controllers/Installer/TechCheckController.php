<?php

declare(strict_types=1);

namespace App\Controllers\Installer;

use App\Core\Request;
use App\Core\Response;
use PDO;

final class TechCheckController
{
    public function show(Request $request): Response
    {
        $checks = $this->runChecks();
        $hasCriticalFailure = (bool) array_filter($checks, static fn (array $c) => $c['critical'] && !$c['pass']);

        return Response::html(view_layout('installer.layout', 'installer.tech_check', [
            'stepKey' => 'tech-check',
            'checks' => $checks,
            'canProceed' => !$hasCriticalFailure,
        ]));
    }

    /** @return array<int,array{label:string,pass:bool,critical:bool,detail:string}> */
    private function runChecks(): array
    {
        $checks = [];

        $phpOk = version_compare(PHP_VERSION, '8.2.0', '>=');
        $checks[] = ['label' => 'Version de PHP (>= 8.2)', 'pass' => $phpOk, 'critical' => true, 'detail' => PHP_VERSION];

        foreach (['pdo', 'pdo_mysql', 'mbstring', 'json', 'fileinfo', 'curl', 'gd', 'sodium'] as $ext) {
            $loaded = extension_loaded($ext);
            $checks[] = [
                'label' => "Extension PHP : {$ext}",
                'pass' => $loaded,
                'critical' => true,
                'detail' => $loaded ? 'chargee' : 'manquante - a activer aupres de votre hebergeur',
            ];
        }

        foreach ([
            'storage/' => storage_path(),
            'storage/cache/' => storage_path('cache'),
            'storage/logs/' => storage_path('logs'),
            'storage/backups/' => storage_path('backups'),
            'public/uploads/' => public_path('uploads'),
        ] as $label => $path) {
            $writable = is_dir($path) && is_writable($path);
            $checks[] = [
                'label' => "Ecriture sur {$label}",
                'pass' => $writable,
                'critical' => true,
                'detail' => $writable ? 'accessible en ecriture' : "non accessible en ecriture (chmod 775 requis)",
            ];
        }

        $driverOk = in_array('mysql', PDO::getAvailableDrivers(), true);
        $checks[] = [
            'label' => 'Pilote MySQL/MariaDB (PDO)',
            'pass' => $driverOk,
            'critical' => true,
            'detail' => $driverOk ? 'disponible' : 'non disponible',
        ];

        $htaccessOk = is_file(public_path('.htaccess'));
        $checks[] = [
            'label' => 'Fichier .htaccess (public/)',
            'pass' => $htaccessOk,
            'critical' => false,
            'detail' => $htaccessOk ? 'present' : 'absent - requis sur Apache, non applicable sur Nginx',
        ];

        $mailOk = function_exists('mail');
        $checks[] = [
            'label' => "Capacite d'envoi d'e-mails",
            'pass' => $mailOk,
            'critical' => false,
            'detail' => $mailOk ? 'fonction mail() disponible' : 'fonction mail() indisponible',
        ];

        $imageOk = extension_loaded('gd') || extension_loaded('imagick');
        $checks[] = [
            'label' => "Bibliotheque de traitement d'images",
            'pass' => $imageOk,
            'critical' => false,
            'detail' => $imageOk ? 'GD/Imagick disponible' : 'aucune bibliotheque image detectee',
        ];

        $composerOk = is_file(base_path('vendor/autoload.php'));
        $checks[] = [
            'label' => 'Composer (optionnel)',
            'pass' => $composerOk,
            'critical' => false,
            'detail' => $composerOk ? 'vendor/ present' : "non requis pour fonctionner, utilise uniquement pour les tests",
        ];

        return $checks;
    }
}
