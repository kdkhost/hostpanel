<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function revenue(Request $request)
    {
        if (!$request->expectsJson()) {
            return view('admin.reports.revenue');
        }

        $from = $request->date_from ?? now()->subMonths(11)->startOfMonth()->toDateString();
        $to   = $request->date_to   ?? now()->toDateString();

        $monthly = Invoice::select(
            DB::raw('DATE_FORMAT(date_paid, "%Y-%m") as month'),
            DB::raw('SUM(total) as total'),
            DB::raw('COUNT(*) as count')
        )
            ->where('status', 'paid')
            ->whereBetween('date_paid', [$from, $to])
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $byGateway = Invoice::select('gateway', DB::raw('SUM(total) as total'), DB::raw('COUNT(*) as count'))
            ->where('status', 'paid')
            ->whereBetween('date_paid', [$from, $to])
            ->whereNotNull('gateway')
            ->groupBy('gateway')
            ->get();

        $summary = Invoice::where('status', 'paid')->whereBetween('date_paid', [$from, $to])
            ->selectRaw('SUM(total) as total_revenue, COUNT(*) as total_invoices, AVG(total) as avg_invoice')
            ->first();

        return response()->json(compact('monthly', 'byGateway', 'summary'));
    }

    public function services(Request $request)
    {
        if (!$request->expectsJson()) {
            return view('admin.reports.services');
        }

        $byStatus = Service::select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')->get();

        $byProduct = Service::select('product_id', DB::raw('COUNT(*) as count'))
            ->with('product:id,name')
            ->groupBy('product_id')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        $newThisMonth = Service::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        $terminationsThisMonth = Service::where('status', 'terminated')
            ->whereMonth('updated_at', now()->month)
            ->whereYear('updated_at', now()->year)
            ->count();

        $churnRate = Service::where('status', 'terminated')->count() /
            max(Service::count(), 1) * 100;

        return response()->json(compact('byStatus', 'byProduct', 'newThisMonth', 'terminationsThisMonth', 'churnRate'));
    }

    public function overdue(Request $request)
    {
        if (!$request->expectsJson()) {
            return view('admin.reports.overdue');
        }

        $invoices = Invoice::with(['client:id,name,email,phone'])
            ->where('status', 'overdue')
            ->orderByDesc('amount_due')
            ->get();

        $totalOverdue = $invoices->sum('amount_due');
        $count        = $invoices->count();
        $byAge        = [
            '1-7_dias'   => $invoices->filter(fn($i) => $i->date_due->diffInDays(now()) <= 7)->count(),
            '8-30_dias'  => $invoices->filter(fn($i) => $i->date_due->diffInDays(now()) > 7 && $i->date_due->diffInDays(now()) <= 30)->count(),
            '31-90_dias' => $invoices->filter(fn($i) => $i->date_due->diffInDays(now()) > 30 && $i->date_due->diffInDays(now()) <= 90)->count(),
            '90+_dias'   => $invoices->filter(fn($i) => $i->date_due->diffInDays(now()) > 90)->count(),
        ];

        return response()->json(compact('invoices', 'totalOverdue', 'count', 'byAge'));
    }
}
