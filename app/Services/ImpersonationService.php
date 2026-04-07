<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\Client;
use App\Models\ImpersonationLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class ImpersonationService
{
    public function impersonate(Admin $admin, Client $client, ?string $reason = null): void
    {
        if ($client->is_protected) {
            throw new \RuntimeException('Este cliente não pode ser acessado via impersonação.');
        }

        $log = ImpersonationLog::create([
            'admin_id'   => $admin->id,
            'client_id'  => $client->id,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'reason'     => $reason,
            'started_at' => now(),
        ]);

        Session::put('impersonation', [
            'admin_id'      => $admin->id,
            'client_id'     => $client->id,
            'log_id'        => $log->id,
            'started_at'    => now()->timestamp,
        ]);

        Auth::guard('client')->login($client);

        Log::channel('security')->info("Admin #{$admin->id} ({$admin->name}) started impersonating client #{$client->id} ({$client->name})", [
            'ip'     => request()->ip(),
            'reason' => $reason,
        ]);
    }

    public function stopImpersonation(): void
    {
        $data = Session::get('impersonation');

        if (!$data) return;

        $log = ImpersonationLog::find($data['log_id']);
        if ($log) {
            $duration = now()->timestamp - ($data['started_at'] ?? now()->timestamp);
            $log->update([
                'ended_at'         => now(),
                'duration_seconds' => $duration,
            ]);
        }

        Session::forget('impersonation');
        Auth::guard('client')->logout();

        $admin = Admin::find($data['admin_id']);
        if ($admin) {
            Auth::guard('admin')->login($admin);
        }

        Log::channel('security')->info("Impersonation ended for admin #{$data['admin_id']} → client #{$data['client_id']}");
    }

    public function isImpersonating(): bool
    {
        return Session::has('impersonation');
    }

    public function getImpersonationData(): ?array
    {
        return Session::get('impersonation');
    }
}
