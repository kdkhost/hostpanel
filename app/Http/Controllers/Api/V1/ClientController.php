<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ClientController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $clients = Client::when($request->search, fn($q) =>
            $q->where('name', 'like', "%{$request->search}%")
              ->orWhere('email', 'like', "%{$request->search}%")
        )->orderByDesc('created_at')->paginate($request->per_page ?? 20);
        return response()->json($clients);
    }

    public function show(Client $client): JsonResponse
    {
        return response()->json($client->load(['services', 'invoices']));
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name'     => 'required|string|max:100',
            'email'    => 'required|email|unique:clients,email',
            'password' => 'required|string|min:8',
        ]);

        $client = Client::create(array_merge(
            $request->only(['name', 'email', 'document_type', 'document_number', 'phone', 'company_name']),
            ['password' => Hash::make($request->password), 'status' => 'active']
        ));

        return response()->json($client, 201);
    }

    public function update(Request $request, Client $client): JsonResponse
    {
        $client->update($request->only(['name', 'phone', 'mobile', 'company_name', 'address', 'city', 'state', 'postcode', 'country']));
        return response()->json($client->fresh());
    }

    public function destroy(Client $client): JsonResponse
    {
        $client->delete();
        return response()->json(['message' => 'Cliente excluído.']);
    }
}
