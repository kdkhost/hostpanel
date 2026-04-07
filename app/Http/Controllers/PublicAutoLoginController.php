<?php

namespace App\Http\Controllers;

use App\Services\AutoLoginService;
use Illuminate\Http\Request;

class PublicAutoLoginController extends Controller
{
    public function __construct(private AutoLoginService $autoLoginService) {}

    /**
     * Valida token público e redireciona para o painel de hospedagem.
     * Rota pública — sem autenticação.
     */
    public function access(Request $request, string $token)
    {
        $result = $this->autoLoginService->resolve($token, $request->ip());

        if ($result['valid']) {
            return redirect()->away($result['panel_url']);
        }

        $reason  = $result['reason'] ?? 'unknown';
        $service = $result['service'] ?? null;
        $client  = $result['client'] ?? $service?->client;

        return response()->view('autologin.invalid', [
            'reason'     => $reason,
            'expires_at' => $result['expires_at'] ?? null,
            'service'    => $service,
            'client'     => $client,
            'message'    => $result['message'] ?? null,
        ], match ($reason) {
            'not_found' => 404,
            'expired'   => 410,
            default     => 422,
        });
    }
}
