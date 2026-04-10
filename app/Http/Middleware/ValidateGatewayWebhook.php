<?php

namespace App\Http\Middleware;

use App\Models\Gateway;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ValidateGatewayWebhook
{
    public function handle(Request $request, Closure $next, string $gatewaySlug)
    {
        $gateway = Gateway::where('slug', $gatewaySlug)->where('active', true)->first();
        
        if (!$gateway) {
            Log::warning("Webhook received for inactive/unknown gateway: {$gatewaySlug}");
            return response()->json(['error' => 'Gateway not found'], 404);
        }

        // Valida assinatura baseado no tipo de gateway
        if (!$this->validateSignature($request, $gateway)) {
            Log::warning("Invalid webhook signature for gateway: {$gatewaySlug}", [
                'ip' => $request->ip(),
                'headers' => $request->headers->all(),
            ]);
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        // Adiciona o gateway ao request para uso no controller
        $request->attributes->set('gateway', $gateway);
        
        return $next($request);
    }

    private function validateSignature(Request $request, Gateway $gateway): bool
    {
        $payload = $request->getContent();
        
        switch ($gateway->driver) {
            case 'paghiper':
                return $this->validatePagHiperSignature($request, $gateway, $payload);
                
            case 'mercadopago':
                return $this->validateMercadoPagoSignature($request, $gateway, $payload);
                
            case 'pagbank':
                return $this->validatePagBankSignature($request, $gateway, $payload);
                
            case 'gerencianet':
                return $this->validateGerencianetSignature($request, $gateway, $payload);
                
            case 'banco_inter':
                return $this->validateBancoInterSignature($request, $gateway, $payload);
                
            case 'banco_brasil':
                return $this->validateBancoBrasilSignature($request, $gateway, $payload);
                
            default:
                // Para gateways sem validação específica, aceita qualquer requisição
                return true;
        }
    }

    private function validatePagHiperSignature(Request $request, Gateway $gateway, string $payload): bool
    {
        $token = $gateway->getSetting('token');
        if (!$token) return false;

        $receivedSignature = $request->header('X-Hub-Signature');
        if (!$receivedSignature) return false;

        $expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, $token);
        
        return hash_equals($expectedSignature, $receivedSignature);
    }

    private function validateMercadoPagoSignature(Request $request, Gateway $gateway, string $payload): bool
    {
        $secret = $gateway->getSetting('webhook_secret');
        if (!$secret) return false;

        $receivedSignature = $request->header('X-Signature');
        if (!$receivedSignature) return false;

        // MercadoPago usa formato: ts=timestamp,v1=signature
        if (!preg_match('/ts=(\d+),v1=([a-f0-9]+)/', $receivedSignature, $matches)) {
            return false;
        }

        $timestamp = $matches[1];
        $signature = $matches[2];
        
        $expectedSignature = hash_hmac('sha256', "id={$request->input('id')}&topic={$request->input('topic')}", $secret);
        
        return hash_equals($expectedSignature, $signature);
    }

    private function validatePagBankSignature(Request $request, Gateway $gateway, string $payload): bool
    {
        $token = $gateway->getSetting('token');
        if (!$token) return false;

        $receivedSignature = $request->header('Authorization');
        if (!$receivedSignature || !str_starts_with($receivedSignature, 'Bearer ')) {
            return false;
        }

        $signature = substr($receivedSignature, 7);
        
        return hash_equals($token, $signature);
    }

    private function validateGerencianetSignature(Request $request, Gateway $gateway, string $payload): bool
    {
        $clientSecret = $gateway->getSetting('client_secret');
        if (!$clientSecret) return false;

        $receivedSignature = $request->header('Pix-Signature');
        if (!$receivedSignature) return false;

        $expectedSignature = hash_hmac('sha256', $payload, $clientSecret);
        
        return hash_equals($expectedSignature, $receivedSignature);
    }

    private function validateBancoInterSignature(Request $request, Gateway $gateway, string $payload): bool
    {
        $secret = $gateway->getSetting('webhook_secret');
        if (!$secret) return false;

        $receivedSignature = $request->header('X-Inter-Signature');
        if (!$receivedSignature) return false;

        $expectedSignature = hash_hmac('sha256', $payload, $secret);
        
        return hash_equals($expectedSignature, $receivedSignature);
    }

    private function validateBancoBrasilSignature(Request $request, Gateway $gateway, string $payload): bool
    {
        $secret = $gateway->getSetting('webhook_secret');
        if (!$secret) return false;

        $receivedSignature = $request->header('X-BB-Signature');
        if (!$receivedSignature) return false;

        $expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, $secret);
        
        return hash_equals($expectedSignature, $receivedSignature);
    }
}