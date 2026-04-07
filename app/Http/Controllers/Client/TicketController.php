<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketDepartment;
use App\Models\TicketReply;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TicketController extends Controller
{
    protected function client() { return Auth::guard('client')->user(); }

    public function create()
    {
        $departments = TicketDepartment::where('active', true)->orderBy('sort_order')->get();
        $services    = $this->client()->services()->where('status', 'active')->get(['id', 'product_name', 'domain']);
        return view('client.tickets.create', compact('departments', 'services'));
    }

    public function index(Request $request)
    {
        if ($request->expectsJson()) {
            $tickets = Ticket::with(['department:id,name'])
                ->where('client_id', $this->client()->id)
                ->when($request->status, fn($q) => $q->where('status', $request->status))
                ->orderByDesc('updated_at')
                ->paginate(15);
            return response()->json($tickets);
        }
        return view('client.tickets.index');
    }

    public function show(Request $request, Ticket $ticket)
    {
        $this->authorizeTicket($ticket);
        $ticket->load(['department', 'publicReplies.client:id,name,avatar', 'publicReplies.admin:id,name,avatar', 'publicReplies.attachments', 'service:id,product_name']);

        if ($request->expectsJson()) {
            return response()->json($ticket);
        }
        return view('client.tickets.show', compact('ticket'));
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'subject'            => 'required|string|max:255',
            'message'            => 'required|string',
            'ticket_department_id' => 'required|exists:ticket_departments,id',
            'priority'           => 'required|in:low,medium,high,urgent',
            'attachments'        => 'nullable|array',
            'attachments.*'      => 'file|max:10240',
        ]);

        $department = TicketDepartment::findOrFail($request->ticket_department_id);

        $ticket = Ticket::create([
            'client_id'           => $this->client()->id,
            'ticket_department_id'=> $department->id,
            'subject'             => $request->subject,
            'priority'            => $request->priority,
            'status'              => 'open',
            'service_id'          => $request->service_id,
            'ip_address'          => $request->ip(),
            'last_reply_at'       => now(),
            'sla_due_at'          => now()->addHours($department->sla_hours),
        ]);

        $reply = TicketReply::create([
            'ticket_id'  => $ticket->id,
            'client_id'  => $this->client()->id,
            'message'    => $request->message,
            'is_note'    => false,
            'ip_address' => $request->ip(),
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

        app(NotificationService::class)->send($this->client(), 'ticket_created', [
            'name'       => $this->client()->name,
            'ticket_num' => $ticket->number,
            'subject'    => $ticket->subject,
            'action_url' => url('/cliente/tickets/' . $ticket->id),
            'message'    => "Seu ticket #{$ticket->number} foi criado com sucesso.",
        ]);

        return response()->json(['message' => 'Ticket aberto com sucesso!', 'ticket' => $ticket->load('department')], 201);
    }

    public function reply(Request $request, Ticket $ticket): JsonResponse
    {
        $this->authorizeTicket($ticket);
        $request->validate([
            'message'       => 'required|string',
            'attachments.*' => 'nullable|file|max:10240',
        ]);

        if ($ticket->isClosed()) {
            return response()->json(['message' => 'Este ticket está fechado.'], 422);
        }

        $reply = TicketReply::create([
            'ticket_id'  => $ticket->id,
            'client_id'  => $this->client()->id,
            'message'    => $request->message,
            'is_note'    => false,
            'ip_address' => $request->ip(),
        ]);

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('ticket-attachments', 'public');
                $reply->attachments()->create([
                    'filename' => $path, 'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(), 'size' => $file->getSize(),
                ]);
            }
        }

        $ticket->update(['status' => 'customer_reply', 'last_reply_at' => now()]);

        return response()->json(['message' => 'Resposta enviada!', 'reply' => $reply->load('attachments')]);
    }

    public function close(Ticket $ticket): JsonResponse
    {
        $this->authorizeTicket($ticket);
        $ticket->update(['status' => 'closed', 'closed_at' => now()]);
        return response()->json(['message' => 'Ticket fechado com sucesso!']);
    }

    public function rate(Request $request, Ticket $ticket): JsonResponse
    {
        $this->authorizeTicket($ticket);
        $request->validate(['rating' => 'required|integer|min:1|max:5', 'comment' => 'nullable|string|max:500']);
        $ticket->update(['rating' => $request->rating, 'rating_comment' => $request->comment]);
        return response()->json(['message' => 'Avaliação registrada. Obrigado!']);
    }

    protected function authorizeTicket(Ticket $ticket): void
    {
        if ($ticket->client_id !== $this->client()->id) abort(403);
    }
}
