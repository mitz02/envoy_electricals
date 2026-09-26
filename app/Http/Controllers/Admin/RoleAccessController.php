<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Store;
use App\Services\AuditLogger;
use App\Support\RoleAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class RoleAccessController extends Controller
{
    public function index(): Response
    {
        abort_unless(request()->user()->isSuperAdmin(), 403, 'Only the super admin can manage access control.');

        $roles = Role::with('permissions')
            ->orderBy('id')
            ->get()
            ->map(fn (Role $role) => [
                'id' => $role->id,
                'name' => $role->name,
                'slug' => $role->slug,
                'description' => $role->description,
                'is_owner' => $role->slug === 'owner',
                'staff_count' => $role->users()->count(),
                'permission_slugs' => $role->permissions->pluck('slug')->values(),
                'masked_fields' => $role->maskedFields(),
                'allowed_store_ids' => $role->allowedStoreIds() ?? [],
            ]);

        $permissions = Permission::orderBy('module')->orderBy('name')
            ->get(['id', 'slug', 'module', 'description', 'name'])
            ->groupBy('module')
            ->map(fn ($perms) => $perms->map(fn ($p) => [
                'slug' => $p->slug,
                'name' => $p->name,
                'description' => $p->description,
            ])->values());

        $stores = Store::orderBy('name')
            ->get(['id', 'name', 'code', 'is_active'])
            ->map(fn ($s) => [
                'id' => $s->id,
                'name' => $s->name,
                'code' => $s->code,
                'is_active' => $s->is_active,
            ]);

        return Inertia::render('Admin/Settings/Roles', [
            'roles' => $roles,
            'permission_modules' => $permissions,
            'maskable_fields' => RoleAccess::maskableFields(),
            'stores' => $stores,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isSuperAdmin(), 403, 'Only the super admin can manage access control.');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles,name'],
            'slug' => ['required', 'string', 'max:255', 'unique:roles,slug', 'alpha_dash'],
            'description' => ['nullable', 'string'],
            'permissions' => ['array'],
            'permissions.*' => ['string', 'exists:permissions,slug'],
            'masked_fields' => ['array'],
            'masked_fields.*' => ['string', Rule::in(RoleAccess::maskableSlugs())],
            'allowed_store_ids' => ['array'],
            'allowed_store_ids.*' => ['integer', 'exists:stores,id'],
        ]);

        $role = Role::create([
            'name' => $data['name'],
            'slug' => $data['slug'],
            'description' => $data['description'] ?? '',
        ]);

        $permissionIds = Permission::whereIn('slug', $data['permissions'] ?? [])->pluck('id');
        $role->permissions()->sync($permissionIds);

        $role->forceFill([
            'access_config' => [
                'masked_fields' => array_values(array_unique($data['masked_fields'] ?? [])),
                'allowed_store_ids' => array_values(array_unique($data['allowed_store_ids'] ?? [])),
            ],
        ])->save();

        AuditLogger::log('created', 'role', $role->id, "Created role {$role->name} ({$role->slug}).");

        return redirect()->back()->with('success', "Role {$role->name} created successfully.");
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        abort_unless($request->user()->isSuperAdmin(), 403, 'Only the super admin can manage access control.');
        abort_unless($role->slug !== 'owner', 403, 'The super admin role cannot be changed.');

        $data = $request->validate([
            'permissions' => ['array'],
            'permissions.*' => ['string', 'exists:permissions,slug'],
            'masked_fields' => ['array'],
            'masked_fields.*' => ['string', Rule::in(RoleAccess::maskableSlugs())],
            'allowed_store_ids' => ['array'],
            'allowed_store_ids.*' => ['integer', 'exists:stores,id'],
        ]);

        $permissionIds = Permission::whereIn('slug', $data['permissions'] ?? [])->pluck('id');

        $role->permissions()->sync($permissionIds);
        $role->forceFill([
            'access_config' => [
                'masked_fields' => array_values(array_unique($data['masked_fields'] ?? [])),
                'allowed_store_ids' => array_values(array_unique($data['allowed_store_ids'] ?? [])),
            ],
        ])->save();

        AuditLogger::log('updated', 'role', $role->id, "Updated access for role {$role->name} ({$role->slug}).");

        return redirect()->back()->with('success', "Access settings saved for the {$role->name} role.");
    }

    public function destroy(Request $request, Role $role): RedirectResponse
    {
        abort_unless($request->user()->isSuperAdmin(), 403, 'Only the super admin can manage access control.');
        abort_unless($role->slug !== 'owner', 403, 'The super admin role cannot be deleted.');

        // Check if role is assigned to any staff/user
        if ($role->users()->exists()) {
            return back()->with('error', "Cannot delete role \"{$role->name}\" — it is currently assigned to {$role->users()->count()} staff member(s). Please reassign them first.");
        }

        $roleName = $role->name;
        $role->delete();

        AuditLogger::log('deleted', 'role', $role->id, "Deleted role {$roleName} ({$role->slug}).");

        return redirect()->back()->with('success', "Role {$roleName} deleted successfully.");
    }
}
