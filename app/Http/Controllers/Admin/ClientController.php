<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Support\InputSanitizer;
use App\Services\BillingService;
use App\Services\ImpersonationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        if ($request->expectsJson()) {
            $query = Client::withCount(['services', 'invoices', 'tickets'])
                ->when($request->search, fn($q) => $q->where(function ($q2) use ($request) {
                    $q2->where('name', 'like', "%{$request->search}%")
                       ->orWhere('email', 'like', "%{$request->search}%")
                       ->orWhere('document_number', 'like', "%{$request->search}%");
                }))
                ->when($request->status, fn($q) => $q->where('status', $request->status))
                ->orderBy($request->sort_by ?? 'created_at', $request->sort_dir ?? 'desc');

            return response()->json($query->paginate($request->per_page ?? 20));
        }

        return view('admin.clients.index');
    }

    public function show(Client $client)
    {
        $client->load([
            'services.product',
            'invoices' => fn($q) => $q->orderByDesc('created_at')->limit(10),
            'tickets'  => fn($q) => $q->orderByDesc('created_at')->limit(10),
            'loginLogs' => fn($q) => $q->orderByDesc('created_at')->limit(10),
        ]);
        $client->loadCount(['services', 'invoices', 'tickets']);

        return view('admin.clients.show', compact('client'));
    }

    public function edit(Client $client)
    {
        return redirect()->route('admin.clients.show', $client)->with('open_edit', true);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name'            => 'required|string|max:100',
            'email'           => 'required|email|unique:clients,email',
            'password'        => 'required|string|min:8',
            'document_type'   => 'required|in:cpf,cnpj',
            'document_number' => 'required|string',
            'status'          => 'required|in:active,inactive,pending',
        ]);

        $client = Client::create([
            'name'            => $request->name,
            'email'           => $request->email,
            'password'        => Hash::make($request->password),
            'document_type'   => $request->document_type,
            'document_number' => InputSanitizer::document($request->document_number),
            'phone'           => InputSanitizer::phone($request->phone),
            'mobile'          => InputSanitizer::phone($request->mobile),
            'whatsapp'        => InputSanitizer::phone($request->whatsapp),
            'company_name'    => $request->company_name,
            'address'         => $request->address,
            'address_number'  => $request->address_number,
            'address_complement' => $request->address_complement,
            'neighborhood'    => $request->neighborhood,
            'city'            => $request->city,
            'state'           => InputSanitizer::uf($request->state),
            'postcode'        => InputSanitizer::postcode($request->postcode),
            'country'         => $request->country ?? 'BR',
            'status'          => $request->status,
        ]);

        activity()->causedBy(auth('admin')->user())->on($client)->log('Cliente criado');

        return response()->json(['message' => 'Cliente criado com sucesso!', 'client' => $client], 201);
    }

    public function update(Request $request, Client $client): JsonResponse
    {
        $request->validate([
            'name'  => 'required|string|max:100',
            'email' => 'required|email|unique:clients,email,' . $client->id,
        ]);

        $data = $request->only([
            'name', 'email', 'document_type', 'document_number', 'phone', 'mobile',
            'whatsapp', 'company_name', 'address', 'address_number', 'address_complement',
            'neighborhood', 'city', 'state', 'postcode', 'country', 'status', 'notes',
        ]);

        if ($request->filled('password')) {
            $request->validate(['password' => 'min:8']);
            $data['password'] = Hash::make($request->password);
        }

        if (array_key_exists('document_number', $data)) {
            $data['document_number'] = InputSanitizer::document($data['document_number']);
        }
        if (array_key_exists('phone', $data)) {
            $data['phone'] = InputSanitizer::phone($data['phone']);
        }
        if (array_key_exists('mobile', $data)) {
            $data['mobile'] = InputSanitizer::phone($data['mobile']);
        }
        if (array_key_exists('whatsapp', $data)) {
            $data['whatsapp'] = InputSanitizer::phone($data['whatsapp']);
        }
        if (array_key_exists('postcode', $data)) {
            $data['postcode'] = InputSanitizer::postcode($data['postcode']);
        }
        if (array_key_exists('state', $data)) {
            $data['state'] = InputSanitizer::uf($data['state']);
        }

        $client->update($data);
        activity()->causedBy(auth('admin')->user())->on($client)->log('Cliente atualizado');

        return response()->json(['message' => 'Cliente atualizado com sucesso!', 'client' => $client->fresh()]);
    }

    public function destroy(Client $client): JsonResponse
    {
        if ($client->services()->where('status', 'active')->exists()) {
            return response()->json(['message' => 'Não é possível excluir um cliente com serviços ativos.'], 422);
        }

        activity()->causedBy(auth('admin')->user())->on($client)->log('Cliente excluído');
        $client->delete();

        return response()->json(['message' => 'Cliente excluído com sucesso!']);
    }

    public function impersonate(Request $request, Client $client): JsonResponse
    {
        $request->validate(['reason' => 'nullable|string|max:500']);

        $admin = auth('admin')->user();

        if (!$admin->hasPermissionTo('impersonate_clients')) {
            return response()->json(['message' => 'Sem permissão para impersonar clientes.'], 403);
        }

        app(ImpersonationService::class)->impersonate($admin, $client, $request->reason);

        return response()->json(['redirect' => route('client.dashboard')]);
    }

    public function addCredit(Request $request, Client $client): JsonResponse
    {
        $request->validate([
            'amount'      => 'required|numeric|min:0.01',
            'description' => 'required|string|max:255',
        ]);

        app(BillingService::class)->addCredit(
            $client,
            (float) $request->amount,
            $request->description,
            auth('admin')->id()
        );

        return response()->json(['message' => "Crédito de R$ {$request->amount} adicionado com sucesso!"]);
    }

    public function toggleStatus(Client $client): JsonResponse
    {
        $newStatus = $client->status === 'active' ? 'blocked' : 'active';
        $client->update(['status' => $newStatus]);

        return response()->json([
            'message' => 'Status alterado para ' . ($newStatus === 'active' ? 'Ativo' : 'Bloqueado'),
            'status'  => $newStatus,
        ]);
    }
}
