<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Services\ProvisioningService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function index(Request $request)
    {
        if ($request->expectsJson()) {
            $query = Service::with(['client:id,name,email', 'product:id,name', 'server:id,hostname'])
                ->when($request->search, fn($q) => $q->where(function ($q2) use ($request) {
                    $q2->where('domain', 'like', "%{$request->search}%")
                       ->orWhere('username', 'like', "%{$request->search}%")
                       ->orWhereHas('client', fn($q3) => $q3->where('email', 'like', "%{$request->search}%"));
                }))
                ->when($request->status, fn($q) => $q->where('status', $request->status))
                ->when($request->server_id, fn($q) => $q->where('server_id', $request->server_id))
                ->orderBy($request->sort_by ?? 'created_at', $request->sort_dir ?? 'desc');

            return response()->json($query->paginate($request->per_page ?? 20));
        }
        return view('admin.services.index');
    }

    public function show(Service $service)
    {
        $service->load(['client', 'product', 'server', 'invoiceItems.invoice', 'addonSubscriptions']);
        return view('admin.services.show', compact('service'));
    }

    public function suspend(Request $request, Service $service): JsonResponse
    {
        $request->validate(['reason' => 'nullable|string|max:255']);
        $success = app(ProvisioningService::class)->suspend($service, $request->reason ?? 'Solicitação administrativa');
        return response()->json($success
            ? ['message' => 'Serviço suspenso com sucesso!']
            : ['message' => 'Falha ao suspender serviço.'], $success ? 200 : 422);
    }

    public function reactivate(Service $service): JsonResponse
    {
        $success = app(ProvisioningService::class)->reactivate($service);
        return response()->json($success
            ? ['message' => 'Serviço reativado com sucesso!']
            : ['message' => 'Falha ao reativar serviço.'], $success ? 200 : 422);
    }

    public function terminate(Service $service): JsonResponse
    {
        $success = app(ProvisioningService::class)->terminate($service);
        return response()->json($success
            ? ['message' => 'Serviço encerrado com sucesso!']
            : ['message' => 'Falha ao encerrar serviço.'], $success ? 200 : 422);
    }

    public function reprovision(Service $service): JsonResponse
    {
        dispatch(new \App\Jobs\ProvisionServiceJob($service->id));
        return response()->json(['message' => 'Job de provisionamento disparado!']);
    }

    public function cpanelLogin(Service $service): JsonResponse
    {
        try {
            $url = app(ProvisioningService::class)->getCpanelLoginUrl($service);
            return response()->json(['url' => $url]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function changePassword(Request $request, Service $service): JsonResponse
    {
        $request->validate(['password' => 'required|string|min:8']);
        $success = app(ProvisioningService::class)->changePassword($service, $request->password);
        return response()->json($success
            ? ['message' => 'Senha alterada com sucesso!']
            : ['message' => 'Falha ao alterar senha.'], $success ? 200 : 422);
    }

    public function provision(Service $service): JsonResponse
    {
        if (!in_array($service->status, ['pending', 'failed'])) {
            return response()->json(['message' => 'O serviço não está em estado provisionável.'], 422);
        }
        dispatch(new \App\Jobs\ProvisionServiceJob($service->id));
        return response()->json(['message' => 'Provisionamento iniciado! Aguarde alguns instantes.']);
    }

    public function edit(Service $service)
    {
        $service->load(['client', 'product', 'server']);
        $servers  = \App\Models\Server::where('active', true)->get(['id', 'hostname']);
        $products = \App\Models\Product::where('active', true)->get(['id', 'name']);
        return view('admin.services.edit', compact('service', 'servers', 'products'));
    }

    public function update(Request $request, Service $service): JsonResponse
    {
        $validated = $request->validate([
            'domain'          => 'nullable|string|max:255',
            'username'        => 'nullable|string|max:64',
            'price'           => 'nullable|numeric|min:0',
            'billing_cycle'   => 'nullable|string',
            'next_due_date'   => 'nullable|date',
            'server_id'       => 'nullable|integer|exists:servers,id',
            'admin_notes'     => 'nullable|string|max:2000',
        ]);

        $service->update(array_filter($validated, fn($v) => !is_null($v)));

        return response()->json([
            'message' => 'Serviço atualizado com sucesso!',
            'service' => $service->fresh(),
        ]);
    }

    public function saveNotes(Request $request, Service $service): JsonResponse
    {
        $request->validate(['notes' => 'nullable|string|max:4000']);
        $service->update(['admin_notes' => $request->notes]);
        return response()->json(['message' => 'Notas salvas com sucesso!']);
    }
}
