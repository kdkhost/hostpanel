<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Services\BillingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $invoices = Invoice::with(['client:id,name,email'])
            ->when($request->client_id, fn($q) => $q->where('client_id', $request->client_id))
            ->when($request->status,    fn($q) => $q->where('status', $request->status))
            ->orderByDesc('created_at')
            ->paginate($request->per_page ?? 20);
        return response()->json($invoices);
    }

    public function show(Invoice $invoice): JsonResponse
    {
        return response()->json($invoice->load(['client', 'items', 'transactions']));
    }

    public function pay(Request $request, Invoice $invoice): JsonResponse
    {
        $request->validate(['amount' => 'required|numeric|min:0.01', 'gateway' => 'required|string']);
        $tx = app(BillingService::class)->applyPayment($invoice, (float) $request->amount, $request->gateway, $request->transaction_id);
        return response()->json(['message' => 'Pagamento registrado.', 'transaction' => $tx, 'invoice' => $invoice->fresh()]);
    }

    public function cancel(Invoice $invoice): JsonResponse
    {
        if (!$invoice->isCancellable()) return response()->json(['message' => 'Fatura não pode ser cancelada.'], 422);
        $invoice->update(['status' => 'cancelled']);
        return response()->json(['message' => 'Fatura cancelada.']);
    }
}
