<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Services\BillingService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InvoiceController extends Controller
{
    protected function client() { return Auth::guard('client')->user(); }

    public function index(Request $request)
    {
        if ($request->expectsJson()) {
            $invoices = Invoice::where('client_id', $this->client()->id)
                ->when($request->status, fn($q) => $q->where('status', $request->status))
                ->orderByDesc('created_at')
                ->paginate(15);
            return response()->json($invoices);
        }
        return view('client.invoices.index');
    }

    public function show(Request $request, Invoice $invoice)
    {
        $this->authorizeInvoice($invoice);
        $invoice->load(['items.service', 'transactions', 'coupon']);

        if ($request->expectsJson()) {
            return response()->json($invoice);
        }

        $gateways        = \App\Models\Gateway::where('active', true)->get();
        $companySettings = \App\Models\Setting::whereIn('key', [
            'company_name', 'company_address', 'company_cnpj', 'meta_description', 'tagline',
        ])->pluck('value', 'key')->toArray();

        return view('client.invoices.show', compact('invoice', 'gateways', 'companySettings'));
    }

    public function pdf(Invoice $invoice)
    {
        $this->authorizeInvoice($invoice);
        $invoice->load(['client', 'items', 'transactions']);
        $pdf = Pdf::loadView('client.invoices.pdf', compact('invoice'));
        return $pdf->download("fatura-{$invoice->number}.pdf");
    }

    public function pay(Request $request, Invoice $invoice): JsonResponse
    {
        $this->authorizeInvoice($invoice);

        if ($invoice->isPaid()) {
            return response()->json(['message' => 'Esta fatura já está paga.'], 422);
        }

        $request->validate([
            'gateway' => 'required|string',
            'method'  => 'nullable|string|in:pix,billet,boleto,credit_card',
        ]);

        try {
            $result = app(BillingService::class)->initiatePayment(
                $invoice,
                $request->gateway,
                ['method' => $request->input('method', 'pix')]
            );

            return response()->json(array_merge($result, [
                'invoice' => $invoice->fresh(['transactions']),
            ]));
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function applyCredit(Request $request, Invoice $invoice): JsonResponse
    {
        $this->authorizeInvoice($invoice);
        $request->validate(['amount' => 'required|numeric|min:0.01']);

        $client = $this->client();
        $amount = min((float) $request->amount, $client->credit_balance, $invoice->amount_due);

        if ($amount <= 0) {
            return response()->json(['message' => 'Saldo de crédito insuficiente ou fatura já está paga.'], 422);
        }

        app(BillingService::class)->applyCreditToInvoice($invoice, $amount);

        return response()->json([
            'message' => "R$ " . number_format($amount, 2, ',', '.') . " de crédito aplicado!",
            'invoice' => $invoice->fresh(),
        ]);
    }

    protected function authorizeInvoice(Invoice $invoice): void
    {
        if ($invoice->client_id !== $this->client()->id) {
            abort(403, 'Acesso não autorizado.');
        }
    }
}
