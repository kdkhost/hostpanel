<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\LoginLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

class AdminAuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::guard('admin')->check()) {
            return redirect()->route('admin.dashboard');
        }
        return view('admin.auth.login');
    }

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $throttleKey = 'admin_login:' . $request->ip();
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return response()->json(['message' => "Muitas tentativas. Tente novamente em {$seconds}s."], 429);
        }

        $admin = Admin::where('email', $request->email)->first();

        if (!$admin || !Hash::check($request->password, $admin->password)) {
            RateLimiter::hit($throttleKey, 60);
            LoginLog::create([
                'authenticatable_type' => Admin::class,
                'authenticatable_id'   => $admin?->id ?? 0,
                'ip_address'           => $request->ip(),
                'user_agent'           => $request->userAgent(),
                'success'              => false,
                'fail_reason'          => 'Credenciais inválidas',
            ]);
            return response()->json(['message' => 'Credenciais inválidas.'], 422);
        }

        if ($admin->status !== 'active') {
            return response()->json(['message' => 'Conta administrativa inativa ou bloqueada.'], 403);
        }

        if ($admin->two_factor_enabled && $admin->two_factor_confirmed_at) {
            session(['2fa_pending_admin_id' => $admin->id]);
            return response()->json(['two_factor_required' => true]);
        }

        RateLimiter::clear($throttleKey);
        Auth::guard('admin')->login($admin, $request->boolean('remember'));
        $admin->update(['last_login_at' => now(), 'last_login_ip' => $request->ip()]);

        LoginLog::create([
            'authenticatable_type' => Admin::class,
            'authenticatable_id'   => $admin->id,
            'ip_address'           => $request->ip(),
            'user_agent'           => $request->userAgent(),
            'success'              => true,
        ]);

        return response()->json(['redirect' => route('admin.dashboard')]);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('admin.login');
    }
}
