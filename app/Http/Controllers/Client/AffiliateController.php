<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Affiliate;
use App\Models\AffiliatePayout;
use App\Services\AffiliateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AffiliateController extends Controller
{
    public function __construct(private AffiliateService $service) {}

    public function index(Request $request)
    {
        $client    = auth('client')->user();
        $affiliate = Affiliate::where('client_id', $client->id)->first();

        if ($request->expectsJson()) {
            if (!$affiliate) return response()->json(['enrolled' => false]);

            $affiliate->load(['referrals.referredClient:id,name,email,created_at', 'commissions', 'payouts']);
            return response()->json([
                'enrolled'  => true,
                'affiliate' => $affiliate,
                'stats'     => [
                    'referral_url'     => $affiliate->referral_url,
                    'total_referrals'  => $affiliate->total_referrals,
                    'total_conversions'=> $affiliate->total_conversions,
                    'pending'          => $affiliate->commissions()->where('status', 'pending')->sum('commission_amount'),
                    'balance'          => $affiliate->balance,
                    'total_earned'     => $affiliate->total_earned,
                    'total_withdrawn'  => $affiliate->total_withdrawn,
                ],
            ]);
        }

        return view('client.affiliates.index');
    }

    public function enroll(): JsonResponse
    {
        $client    = auth('client')->user();
        $affiliate = $this->service->register($client);

        return response()->json([
            'message'      => 'Você agora é um afiliado!',
            'referral_url' => $affiliate->referral_url,
            'affiliate'    => $affiliate,
        ]);
    }

    public function requestPayout(Request $request): JsonResponse
    {
        $request->validate([
            'amount'          => 'required|numeric|min:1',
            'method'          => 'required|in:pix,bank_transfer,credit',
            'payment_details' => 'required|string|max:500',
        ]);

        $client    = auth('client')->user();
        $affiliate = Affiliate::where('client_id', $client->id)->where('status', 'active')->firstOrFail();

        $minPayout = (float) \App\Models\Setting::get('affiliate.min_payout', 50);
        if ($request->amount < $minPayout) {
            return response()->json(['message' => "Valor mínimo para saque: R$ " . number_format($minPayout, 2, ',', '.')], 422);
        }

        if ($request->amount > $affiliate->balance) {
            return response()->json(['message' => 'Saldo insuficiente.'], 422);
        }

        // Check pending payouts
        $pendingPayout = $affiliate->payouts()->whereIn('status', ['pending', 'processing'])->exists();
        if ($pendingPayout) {
            return response()->json(['message' => 'Você já tem um saque em processamento.'], 422);
        }

        $payout = AffiliatePayout::create([
            'affiliate_id'    => $affiliate->id,
            'amount'          => $request->amount,
            'method'          => $request->method,
            'payment_details' => $request->payment_details,
            'status'          => 'pending',
        ]);

        // Deduct from balance
        $affiliate->decrement('balance', $request->amount);

        return response()->json(['message' => 'Solicitação de saque enviada!', 'payout' => $payout]);
    }

    public function updatePaymentInfo(Request $request): JsonResponse
    {
        $request->validate(['payment_info' => 'required|string|max:1000']);

        $client    = auth('client')->user();
        $affiliate = Affiliate::where('client_id', $client->id)->firstOrFail();
        $affiliate->update(['payment_info' => $request->payment_info]);

        return response()->json(['message' => 'Dados de pagamento atualizados!']);
    }
}
