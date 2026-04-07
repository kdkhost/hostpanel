<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Domain;
use App\Models\DomainTld;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DomainController extends Controller
{
    public function index(Request $request)
    {
        if (!$request->expectsJson()) return view('admin.domains.index');
        $domains = Domain::with(['client:id,name,email'])
            ->when($request->search, fn($q) => $q->where('name', 'like', "%{$request->search}%"))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->orderByDesc('created_at')
            ->paginate(20);
        return response()->json($domains);
    }

    public function tlds(): JsonResponse
    {
        return response()->json(DomainTld::orderBy('sort_order')->orderBy('tld')->get());
    }

    public function storeTld(Request $request): JsonResponse
    {
        $request->validate(['tld' => 'required|string|unique:domain_tlds,tld', 'registrar' => 'required|string']);
        $tld = DomainTld::create($request->all());
        return response()->json(['message' => 'TLD adicionado!', 'tld' => $tld], 201);
    }
}
