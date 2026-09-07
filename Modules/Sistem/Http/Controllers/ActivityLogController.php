<?php

namespace Modules\Sistem\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Illuminate\Http\JsonResponse;
use Modules\Sistem\Entities\ActivityLog;

class ActivityLogController extends Controller
{
    public function index(Request $request): InertiaResponse|JsonResponse
    {
        $logs = ActivityLog::orderBy('created_at', 'desc')->paginate(25);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'data' => $logs]);
        }

        return Inertia::render('Sistem/ActivityLogs', [
            'logs' => $logs,
        ]);
    }
}
