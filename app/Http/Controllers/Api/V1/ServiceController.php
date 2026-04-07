<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Services\ProvisioningService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $services = Service::with(['client:id,name,email', 'product:id,name'])
            ->when($request->client_id, fn($q) => $q->where('client_id', $request->client_id))
            ->when($request->status,    fn($q) => $q->where('status', $request->status))
            ->orderByDesc('created_at')
            ->paginate($request->per_page ?? 20);
        return response()->json($services);
    }

    public function show(Service $service): JsonResponse
    {
        return response()->json($service->load(['client:id,name,email', 'product', 'server:id,hostname']));
    }

    public function suspend(Request $request, Service $service): JsonResponse
    {
        app(ProvisioningService::class)->suspend($service, $request->reason ?? 'API request');
        return response()->json(['message' => 'Serviço suspenso.', 'service' => $service->fresh()]);
    }

    public function reactivate(Service $service): JsonResponse
    {
        app(ProvisioningService::class)->reactivate($service);
        return response()->json(['message' => 'Serviço reativado.', 'service' => $service->fresh()]);
    }

    public function terminate(Service $service): JsonResponse
    {
        app(ProvisioningService::class)->terminate($service);
        return response()->json(['message' => 'Serviço encerrado.', 'service' => $service->fresh()]);
    }
}
