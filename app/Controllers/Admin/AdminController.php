<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Response;
use App\Models\ActivityLog;
use App\Models\Lead;
use App\Services\Auth\AuthService;

abstract class AdminController
{
    public function __construct(protected AuthService $auth)
    {
    }

    /** @param array<string,mixed> $data */
    protected function render(string $view, array $data, string $activeNav): Response
    {
        $data['currentUser'] = $this->auth->user();
        $data['activeNav'] = $activeNav;
        $data['newLeadsCount'] = Lead::count(['status'=>'new']) + Lead::count(['status'=>'completed']) + Lead::count(['status'=>'abandoned']);

        return Response::html(view_layout('admin.layout', $view, $data));
    }

    protected function log(string $action, ?string $subjectType = null, int|string|null $subjectId = null, ?string $description = null): void
    {
        ActivityLog::create([
            'user_id' => $this->auth->user()?->id(),
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'description' => $description,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
    }
}
