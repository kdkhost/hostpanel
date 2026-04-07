<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ApiToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email', 'password' => 'required|string']);

        $client = Client::where('email', $request->email)->first();

        if (!$client || !Hash::check($request->password, $client->password)) {
            return response()->json(['message' => 'Credenciais inválidas.'], 401);
        }

        if ($client->status !== 'active') {
            return response()->json(['message' => 'Conta inativa ou bloqueada.'], 403);
        }

        ['token' => $tokenModel, 'plain_token' => $plain] = ApiToken::generate(
            'API Login - ' . now()->toDateTimeString(),
            $client->id
        );

        return response()->json([
            'token'      => $plain,
            'token_type' => 'Bearer',
            'client'     => $client->only(['id', 'name', 'email', 'status', 'credit_balance']),
        ]);
    }

    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'name'            => 'required|string|max:100',
            'email'           => 'required|email|unique:clients,email',
            'password'        => 'required|string|min:8',
            'document_number' => 'nullable|string',
        ]);

        $client = Client::create([
            'name'            => $request->name,
            'email'           => $request->email,
            'password'        => Hash::make($request->password),
            'document_number' => preg_replace('/\D/', '', $request->document_number ?? ''),
            'document_type'   => $request->document_type ?? 'cpf',
            'status'          => 'active',
        ]);

        ['plain_token' => $plain] = ApiToken::generate('API Register', $client->id);

        return response()->json(['token' => $plain, 'client' => $client], 201);
    }

    public function logout(Request $request): JsonResponse
    {
        $apiToken = $request->attributes->get('api_token');
        if ($apiToken) $apiToken->update(['active' => false]);
        return response()->json(['message' => 'Logout realizado com sucesso.']);
    }

    public function me(Request $request): JsonResponse
    {
        $apiToken = $request->attributes->get('api_token');
        $client   = $apiToken?->client;
        return response()->json($client);
    }
}
