<?php

namespace App\Jobs;

use App\Models\Invoice;
use App\Models\AffiliateReferral;
use App\Services\AffiliateService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessAffiliateCommissionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(AffiliateService $affiliateService): void
    {
        // Busca faturas pagas nas últimas 24h que ainda não geraram comissão
        $paidInvoices = Invoice::with(['client', 'affiliateCommissions'])
            ->where('status', 'paid')
            ->where('date_paid', '>=', now()->subDay())
            ->whereDoesntHave('affiliateCommissions')
            ->get();

        $processed = 0;

        foreach ($paidInvoices as $invoice) {
            // Verifica se o cliente foi referenciado por um afiliado
            $referral = AffiliateReferral::where('referred_client_id', $invoice->client_id)->first();
            
            if ($referral && $referral->affiliate->status === 'active') {
                try {
                    $affiliateService->processInvoice($invoice, $referral->affiliate);
                    $processed++;
                } catch (\Exception $e) {
                    Log::error("Failed to process affiliate commission for invoice #{$invoice->id}: " . $e->getMessage());
                }
            }
        }

        Log::info("ProcessAffiliateCommissionsJob: {$processed} commission(s) processed.");
    }
}