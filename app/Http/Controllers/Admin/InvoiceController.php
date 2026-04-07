<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Client;
use App\Services\BillingService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        if ($request->expectsJson()) {
            $query = Invoice::with(['client:id,name,email'])
                ->when($request->search, fn($q) => $q->where(function ($q2) use ($request) {
                    $q2->where('number', 'like', "%{$request->search}%")
                       ->orWhereHas('client', fn($q3) => $q3->where('name', 'like', "%{$request->search}%")
                           ->orWhere('email', 'like', "%{$request->search}%"));
                }))
                ->when($request->status, fn($q) => $q->where('status', $request->status))
                ->when($request->date_from, fn($q) => $q->whereDate('date_issued', '>=', $request->date_from))
                ->when($request->date_to,   fn($q) => $q->whereDate('date_issued', '<=', $request->date_to))
                ->orderBy($request->sort_by ?? 'created_at', $request->sort_dir ?? 'desc');

            return response()->json($query->paginate($request->per_page ?? 20));
        }
        return view('admin.invoices.index');
    }

    public function show(Invoice $invoice)
    {
        $invoice->load(['client', 'items.service', 'transactions', 'coupon']);
        $gateways = \App\Models\Gateway::where('active', true)->get();
        return view('admin.invoices.show', compact('invoice', 'gateways'));
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'client_id'  => 'required|exists:clients,id',
            'date_due'   => 'required|date',
            'items'      => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.amount'      => 'required|numeric|min:0',
        ]);

        $client  = Client::findOrFail($request->client_id);
        $subtotal = collect($request->items)->sum('amount');

        $invoice = Invoice::create([
            'client_id'   => $client->id,
            'status'      => 'pending',
            'subtotal'    => $subtotal,
            'total'       => $subtotal,
            'amount_due'  => $subtotal,
            'currency'    => 'BRL',
            'date_issued' => now()->toDateString(),
            'date_due'    => $request->date_due,
            'notes'       => $request->notes,
        ]);

        foreach ($request->items as $item) {
            $invoice->items()->create([
                'description' => $item['description'],
                'amount'      => $item['amount'],
                'unit_price'  => $item['amount'],
                'quantity'    => 1,
                'type'        => $item['type'] ?? 'manual',
            ]);
        }

        return response()->json(['message' => 'Fatura criada com sucesso!', 'invoice' => $invoice->load('items')], 201);
    }

    public function markPaid(Request $request, Invoice $invoice): JsonResponse
    {
        $request->validate([
            'amount'  => 'required|numeric|min:0.01',
            'gateway' => 'required|string',
        ]);

        $transaction = app(BillingService::class)->applyPayment(
            $invoice,
            (float) $request->amount,
            $request->gateway,
            $request->transaction_id
        );

        return response()->json([
            'message'     => 'Pagamento registrado com sucesso!',
            'transaction' => $transaction,
            'invoice'     => $invoice->fresh(),
        ]);
    }

    public function cancel(Invoice $invoice): JsonResponse
    {
        if (!$invoice->isCancellable()) {
            return response()->json(['message' => 'Esta fatura não pode ser cancelada.'], 422);
        }
        $invoice->update(['status' => 'cancelled']);
        return response()->json(['message' => 'Fatura cancelada com sucesso!']);
    }

    public function pdf(Invoice $invoice)
    {
        $invoice->load(['client', 'items', 'transactions']);
        $pdf = Pdf::loadView('admin.invoices.pdf', compact('invoice'));
        return $pdf->download("fatura-{$invoice->number}.pdf");
    }

    public function applyLateFees(Invoice $invoice): JsonResponse
    {
        app(BillingService::class)->applyLateFees($invoice);
        return response()->json(['message' => 'Multa e juros aplicados!', 'invoice' => $invoice->fresh()]);
    }

    public function sendEmail(Invoice $invoice): JsonResponse
    {
        try {
            \Mail::to($invoice->client->email)
                ->send(new \App\Mail\InvoiceNotification($invoice));
            return response()->json(['message' => 'Fatura enviada por e-mail com sucesso!']);
        } catch (\Exception $e) {
            \Log::error('Falha ao enviar fatura por e-mail: ' . $e->getMessage());
            return response()->json(['message' => 'Erro ao enviar e-mail. Tente novamente.'], 422);
        }
    }
}
