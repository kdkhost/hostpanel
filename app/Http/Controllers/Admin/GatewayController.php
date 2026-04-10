<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Gateway;
use App\Models\GatewayLog;
use App\Models\Invoice;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GatewayController extends Controller
{
    public function index(Request $request)
    {
        if ($request->expectsJson()) {
            return response()->json(Gateway::orderBy('sort_order')->get());
        }
        
        $gateways = Gateway::orderBy('sort_order')->get();
        return view('admin.gateways.index', compact('gateways'));
    }

    public function update(Request $request, Gateway $gateway): JsonResponse
    {
        $request->validate([
            'active' => 'boolean',
            'test_mode' => 'boolean',
            'fee_fixed' => 'nullable|numeric|min:0',
            'fee_percentage' => 'nullable|numeric|min:0|max:100',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $data = $request->only(['active', 'test_mode', 'fee_fixed', 'fee_percentage', 'sort_order']);

        if ($request->has('settings') && is_array($request->settings)) {
            $gateway->setSettingsEncryptedAttribute($request->settings);
            $data['settings'] = $gateway->settings;
        }

        $gateway->update($data);
        return response()->json([
            'message' => 'Gateway atualizado com sucesso!', 
            'gateway' => $gateway->fresh()
        ]);
    }

    public function configure(Gateway $gateway)
    {
        return view('admin.gateways.configure', compact('gateway'));
    }

    public function configureSave(Request $request, Gateway $gateway): JsonResponse
    {
        $request->validate([
            'active' => 'boolean',
            'test_mode' => 'boolean',
            'fee_fixed' => 'nullable|numeric|min:0',
            'fee_percentage' => 'nullable|numeric|min:0|max:100',
            'sort_order' => 'nullable|integer|min:0',
            'supports_recurring' => 'boolean',
            'supports_refund' => 'boolean',
            'due_days' => 'nullable|integer|min:1|max:365',
            'settings' => 'nullable|array',
        ]);

        $data = $request->only([
            'active', 'test_mode', 'fee_fixed', 'fee_percentage',
            'sort_order', 'supports_recurring', 'supports_refund', 'due_days'
        ]);

        // Processa configurações específicas do gateway
        if ($request->has('settings') && is_array($request->settings)) {
            $settings = $this->validateGatewaySettings($gateway->driver, $request->settings);
            $gateway->setSettingsEncryptedAttribute($settings);
            $data['settings'] = $gateway->settings;
        }

        $gateway->update($data);

        return response()->json(['message' => "Gateway '{$gateway->name}' configurado com sucesso!"]);
    }

    public function test(Gateway $gateway): JsonResponse
    {
        try {
            // Testa conectividade básica do gateway
            $result = $this->testGatewayConnection($gateway);
            
            GatewayLog::create([
                'gateway_id' => $gateway->id,
                'event_type' => 'test',
                'request_payload' => ['test' => true],
                'response' => $result,
                'ip_address' => request()->ip(),
                'success' => $result['success'] ?? false,
            ]);

            return response()->json([
                'message' => $result['message'] ?? "Teste do gateway '{$gateway->name}' realizado com sucesso!",
                'success' => $result['success'] ?? true,
                'details' => $result['details'] ?? null,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => "Erro ao testar gateway: " . $e->getMessage(),
                'success' => false,
            ], 422);
        }
    }

    public function refund(Request $request, \App\Models\Transaction $transaction): JsonResponse
    {
        $request->validate([
            'type' => 'required|in:full,partial',
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
                'transaction_id' => $transaction->id,
                'invoice_id' => $transaction->invoice_id,
                'client_id' => $transaction->client_id,
                'requested_by_type' => \App\Models\Admin::class,
                'requested_by_id' => auth('admin')->id(),
                'gateway' => $transaction->gateway,
                'gateway_refund_id' => $result['raw']['id'] ?? null,
                'type' => $request->type,
                'status' => $result['success'] ? 'completed' : 'failed',
                'amount' => $amount,
                'reason' => $request->reason,
                'meta' => $result['raw'] ?? [],
                'processed_at' => $result['success'] ? now() : null,
            ]);

            // Notificar cliente se bem-sucedido
            if ($result['success']) {
                try {
                    app(\App\Services\NotificationService::class)->send(
                        $transaction->invoice->client,
                        'refund_processed',
                        [
                            'name' => $transaction->invoice->client->name,
                            'amount' => number_format($amount, 2, ',', '.'),
                            'invoice_number' => $transaction->invoice->number,
                            'refund_type' => $request->type === 'full' ? 'total' : 'parcial',
                        ]
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

    public function webhook(Request $request, string $gateway): JsonResponse
    {
        $gatewayModel = Gateway::where('driver', $gateway)->first();

        if (!$gatewayModel) {
            return response()->json(['message' => 'Gateway não encontrado.'], 404);
        }

        GatewayLog::create([
            'gateway_id' => $gatewayModel->id,
            'event_type' => 'webhook',
            'request_payload' => $request->all(),
            'ip_address' => $request->ip(),
            'success' => true,
        ]);

        try {
            // Processa webhook usando o manager
            \App\Services\Gateways\GatewayManager::processWebhook($gatewayModel, $request->all());
            
            return response()->json(['received' => true]);
        } catch (\Exception $e) {
            \Log::error("Webhook processing failed for gateway {$gateway}: " . $e->getMessage());
            return response()->json(['error' => 'Processing failed'], 500);
        }
    }

    private function validateGatewaySettings(string $driver, array $settings): array
    {
        $validated = [];

        switch ($driver) {
            case 'paghiper':
                $validated = [
                    'api_key' => $settings['api_key'] ?? '',
                    'token' => $settings['token'] ?? '',
                    'default_method' => $settings['default_method'] ?? 'pix',
                    'pass_fee' => (bool)($settings['pass_fee'] ?? false),
                    'late_fee_enabled' => (bool)($settings['late_fee_enabled'] ?? false),
                    'late_fee_percent' => (float)($settings['late_fee_percent'] ?? 2.0),
                    'interest_daily' => (float)($settings['interest_daily'] ?? 0.033),
                ];
                break;

            case 'mercadopago':
                $validated = [
                    'access_token' => $settings['access_token'] ?? '',
                    'access_token_sandbox' => $settings['access_token_sandbox'] ?? '',
                    'default_method' => $settings['default_method'] ?? 'pix',
                    'pix_expiration_minutes' => (int)($settings['pix_expiration_minutes'] ?? 1440),
                    'webhook_secret' => $settings['webhook_secret'] ?? '',
                    'pass_fee' => (bool)($settings['pass_fee'] ?? false),
                ];
                break;

            case 'efirpro':
                $validated = [
                    'client_id' => $settings['client_id'] ?? '',
                    'client_secret' => $settings['client_secret'] ?? '',
                    'pix_key' => $settings['pix_key'] ?? '',
                    'cert_path' => $settings['cert_path'] ?? '',
                    'expiration_hours' => (int)($settings['expiration_hours'] ?? 24),
                    'pass_fee' => (bool)($settings['pass_fee'] ?? false),
                ];
                break;

            case 'bancointer':
                $validated = [
                    'client_id' => $settings['client_id'] ?? '',
                    'client_secret' => $settings['client_secret'] ?? '',
                    'pix_key' => $settings['pix_key'] ?? '',
                    'cert_path' => $settings['cert_path'] ?? '',
                    'key_path' => $settings['key_path'] ?? '',
                    'webhook_secret' => $settings['webhook_secret'] ?? '',
                ];
                break;

            case 'bancobrasil':
                $validated = [
                    'client_id' => $settings['client_id'] ?? '',
                    'client_secret' => $settings['client_secret'] ?? '',
                    'pix_key' => $settings['pix_key'] ?? '',
                    'developer_app_key_sandbox' => $settings['developer_app_key_sandbox'] ?? '',
                    'webhook_secret' => $settings['webhook_secret'] ?? '',
                ];
                break;

            case 'pagbank':
                $validated = [
                    'token' => $settings['token'] ?? '',
                    'token_sandbox' => $settings['token_sandbox'] ?? '',
                    'default_method' => $settings['default_method'] ?? 'pix',
                    'pass_fee' => (bool)($settings['pass_fee'] ?? false),
                ];
                break;

            default:
                $validated = $settings;
        }

        return $validated;
    }

    private function testGatewayConnection(Gateway $gateway): array
    {
        try {
            $manager = \App\Services\Gateways\GatewayManager::make($gateway);
            
            // Testa método básico do gateway se disponível
            if (method_exists($manager, 'testConnection')) {
                return $manager->testConnection();
            }

            return [
                'success' => true,
                'message' => "Gateway '{$gateway->name}' configurado corretamente.",
                'details' => [
                    'driver' => $gateway->driver,
                    'test_mode' => $gateway->test_mode,
                    'active' => $gateway->active,
                ]
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => "Erro na configuração: " . $e->getMessage(),
            ];
        }
    }
}
