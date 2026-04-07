<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Services\ProvisioningService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ServiceController extends Controller
{
    protected function client() { return Auth::guard('client')->user(); }

    public function index(Request $request)
    {
        if ($request->expectsJson()) {
            $services = Service::with(['product:id,name,type', 'server:id,hostname'])
                ->where('client_id', $this->client()->id)
                ->when($request->status, fn($q) => $q->where('status', $request->status))
                ->orderByDesc('created_at')
                ->paginate(12);
            return response()->json($services);
        }
        return view('client.services.index');
    }

    public function show(Request $request, Service $service)
    {
        $this->authorizeService($service);
        $service->load(['product.group', 'server:id,hostname,ip_address,module,nameserver1,nameserver2,nameserver3', 'addonSubscriptions']);
        $service->load(['invoices' => fn($q) => $q->orderByDesc('date_issued')->limit(10)]);

        if ($request->expectsJson()) {
            return response()->json($service);
        }

        $upgradeProducts = collect();
        if ($service->product?->group_id) {
            $upgradeProducts = \App\Models\Product::where('group_id', $service->product->group_id)
                ->where('active', true)
                ->where('id', '!=', $service->product_id)
                ->with('pricing')
                ->get(['id', 'name', 'group_id']);
        }

        return view('client.services.show', compact('service', 'upgradeProducts'));
    }

    public function cpanelLogin(Service $service): JsonResponse
    {
        $this->authorizeService($service);

        if (!$service->isActive()) {
            return response()->json(['message' => 'Serviço não está ativo.'], 422);
        }

        try {
            $url = app(ProvisioningService::class)->getCpanelLoginUrl($service);
            return response()->json(['url' => $url]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function changePassword(Request $request, Service $service): JsonResponse
    {
        $this->authorizeService($service);
        $request->validate(['password' => 'required|string|min:8|confirmed']);

        $success = app(ProvisioningService::class)->changePassword($service, $request->password);

        return response()->json($success
            ? ['message' => 'Senha alterada com sucesso!']
            : ['message' => 'Falha ao alterar senha. Tente novamente.'], $success ? 200 : 422);
    }

    public function cancelRequest(Request $request, Service $service): JsonResponse
    {
        $this->authorizeService($service);

        if (in_array($service->status, ['cancelled', 'terminated'])) {
            return response()->json(['message' => 'Este serviço já está encerrado.'], 422);
        }

        $request->validate([
            'reason'      => 'nullable|string|max:1000',
            'cancel_type' => 'nullable|in:immediate,end_of_period',
        ]);

        $cancelType = $request->input('cancel_type', 'end_of_period');
        $reason     = $request->input('reason', 'Cancelamento solicitado pelo cliente.');
        $dept       = \App\Models\TicketDepartment::first();

        $ticket = \App\Models\Ticket::create([
            'client_id'            => $this->client()->id,
            'service_id'           => $service->id,
            'ticket_department_id' => $dept?->id,
            'subject'              => 'Solicitação de Cancelamento — ' . ($service->domain ?? $service->product?->name),
            'priority'             => 'medium',
            'status'               => 'open',
        ]);

        \App\Models\TicketReply::create([
            'ticket_id' => $ticket->id,
            'client_id' => $this->client()->id,
            'message'   => "**Tipo:** " . ($cancelType === 'immediate' ? 'Imediato' : 'Fim do Período') . "\n\n**Motivo:** {$reason}",
        ]);

        if ($cancelType === 'immediate') {
            $service->update(['status' => 'cancelled']);
        }

        return response()->json([
            'message' => $cancelType === 'immediate'
                ? 'Serviço cancelado. Um ticket foi aberto para acompanhamento.'
                : 'Solicitação de cancelamento registrada. O serviço será encerrado ao final do período atual.',
        ]);
    }

    public function upgradeRequest(Request $request, Service $service): JsonResponse
    {
        $this->authorizeService($service);

        if (!$service->isActive()) {
            return response()->json(['message' => 'Apenas serviços ativos podem ser atualizados.'], 422);
        }

        $request->validate([
            'requested_product_id' => 'nullable|integer|exists:products,id',
            'message'              => 'nullable|string|max:1000',
        ]);

        $target = $request->filled('requested_product_id')
            ? \App\Models\Product::find($request->requested_product_id)?->name
            : 'Não especificado';
        $dept   = \App\Models\TicketDepartment::first();

        $ticket = \App\Models\Ticket::create([
            'client_id'            => $this->client()->id,
            'service_id'           => $service->id,
            'ticket_department_id' => $dept?->id,
            'subject'              => 'Solicitação de Upgrade — ' . ($service->domain ?? $service->product?->name),
            'priority'             => 'medium',
            'status'               => 'open',
        ]);

        \App\Models\TicketReply::create([
            'ticket_id' => $ticket->id,
            'client_id' => $this->client()->id,
            'message'   => "**Plano Atual:** " . ($service->product?->name ?? '—') . "\n**Plano Desejado:** {$target}\n\n" . ($request->message ?? ''),
        ]);

        return response()->json([
            'message' => 'Solicitação de upgrade registrada! Nossa equipe entrará em contato em breve.',
        ]);
    }

    protected function authorizeService(Service $service): void
    {
        if ($service->client_id !== $this->client()->id) {
            abort(403, 'Acesso não autorizado.');
        }
    }
}
