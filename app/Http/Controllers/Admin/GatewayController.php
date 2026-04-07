<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Gateway;
use App\Models\GatewayLog;
use App\Models\Invoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GatewayController extends Controller
{
    public function index(Request $request)
    {
        if (!$request->expectsJson()) return view('admin.gateways.index');
        return response()->json(Gateway::orderBy('sort_order')->get());
    }

    public function update(Request $request, Gateway $gateway): JsonResponse
    {
        $request->validate(['active' => 'boolean']);

        $data = $request->only(['active', 'test_mode', 'fee_fixed', 'fee_percentage', 'sort_order']);

        if ($request->has('settings')) {
            $gateway->setSettingsEncryptedAttribute($request->settings);
            $data['settings'] = $gateway->settings;
        }

        $gateway->update($data);
        return response()->json(['message' => 'Gateway atualizado!', 'gateway' => $gateway->fresh()]);
    }

    public function configure(Gateway $gateway)
    {
        return view('admin.gateways.configure', compact('gateway'));
    }

    public function configureSave(Request $request, Gateway $gateway): JsonResponse
    {
        $data = $request->only([
            'active', 'test_mode', 'fee_fixed', 'fee_percentage',
            'sort_order', 'supports_recurring', 'supports_refund',
            'due_days', 'pass_fee', 'late_fee_enabled',
            'late_fee_percent', 'interest_daily',
        ]);

        if ($request->has('settings') && is_array($request->settings)) {
            $gateway->setSettingsEncryptedAttribute($request->settings);
            $data['settings'] = $gateway->settings;
        }

        $gateway->update($data);

        return response()->json(['message' => "Gateway '{$gateway->name}' salvo com sucesso!"]);
    }

    public function refund(Request $request, \App\Models\Transaction $transaction): JsonResponse
    {
        $request->validate([
            'type'   => 'required|in:full,partial',
            'amount' => 'required_if:type,partial|numeric|min:0.01',
            'reason' => 'nullable|string|max:500',
        ]);

        if (!$transaction->canRefund()) {
            return response()->json(['message' => 'Esta transação não pode ser estornada.'], 422);
        }

        $amount = $request->type === 'full'
            ? (float) $transaction->amount
            : (float) $request->amount;

        try {
            $result = \App\Services\Gateways\GatewayManager::refund(
                $transaction, $amount, $request->type
            );

            \App\Models\Refund::create([
                'transaction_id'   => $transaction->id,
                'invoice_id'       => $transaction->invoice_id,
                'client_id'        => $transaction->client_id,
                'requested_by_type'=> \App\Models\Admin::class,
                'requested_by_id'  => auth('admin')->id(),
                'gateway'          => $transaction->gateway,
                'gateway_refund_id'=> $result['raw']['id'] ?? null,
                'type'             => $request->type,
                'status'           => $result['success'] ? 'completed' : 'failed',
                'amount'           => $amount,
                'reason'           => $request->reason,
                'meta'             => $result['raw'] ?? [],
                'processed_at'     => $result['success'] ? now() : null,
            ]);

            // Notificar cliente
            if ($result['success']) {
                try {
                    app(\App\Services\InvoiceNotificationService::class)
                        ->sendRefundProcessed(
                            $transaction->invoice->load('client'),
                            $amount,
                            $request->type
                        );
                } catch (\Throwable) {}
            }

            return response()->json([
                'message' => $result['message'] ?? 'Reembolso processado.',
                'success' => $result['success'],
            ]);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function test(Gateway $gateway): JsonResponse
    {
        return response()->json(['message' => "Teste do gateway '{$gateway->name}' realizado. Verifique os logs."]);
    }

    public function webhook(Request $request, string $gateway): JsonResponse
    {
        $gatewayModel = Gateway::where('slug', $gateway)->first();

        if (!$gatewayModel) {
            return response()->json(['message' => 'Gateway não encontrado.'], 404);
        }

        GatewayLog::create([
            'gateway_id'       => $gatewayModel->id,
            'event_type'       => 'webhook',
            'request_payload'  => $request->all(),
            'ip_address'       => $request->ip(),
            'success'          => true,
        ]);

        $transactionId = $request->input('transaction_id') ?? $request->input('id');
        $status        = $request->input('status') ?? $request->input('event');

        if ($transactionId && in_array($status, ['paid', 'approved', 'completed', 'CONFIRMED'])) {
            $invoice = Invoice::where('meta->gateway_transaction_id', $transactionId)
                ->orWhereHas('transactions', fn($q) => $q->where('gateway_transaction_id', $transactionId))
                ->first();

            if ($invoice && !$invoice->isPaid()) {
                app(\App\Services\BillingService::class)->applyPayment(
                    $invoice, $invoice->amount_due, $gateway, $transactionId
                );
            }
        }

        return response()->json(['received' => true]);
    }
}
