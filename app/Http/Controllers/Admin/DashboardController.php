<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Service;
use App\Models\Server;
use App\Models\Ticket;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = $this->getStats();
        return view('admin.dashboard', compact('stats'));
    }

    public function stats(): JsonResponse
    {
        return response()->json($this->getStats());
    }

    protected function getStats(): array
    {
        return Cache::remember('admin.dashboard.stats', 300, function () {
            $now   = now();
            $month = $now->month;
            $year  = $now->year;

            $mrr = Invoice::where('status', 'paid')
                ->whereMonth('date_paid', $month)
                ->whereYear('date_paid', $year)
                ->sum('total');

            $newClientsThisMonth = Client::whereMonth('created_at', $month)
                ->whereYear('created_at', $year)
                ->count();

            $activeServices = Service::where('status', 'active')->count();

            $openTickets = Ticket::whereIn('status', ['open', 'customer_reply', 'in_progress'])->count();

            $overdueInvoices = Invoice::where('status', 'overdue')->count();
            $pendingInvoices = Invoice::where('status', 'pending')->count();

            $overdueAmount = Invoice::where('status', 'overdue')->sum('amount_due');

            $serversOnline  = Server::where('status', 'online')->count();
            $serversOffline = Server::where('status', 'offline')->count();

            $revenueChart = Invoice::select(
                DB::raw('DATE_FORMAT(date_paid, "%Y-%m") as month'),
                DB::raw('SUM(total) as total')
            )
                ->where('status', 'paid')
                ->where('date_paid', '>=', now()->subMonths(11)->startOfMonth())
                ->groupBy('month')
                ->orderBy('month')
                ->pluck('total', 'month');

            $recentTransactions = Transaction::with('client')
                ->where('status', 'completed')
                ->orderByDesc('created_at')
                ->limit(10)
                ->get();

            $suspendedToday = Service::where('status', 'suspended')
                ->whereDate('updated_at', today())
                ->count();

            return [
                'mrr'                  => $mrr,
                'new_clients'          => $newClientsThisMonth,
                'active_services'      => $activeServices,
                'open_tickets'         => $openTickets,
                'overdue_invoices'     => $overdueInvoices,
                'pending_invoices'     => $pendingInvoices,
                'overdue_amount'       => $overdueAmount,
                'servers_online'       => $serversOnline,
                'servers_offline'      => $serversOffline,
                'revenue_chart'        => $revenueChart,
                'recent_transactions'  => $recentTransactions,
                'suspended_today'      => $suspendedToday,
                'total_clients'        => Client::count(),
            ];
        });
    }
}
