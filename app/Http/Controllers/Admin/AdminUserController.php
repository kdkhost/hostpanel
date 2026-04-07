<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AdminUserController extends Controller
{
    public function index(Request $request)
    {
        if ($request->expectsJson()) {
            $admins = Admin::with('roles')->withCount(['tickets as assigned_tickets'])->get();
            return response()->json($admins);
        }
        return view('admin.admins.index');
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name'     => 'required|string|max:100',
            'email'    => 'required|email|unique:admins,email',
            'password' => 'required|string|min:8',
            'role'     => 'required|string|exists:roles,name',
        ]);

        $admin = Admin::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'status'   => 'active',
        ]);

        $admin->assignRole($request->role);

        return response()->json(['message' => 'Administrador criado!', 'admin' => $admin->load('roles')], 201);
    }

    public function update(Request $request, Admin $admin): JsonResponse
    {
        $request->validate([
            'name'  => 'required|string|max:100',
            'email' => 'required|email|unique:admins,email,' . $admin->id,
        ]);

        $data = $request->only(['name', 'email', 'status']);
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }
        $admin->update($data);

        if ($request->filled('role')) {
            $admin->syncRoles([$request->role]);
        }

        return response()->json(['message' => 'Administrador atualizado!', 'admin' => $admin->fresh()->load('roles')]);
    }

    public function destroy(Admin $admin): JsonResponse
    {
        if (auth('admin')->id() === $admin->id) {
            return response()->json(['message' => 'Você não pode excluir sua própria conta.'], 422);
        }
        $admin->delete();
        return response()->json(['message' => 'Administrador removido!']);
    }
}
