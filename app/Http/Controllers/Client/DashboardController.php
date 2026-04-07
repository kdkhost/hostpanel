<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Service;
use App\Models\Ticket;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $client = Auth::guard('client')->user();

        $activeServices   = Service::where('client_id', $client->id)->where('status', 'active')->count();
        $pendingInvoices  = Invoice::where('client_id', $client->id)->whereIn('status', ['pending', 'overdue'])->count();
        $openTickets      = Ticket::where('client_id', $client->id)->whereIn('status', ['open', 'customer_reply'])->count();
        $overdueInvoices  = Invoice::where('client_id', $client->id)->where('status', 'overdue')->get();

        $recentInvoices   = Invoice::where('client_id', $client->id)
            ->orderByDesc('created_at')->limit(5)->get();

        $recentServices   = Service::where('client_id', $client->id)
            ->with('product:id,name')
            ->orderByDesc('created_at')->limit(5)->get();

        $recentTickets    = Ticket::where('client_id', $client->id)
            ->with('department:id,name')
            ->orderByDesc('updated_at')->limit(5)->get();

        $unreadNotifications = $client->notifications()->where('read', false)->count();

        return view('client.dashboard', compact(
            'client', 'activeServices', 'pendingInvoices', 'openTickets',
            'overdueInvoices', 'recentInvoices', 'recentServices',
            'recentTickets', 'unreadNotifications'
        ));
    }
}
