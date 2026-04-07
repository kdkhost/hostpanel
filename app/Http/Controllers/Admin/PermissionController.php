<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionController extends Controller
{
    public function index(Request $request)
    {
        if (!$request->expectsJson()) return view('admin.permissions.index');
        $roles       = Role::with('permissions')->get();
        $permissions = Permission::orderBy('name')->get()->groupBy(fn($p) => explode('_', $p->name)[0] ?? 'other');
        return response()->json(compact('roles', 'permissions'));
    }

    public function roles(): JsonResponse
    {
        return response()->json(['roles' => Role::orderBy('name')->get()]);
    }

    public function assign(Request $request): JsonResponse
    {
        $request->validate([
            'admin_id'    => 'required|exists:admins,id',
            'roles'       => 'nullable|array',
            'permissions' => 'nullable|array',
        ]);

        $admin = Admin::findOrFail($request->admin_id);
        if ($request->has('roles'))       $admin->syncRoles($request->roles ?? []);
        if ($request->has('permissions')) $admin->syncPermissions($request->permissions ?? []);

        return response()->json(['message' => 'Permissões atualizadas com sucesso!']);
    }
}
