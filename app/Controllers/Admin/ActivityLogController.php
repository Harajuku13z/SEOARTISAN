<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Models\ActivityLog;

final class ActivityLogController extends AdminController
{
    public function index(Request $request): Response
    {
        return $this->render('admin.activity_log.index', [
            'logs' => ActivityLog::recent(100),
        ], 'activity');
    }
}
