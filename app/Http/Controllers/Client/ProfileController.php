<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Services\ViaCepService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    protected function client() { return Auth::guard('client')->user(); }

    public function show()
    {
        return view('client.profile.show', ['client' => $this->client()]);
    }

    public function update(Request $request): JsonResponse
    {
        $client = $this->client();

        $request->validate([
            'name'           => 'required|string|max:100',
            'email'          => 'required|email|unique:clients,email,' . $client->id,
            'document_number'=> 'nullable|string',
            'phone'          => 'nullable|string|max:20',
            'mobile'         => 'nullable|string|max:20',
            'whatsapp'       => 'nullable|string|max:20',
        ]);

        $client->update($request->only([
            'name', 'email', 'document_number', 'phone', 'mobile', 'whatsapp',
            'company_name', 'birth_date', 'address', 'address_number',
            'address_complement', 'neighborhood', 'city', 'state', 'postcode',
            'country', 'marketing_consent',
        ]));

        return response()->json(['message' => 'Perfil atualizado com sucesso!', 'client' => $client->fresh()]);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => 'required|string',
            'password'         => 'required|string|min:8|confirmed',
        ]);

        $client = $this->client();

        if (!Hash::check($request->current_password, $client->password)) {
            return response()->json(['message' => 'Senha atual incorreta.'], 422);
        }

        $client->update(['password' => Hash::make($request->password)]);

        return response()->json(['message' => 'Senha alterada com sucesso!']);
    }

    public function lookupCep(Request $request): JsonResponse
    {
        $request->validate(['cep' => 'required|string']);
        $result = app(ViaCepService::class)->lookup($request->cep);

        if (!$result) {
            return response()->json(['message' => 'CEP não encontrado ou inválido.'], 404);
        }

        return response()->json($result);
    }

    public function notifications(Request $request): JsonResponse
    {
        $notifications = $this->client()->notifications()
            ->when($request->unread_only, fn($q) => $q->where('read', false))
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json($notifications);
    }

    public function markNotificationRead(Request $request, int $notificationId): JsonResponse
    {
        $notification = $this->client()->notifications()->findOrFail($notificationId);
        $notification->markAsRead();
        return response()->json(['message' => 'Notificação marcada como lida.']);
    }

    public function markAllNotificationsRead(): JsonResponse
    {
        $this->client()->notifications()->where('read', false)->update(['read' => true, 'read_at' => now()]);
        return response()->json(['message' => 'Todas as notificações foram marcadas como lidas.']);
    }

    public function loginHistory(): JsonResponse
    {
        $logs = $this->client()->loginLogs()->orderByDesc('created_at')->limit(20)->get();
        return response()->json($logs);
    }
}
