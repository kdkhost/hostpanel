<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketReply;
use App\Models\TicketDepartment;
use App\Models\Admin;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        if ($request->expectsJson()) {
            $query = Ticket::with(['client:id,name,email', 'department:id,name', 'assignedAdmin:id,name'])
                ->when($request->search, fn($q) => $q->where(function ($q2) use ($request) {
                    $q2->where('number', 'like', "%{$request->search}%")
                       ->orWhere('subject', 'like', "%{$request->search}%")
                       ->orWhereHas('client', fn($q3) => $q3->where('email', 'like', "%{$request->search}%"));
                }))
                ->when($request->status,        fn($q) => $q->where('status', $request->status))
                ->when($request->priority,      fn($q) => $q->where('priority', $request->priority))
                ->when($request->department_id, fn($q) => $q->where('ticket_department_id', $request->department_id))
                ->when($request->assigned_to,   fn($q) => $q->where('assigned_to', $request->assigned_to))
                ->orderBy($request->sort_by ?? 'last_reply_at', $request->sort_dir ?? 'desc');

            return response()->json($query->paginate($request->per_page ?? 20));
        }
        return view('admin.tickets.index');
    }

    public function show(Ticket $ticket)
    {
        $ticket->load([
            'client',
            'department',
            'assignedAdmin',
            'replies.client:id,name,avatar',
            'replies.admin:id,name,avatar',
            'replies.attachments',
            'service:id,product_name,domain',
        ]);
        $departments = TicketDepartment::where('active', true)->get();
        $admins      = Admin::where('status', 'active')->get(['id', 'name']);
        $quickReplies= \App\Models\QuickReply::where('active', true)->get();
        return view('admin.tickets.show', compact('ticket', 'departments', 'admins', 'quickReplies'));
    }

    public function reply(Request $request, Ticket $ticket): JsonResponse
    {
        $request->validate([
            'message'     => 'required|string',
            'is_note'     => 'boolean',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:10240',
        ]);

        $reply = TicketReply::create([
            'ticket_id' => $ticket->id,
            'admin_id'  => auth('admin')->id(),
            'message'   => $request->message,
            'is_note'   => $request->boolean('is_note'),
            'ip_address'=> $request->ip(),
        ]);

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('ticket-attachments', 'public');
                $reply->attachments()->create([
                    'filename'      => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type'     => $file->getMimeType(),
                    'size'          => $file->getSize(),
                ]);
            }
        }

        $newStatus = $request->boolean('is_note') ? $ticket->status : 'answered';
        $ticket->update(['status' => $newStatus, 'last_reply_at' => now()]);

        if (!$request->boolean('is_note')) {
            app(NotificationService::class)->send($ticket->client, 'ticket_reply', [
                'name'       => $ticket->client->name,
                'ticket_num' => $ticket->number,
                'subject'    => $ticket->subject,
                'action_url' => url('/cliente/tickets/' . $ticket->id),
                'message'    => "Seu ticket #{$ticket->number} foi respondido.",
            ]);
        }

        return response()->json(['message' => 'Resposta enviada!', 'reply' => $reply->load(['admin:id,name,avatar', 'attachments'])]);
    }

    public function updateStatus(Request $request, Ticket $ticket): JsonResponse
    {
        $request->validate(['status' => 'required|in:open,answered,on_hold,in_progress,closed']);
        $ticket->update([
            'status'    => $request->status,
            'closed_at' => $request->status === 'closed' ? now() : $ticket->closed_at,
        ]);
        return response()->json(['message' => 'Status atualizado!', 'status' => $ticket->status]);
    }

    public function assign(Request $request, Ticket $ticket): JsonResponse
    {
        $request->validate(['admin_id' => 'nullable|exists:admins,id']);
        $ticket->update(['assigned_to' => $request->admin_id]);
        return response()->json(['message' => 'Ticket atribuído com sucesso!']);
    }

    public function transfer(Request $request, Ticket $ticket): JsonResponse
    {
        $request->validate(['department_id' => 'required|exists:ticket_departments,id']);
        $ticket->update(['ticket_department_id' => $request->department_id]);
        return response()->json(['message' => 'Ticket transferido com sucesso!']);
    }

    public function kanban(Request $request)
    {
        if (!$request->expectsJson()) return view('admin.kanban.board');
        $statuses = ['open', 'in_progress', 'answered', 'on_hold', 'closed'];
        $columns  = [];
        foreach ($statuses as $status) {
            $columns[$status] = Ticket::with(['client:id,name', 'assignedAdmin:id,name'])
                ->where('status', $status)
                ->when($request->department_id, fn($q) => $q->where('ticket_department_id', $request->department_id))
                ->orderByDesc('last_reply_at')
                ->limit(50)
                ->get();
        }
        return response()->json($columns);
    }
}
