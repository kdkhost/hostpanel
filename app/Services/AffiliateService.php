<?php

namespace App\Services;

use App\Models\Affiliate;
use App\Models\AffiliateCommission;
use App\Models\AffiliateReferral;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Setting;
use Illuminate\Support\Str;

class AffiliateService
{
    /**
     * Register a new affiliate for a client.
     */
    public function register(Client $client): Affiliate
    {
        return Affiliate::firstOrCreate(
            ['client_id' => $client->id],
            [
                'referral_code'   => $this->generateCode(),
                'commission_rate' => (float) Setting::get('affiliate.commission_rate', 10),
                'commission_type' => Setting::get('affiliate.commission_type', 'percentage'),
                'status'          => 'active',
            ]
        );
    }

    /**
     * Track a referral visit (called when ?ref=CODE is present).
     */
    public function trackVisit(string $code, string $ip, ?string $landingPage = null): void
    {
        $affiliate = Affiliate::where('referral_code', $code)->where('status', 'active')->first();
        if (!$affiliate) return;

        session(['affiliate_ref' => $code, 'affiliate_id' => $affiliate->id]);
    }

    /**
     * Link a new client registration to the referring affiliate.
     */
    public function linkReferral(Client $newClient): ?AffiliateReferral
    {
        $affiliateId = session('affiliate_id');
        if (!$affiliateId) return null;

        $affiliate = Affiliate::find($affiliateId);
        if (!$affiliate || $affiliate->client_id === $newClient->id) return null;

        // Prevent duplicate referrals
        $existing = AffiliateReferral::where('affiliate_id', $affiliate->id)
            ->where('referred_client_id', $newClient->id)->first();
        if ($existing) return $existing;

        $referral = AffiliateReferral::create([
            'affiliate_id'      => $affiliate->id,
            'referred_client_id'=> $newClient->id,
            'ip'                => request()->ip(),
            'landing_page'      => session('affiliate_landing', '/'),
        ]);

        $affiliate->increment('total_referrals');
        session()->forget(['affiliate_ref', 'affiliate_id', 'affiliate_landing']);

        return $referral;
    }

    /**
     * Generate commission when an invoice from a referred client is paid.
     */
    public function processInvoice(Invoice $invoice): ?AffiliateCommission
    {
        if (!Setting::get('affiliate.enabled', false)) return null;

        $referral = AffiliateReferral::where('referred_client_id', $invoice->client_id)->first();
        if (!$referral) return null;

        $affiliate = $referral->affiliate;
        if (!$affiliate || $affiliate->status !== 'active') return null;

        // Check if commission already exists for this invoice
        $existing = AffiliateCommission::where('invoice_id', $invoice->id)->first();
        if ($existing) return $existing;

        // Only on first invoice or recurring? (setting)
        $onlyFirst = Setting::get('affiliate.only_first_invoice', false);
        if ($onlyFirst && $referral->converted) return null;

        // Calculate commission
        $rate   = $affiliate->commission_rate;
        $type   = $affiliate->commission_type;
        $amount = $type === 'percentage'
            ? round($invoice->total * ($rate / 100), 2)
            : $rate;

        // Min payout check
        $minCommission = (float) Setting::get('affiliate.min_commission', 0);
        if ($amount < $minCommission) return null;

        $commission = AffiliateCommission::create([
            'affiliate_id'      => $affiliate->id,
            'referral_id'       => $referral->id,
            'invoice_id'        => $invoice->id,
            'invoice_amount'    => $invoice->total,
            'commission_amount' => $amount,
            'rate_applied'      => $rate,
            'type'              => $type,
            'status'            => Setting::get('affiliate.auto_approve', false) ? 'approved' : 'pending',
            'description'       => "Comissão da fatura #{$invoice->id}",
        ]);

        // If auto-approved, credit balance immediately
        if ($commission->status === 'approved') {
            $affiliate->increment('balance', $amount);
            $affiliate->increment('total_earned', $amount);
        }

        // Mark referral as converted
        if (!$referral->converted) {
            $referral->update(['converted' => true, 'converted_at' => now()]);
            $affiliate->increment('total_conversions');
        }

        return $commission;
    }

    /**
     * Generate unique referral code.
     */
    private function generateCode(): string
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (Affiliate::where('referral_code', $code)->exists());

        return $code;
    }
}
