<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailTemplate;
use App\Models\NotificationLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function emailTemplates(Request $request)
    {
        if (!$request->expectsJson()) return view('admin.notifications.email-templates');
        return response()->json(EmailTemplate::orderBy('name')->get());
    }

    public function updateEmailTemplate(Request $request, EmailTemplate $t): JsonResponse
    {
        $request->validate(['subject' => 'required|string']);
        $data = array_filter([
            'subject'   => $request->subject,
            'body_html' => $request->body ?? $request->body_html,
            'body_text' => $request->body_text,
            'active'    => $request->has('active') ? (bool) $request->active : $t->active,
        ], fn($v) => !is_null($v));
        $t->update($data);
        return response()->json(['message' => 'Template atualizado!', 'template' => $t->fresh()]);
    }

    public function logs(Request $request): JsonResponse
    {
        $logs = NotificationLog::when($request->channel, fn($q) => $q->where('channel', $request->channel))
            ->when($request->status,  fn($q) => $q->where('status', $request->status))
            ->when($request->search,  fn($q) => $q->where('recipient', 'like', "%{$request->search}%"))
            ->orderByDesc('created_at')
            ->paginate($request->per_page ?? 20);
        return response()->json($logs);
    }
}
