<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Affiliate;
use App\Models\AffiliateCommission;
use App\Models\AffiliatePayout;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AffiliateController extends Controller
{
    public function index(Request $request)
    {
        if (!$request->expectsJson()) return view('admin.affiliates.index');

        $affiliates = Affiliate::with('client:id,name,email')
            ->withCount(['referrals', 'commissions', 'payouts'])
            ->when($request->search, fn($q) => $q->whereHas('client', fn($c) => $c->where('name', 'like', "%{$request->search}%")->orWhere('email', 'like', "%{$request->search}%")))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->orderByDesc('created_at')
            ->paginate($request->per_page ?? 20);

        return response()->json($affiliates);
    }

    public function commissions(Request $request): JsonResponse
    {
        $commissions = AffiliateCommission::with(['affiliate.client:id,name,email', 'referral.referredClient:id,name', 'invoice:id,total'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->orderByDesc('created_at')
            ->paginate($request->per_page ?? 20);

        return response()->json($commissions);
    }

    public function approveCommission(AffiliateCommission $commission): JsonResponse
    {
        if ($commission->status !== 'pending') {
            return response()->json(['message' => 'Comissão já processada.'], 422);
        }

        $commission->update(['status' => 'approved']);
        $commission->affiliate->increment('balance', $commission->commission_amount);
        $commission->affiliate->increment('total_earned', $commission->commission_amount);

        if (!$commission->referral->converted) {
            $commission->referral->update(['converted' => true, 'converted_at' => now()]);
            $commission->affiliate->increment('total_conversions');
        }

        return response()->json(['message' => 'Comissão aprovada!']);
    }

    public function rejectCommission(AffiliateCommission $commission): JsonResponse
    {
        if ($commission->status !== 'pending') {
            return response()->json(['message' => 'Comissão já processada.'], 422);
        }

        $commission->update(['status' => 'rejected']);
        return response()->json(['message' => 'Comissão rejeitada.']);
    }

    public function payouts(Request $request): JsonResponse
    {
        $payouts = AffiliatePayout::with('affiliate.client:id,name,email')
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->orderByDesc('created_at')
            ->paginate($request->per_page ?? 20);

        return response()->json($payouts);
    }

    public function processPayout(Request $request, AffiliatePayout $payout): JsonResponse
    {
        $request->validate(['status' => 'required|in:completed,rejected', 'admin_notes' => 'nullable|string']);

        if (!in_array($payout->status, ['pending', 'processing'])) {
            return response()->json(['message' => 'Saque já finalizado.'], 422);
        }

        $payout->update([
            'status'       => $request->status,
            'admin_notes'  => $request->admin_notes,
            'processed_at' => now(),
        ]);

        if ($request->status === 'completed') {
            $payout->affiliate->increment('total_withdrawn', $payout->amount);
        } elseif ($request->status === 'rejected') {
            // Refund balance
            $payout->affiliate->increment('balance', $payout->amount);
        }

        return response()->json(['message' => $request->status === 'completed' ? 'Saque pago!' : 'Saque rejeitado, saldo devolvido.']);
    }

    public function updateAffiliate(Request $request, Affiliate $affiliate): JsonResponse
    {
        $affiliate->update($request->only(['commission_rate', 'commission_type', 'status']));
        return response()->json(['message' => 'Afiliado atualizado!', 'affiliate' => $affiliate->fresh()]);
    }

    public function stats(): JsonResponse
    {
        return response()->json([
            'total_affiliates'  => Affiliate::count(),
            'active_affiliates' => Affiliate::where('status', 'active')->count(),
            'total_commissions' => AffiliateCommission::sum('commission_amount'),
            'pending_commissions' => AffiliateCommission::where('status', 'pending')->sum('commission_amount'),
            'pending_payouts'   => AffiliatePayout::whereIn('status', ['pending', 'processing'])->sum('amount'),
            'total_paid'        => AffiliatePayout::where('status', 'completed')->sum('amount'),
        ]);
    }
}
