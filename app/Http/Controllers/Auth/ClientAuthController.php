<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\LoginLog;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ClientAuthController extends Controller
{
    public function showLogin()
    {
        return view('client.auth.login');
    }

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $throttleKey = 'login:' . $request->ip();
        if (RateLimiter::tooManyAttempts($throttleKey, config('hostpanel.security.rate_limit_login', 5))) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return response()->json(['message' => "Muitas tentativas. Tente novamente em {$seconds}s."], 429);
        }

        $client = Client::where('email', $request->email)->first();

        if (!$client || !Hash::check($request->password, $client->password)) {
            RateLimiter::hit($throttleKey, 60);
            LoginLog::create([
                'authenticatable_type' => Client::class,
                'authenticatable_id'   => $client?->id ?? 0,
                'ip_address'           => $request->ip(),
                'user_agent'           => $request->userAgent(),
                'success'              => false,
                'fail_reason'          => 'Credenciais inválidas',
            ]);
            return response()->json(['message' => 'Credenciais inválidas.'], 422);
        }

        if ($client->status === 'blocked') {
            return response()->json(['message' => 'Conta bloqueada. Entre em contato com o suporte.'], 403);
        }

        if ($client->status === 'inactive') {
            return response()->json(['message' => 'Conta inativa.'], 403);
        }

        if ($client->two_factor_enabled && $client->two_factor_confirmed_at) {
            session(['2fa_pending_client_id' => $client->id]);
            return response()->json(['two_factor_required' => true]);
        }

        RateLimiter::clear($throttleKey);
        Auth::guard('client')->login($client, $request->boolean('remember'));

        $client->update(['last_login_at' => now(), 'last_login_ip' => $request->ip()]);

        LoginLog::create([
            'authenticatable_type' => Client::class,
            'authenticatable_id'   => $client->id,
            'ip_address'           => $request->ip(),
            'user_agent'           => $request->userAgent(),
            'success'              => true,
        ]);

        return response()->json(['redirect' => route('client.dashboard')]);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('client')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('client.login');
    }

    public function showRegister()
    {
        return view('client.auth.register');
    }

    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'name'             => 'required|string|max:100',
            'email'            => 'required|email|unique:clients,email',
            'password'         => 'required|string|min:8|confirmed',
            'document_type'    => 'required|in:cpf,cnpj',
            'document_number'  => 'required|string',
            'phone'            => 'nullable|string|max:20',
            'terms_accepted'   => 'required|accepted',
        ]);

        $client = Client::create([
            'name'            => $request->name,
            'email'           => $request->email,
            'password'        => Hash::make($request->password),
            'document_type'   => $request->document_type,
            'document_number' => preg_replace('/\D/', '', $request->document_number),
            'phone'           => $request->phone,
            'mobile'          => $request->mobile,
            'whatsapp'        => $request->whatsapp ?? $request->mobile,
            'status'          => 'active',
            'terms_accepted'  => true,
            'terms_accepted_at' => now(),
        ]);

        Auth::guard('client')->login($client);
        app(NotificationService::class)->send($client, 'welcome', ['name' => $client->name]);

        return response()->json(['redirect' => route('client.dashboard')]);
    }

    public function showForgotPassword()
    {
        return view('client.auth.forgot-password');
    }

    public function sendResetLink(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email']);

        $client = Client::where('email', $request->email)->first();

        if ($client) {
            $token = Str::random(64);
            \DB::table('password_reset_tokens')->upsert([
                'email'      => $client->email,
                'token'      => Hash::make($token),
                'created_at' => now(),
            ], ['email']);

            app(NotificationService::class)->sendEmail($client, 'password_reset', [
                'name'  => $client->name,
                'link'  => url("/cliente/redefinir-senha?token={$token}&email=" . urlencode($client->email)),
            ]);
        }

        return response()->json(['message' => 'Se o e-mail estiver cadastrado, você receberá as instruções em breve.']);
    }

    public function showResetPassword(Request $request)
    {
        return view('client.auth.reset-password', ['token' => $request->token, 'email' => $request->email]);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'token'    => 'required',
            'email'    => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        $record = \DB::table('password_reset_tokens')->where('email', $request->email)->first();

        if (!$record || !Hash::check($request->token, $record->token)) {
            return response()->json(['message' => 'Token inválido ou expirado.'], 422);
        }

        $client = Client::where('email', $request->email)->firstOrFail();
        $client->update(['password' => Hash::make($request->password)]);

        \DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return response()->json(['message' => 'Senha redefinida com sucesso!', 'redirect' => route('client.login')]);
    }
}
