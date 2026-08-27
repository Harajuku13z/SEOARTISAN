<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Core\Database;
use App\Repositories\SettingsRepository;
use App\Services\Auth\AuthService;

final class VisitsController extends AdminController
{
    public function __construct(AuthService $auth, private SettingsRepository $settings)
    {
        parent::__construct($auth);
    }

    public function index(Request $request): Response
    {
        $pdo = Database::instance()->pdo();

        $stats = [
            'humans_total' => $this->count($pdo, 'is_bot = 0 AND status_code < 400'),
            'humans_today' => $this->count($pdo, 'is_bot = 0 AND status_code < 400 AND visited_at >= CURDATE()'),
            'humans_7d' => $this->count($pdo, 'is_bot = 0 AND status_code < 400 AND visited_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)'),
            'humans_30d' => $this->count($pdo, 'is_bot = 0 AND status_code < 400 AND visited_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)'),
            'bots_7d' => $this->count($pdo, "is_bot = 1 AND visited_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"),
            'blocked_7d' => $this->count($pdo, "status_code = 403 AND visited_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"),
        ];

        $byCategory = $pdo->query(
            "SELECT bot_category, COUNT(*) AS n FROM artisan_visits
             WHERE visited_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND is_bot = 1
             GROUP BY bot_category ORDER BY n DESC"
        )->fetchAll(\PDO::FETCH_ASSOC);

        $byBot = $pdo->query(
            "SELECT bot_name, bot_category, COUNT(*) AS n FROM artisan_visits
             WHERE visited_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND is_bot = 1 AND bot_name IS NOT NULL
             GROUP BY bot_name, bot_category ORDER BY n DESC LIMIT 12"
        )->fetchAll(\PDO::FETCH_ASSOC);

        $topPages = $pdo->query(
            "SELECT path, COUNT(*) AS n FROM artisan_visits
             WHERE visited_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND is_bot = 0 AND status_code < 400
             GROUP BY path ORDER BY n DESC LIMIT 10"
        )->fetchAll(\PDO::FETCH_ASSOC);

        $daily = $pdo->query(
            "SELECT DATE(visited_at) AS day,
                    SUM(CASE WHEN is_bot = 0 THEN 1 ELSE 0 END) AS humans,
                    SUM(CASE WHEN is_bot = 1 THEN 1 ELSE 0 END) AS bots
             FROM artisan_visits
             WHERE visited_at >= DATE_SUB(NOW(), INTERVAL 14 DAY)
             GROUP BY DATE(visited_at) ORDER BY day ASC"
        )->fetchAll(\PDO::FETCH_ASSOC);

        return $this->render('admin/visits/index', [
            'stats' => $stats,
            'byCategory' => $byCategory,
            'byBot' => $byBot,
            'topPages' => $topPages,
            'daily' => $daily,
            'settings' => [
                'tracking_enabled' => (bool) $this->settings->get('visits.tracking_enabled', true),
                'block_bad_bots' => (bool) $this->settings->get('visits.block_bad_bots', true),
                'allow_ai_bots' => (bool) $this->settings->get('visits.allow_ai_bots', true),
                'public_counter' => (bool) $this->settings->get('visits.public_counter', true),
                'counter_offset' => (int) $this->settings->get('visits.counter_offset', 0),
            ],
        ], 'visits');
    }

    public function update(Request $request): Response
    {
        $this->settings->set('visits.tracking_enabled', (bool) $request->input('tracking_enabled', false));
        $this->settings->set('visits.block_bad_bots', (bool) $request->input('block_bad_bots', false));
        $this->settings->set('visits.allow_ai_bots', (bool) $request->input('allow_ai_bots', false));
        $this->settings->set('visits.public_counter', (bool) $request->input('public_counter', false));
        $this->settings->set('visits.counter_offset', max(0, (int) $request->input('counter_offset', 0)));

        \App\Core\Cache::flush();

        $this->log('visits_settings_update', null, null, 'Réglages visites & robots mis à jour');

        return Response::redirect('/admin/visits?success=1');
    }

    private function count(\PDO $pdo, string $where): int
    {
        return (int) $pdo->query("SELECT COUNT(*) FROM artisan_visits WHERE " . $where)->fetchColumn();
    }
}
