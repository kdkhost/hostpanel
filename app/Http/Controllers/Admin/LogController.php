<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ImpersonationLog;
use App\Models\LoginLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LogController extends Controller
{
    public function impersonation(Request $request)
    {
        if (!$request->expectsJson()) return view('admin.logs.impersonation');
        $logs = ImpersonationLog::with(['admin:id,name,email', 'client:id,name,email'])
            ->orderByDesc('started_at')
            ->paginate($request->per_page ?? 20);
        return response()->json($logs);
    }

    public function activity(Request $request)
    {
        if (!$request->expectsJson()) return view('admin.logs.activity');
        $logs = activity()->with('causer', 'subject')
            ->orderByDesc('created_at')
            ->paginate($request->per_page ?? 20);
        return response()->json($logs);
    }

    public function auth(Request $request)
    {
        if (!$request->expectsJson()) return view('admin.logs.auth');
        $logs = LoginLog::with('authenticatable')
            ->when($request->type, fn($q) => $q->where('authenticatable_type', 'like', "%{$request->type}%"))
            ->when($request->success, fn($q) => $q->where('success', $request->success === 'true'))
            ->orderByDesc('created_at')
            ->paginate($request->per_page ?? 20);
        return response()->json($logs);
    }
}
