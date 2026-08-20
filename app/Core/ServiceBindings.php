<?php

declare(strict_types=1);

namespace App\Core;

use App\Services\Auth\AuthService;
use App\Services\Geography\GeoGouvFrProvider;
use App\Services\Geography\GeographyProviderInterface;
use App\Services\Media\MediaUploadService;

/**
 * Container::make() auto-wires constructor dependencies via reflection,
 * but only for typed class parameters - services that take plain arrays
 * (config arrays) or interfaces need an explicit factory registered here
 * once, at boot, instead of in every entry point.
 */
final class ServiceBindings
{
    public static function register(Container $container): void
    {
        // Database is configured as a singleton by bootstrap.php (or by the
        // installer's database step); repositories type-hint Database in
        // their constructor and get auto-wired through this binding.
        $container->bind(Database::class, static fn () => Database::instance());

        $container->bind(AuthService::class, static fn () => new AuthService((array) config('security')));

        $container->bind(MediaUploadService::class, static fn () => new MediaUploadService((array) config('security')));

        $container->bind(GeographyProviderInterface::class, static fn () => new GeoGouvFrProvider());
    }
}
