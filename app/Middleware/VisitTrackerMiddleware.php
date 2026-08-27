<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Models\Visit;
use App\Repositories\SettingsRepository;
use App\Services\Tracking\BotDetector;
use Closure;

/**
 * Compteur de visites + filtre de robots.
 *
 * - Classe chaque requête publique (humain / moteur / IA / social / outil).
 * - Bloque les outils et scrapers agressifs si le réglage est actif.
 * - Les moteurs de recherche et (optionnellement) les IA passent toujours.
 * - Seuls les humains alimentent le compteur public.
 */
final class VisitTrackerMiddleware implements MiddlewareInterface
{
    public function __construct(private SettingsRepository $settings) {}

    public function handle(Request $request, Closure $next, string ...$params): Response
    {
        $path = '/' . ltrim($request->path(), '/');

        // Ressources statiques et endpoints techniques : ni comptés ni filtrés.
        if (preg_match('#^/(assets|uploads|vendor)/#', $path)
            || in_array($path, ['/robots.txt', '/llms.txt', '/track/stats', '/favicon.ico'])
            || str_starts_with($path, '/track/')) {
            return $next($request);
        }

        $enabled = (bool) $this->settings->get('visits.tracking_enabled', true);
        $detection = BotDetector::detect($request->userAgent());
if ($enabled && $detection['is_bot']) {
            $blockBadBots = (bool) $this->settings->get('visits.block_bad_bots', false);
            $allowAi = (bool) $this->settings->get('visits.allow_ai_bots', true);
            $category = $detection['category'];

            $blocked = $blockBadBots && $category === 'tool';
            if ($category === 'ai' && !$allowAi) {
                $blocked = true;
            }

            if ($enabled) {
                $this->record($request, $path, $detection, $blocked ? 403 : 200);
            }

            if ($blocked) {
                return Response::text("Accès refusé.\n", 403)->withHeader('X-Robots-Blocked', (string) $detection['name']);
            }

            return $next($request);
        }

        $response = $next($request);

        if ($enabled) {
            $this->record($request, $path, $detection, $response->status());
        }

        return $response;
    }

    private function record(Request $request, string $path, array $detection, int $status): void
    {
        try {
            Visit::create([
                'path' => mb_substr($path, 0, 500),
                'is_bot' => $detection['is_bot'],
                'bot_category' => $detection['category'],
                'bot_name' => $detection['name'],
                'user_agent' => mb_substr(trim($request->userAgent()), 0, 255) ?: null,
                'ip_hash' => hash('sha256', $request->ip() . '|visits'),
                'referrer' => mb_substr(trim((string) $request->header('referer', '')), 0, 500) ?: null,
                'status_code' => $status,
            ]);
        } catch (\Throwable) {
            // Le suivi ne doit jamais casser la page rendue.
        }
    }
}
