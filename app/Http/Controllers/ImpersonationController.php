<?php

namespace App\Http\Controllers;

use App\Services\ImpersonationService;
use Illuminate\Http\Request;

class ImpersonationController extends Controller
{
    public function stop(Request $request)
    {
        app(ImpersonationService::class)->stopImpersonation();
        return redirect()->route('admin.clients.index')
            ->with('success', 'Impersonação encerrada com sucesso.');
    }
}
