<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketReply;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tickets = Ticket::with(['client:id,name,email', 'department:id,name'])
            ->when($request->client_id, fn($q) => $q->where('client_id', $request->client_id))
            ->when($request->status,    fn($q) => $q->where('status', $request->status))
            ->orderByDesc('last_reply_at')
            ->paginate($request->per_page ?? 20);
        return response()->json($tickets);
    }

    public function show(Ticket $ticket): JsonResponse
    {
        return response()->json($ticket->load(['client', 'department', 'replies.client:id,name', 'replies.admin:id,name']));
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'client_id'           => 'required|exists:clients,id',
            'subject'             => 'required|string|max:255',
            'message'             => 'required|string',
            'ticket_department_id'=> 'required|exists:ticket_departments,id',
            'priority'            => 'required|in:low,medium,high,urgent',
        ]);

        $ticket = Ticket::create(array_merge($request->except('message'), ['status' => 'open', 'last_reply_at' => now()]));
        TicketReply::create(['ticket_id' => $ticket->id, 'client_id' => $request->client_id, 'message' => $request->message]);

        return response()->json($ticket->load('department'), 201);
    }

    public function reply(Request $request, Ticket $ticket): JsonResponse
    {
        $request->validate(['message' => 'required|string']);
        $reply = TicketReply::create(['ticket_id' => $ticket->id, 'admin_id' => null, 'client_id' => $request->client_id, 'message' => $request->message]);
        $ticket->update(['status' => $request->client_id ? 'customer_reply' : 'answered', 'last_reply_at' => now()]);
        return response()->json($reply, 201);
    }

    public function close(Ticket $ticket): JsonResponse
    {
        $ticket->update(['status' => 'closed', 'closed_at' => now()]);
        return response()->json(['message' => 'Ticket fechado.']);
    }
}
