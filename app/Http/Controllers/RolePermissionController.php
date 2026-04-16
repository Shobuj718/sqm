<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Validation\Rule;

class RolePermissionController extends Controller
{
    /**
     * Display roles management page
     */
    public function roles(): View
    {
        $roles = Role::with('permissions')->get();
        return view('admin.roles-permissions.roles', compact('roles'));
    }

    /**
     * Display permissions management page
     */
    public function permissions(): View
    {
        $permissions = Permission::with('roles')->get();
        return view('admin.roles-permissions.permissions', compact('permissions'));
    }

    /**
     * Display user roles and permissions management page
     */
    public function userRolesPermissions(User $user): View
    {
        return view('admin.roles-permissions.user-detail', compact('user'));
    }

    /**
     * Get all roles with their permissions
     */
    public function getRoles(Request $request): JsonResponse|View
    {
        $roles = Role::with('permissions')->get();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => $roles
            ]);
        }

        return view('admin.roles-permissions.roles', compact('roles'));
    }

    /**
     * Get all permissions
     */
    public function getPermissions(Request $request): JsonResponse|View
    {
        $permissions = Permission::all();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => $permissions
            ]);
        }

        return view('admin.roles-permissions.permissions', compact('permissions'));
    }

    /**
     * Get user with their roles and permissions
     */
    public function getUserRolesPermissions(Request $request, User $user): JsonResponse|View
    {
        $user->load(['roles.permissions', 'permissions']);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'user' => $user,
                    'roles' => $user->roles,
                    'direct_permissions' => $user->permissions,
                    'all_permissions' => $user->roles->flatMap->permissions->merge($user->permissions)->unique('id')
                ]
            ]);
        }

        $allRoles = Role::all();
        $allPermissions = Permission::all();

        return view('admin.roles-permissions.user-detail', compact('user', 'allRoles', 'allPermissions'));
    }

    /**
     * Assign role to user
     */
    public function assignRoleToUser(Request $request, User $user): JsonResponse|RedirectResponse
    {
        $request->validate([
            'role' => ['required', 'string', Rule::exists('roles', 'name')]
        ]);

        $role = Role::where('name', $request->role)->first();

        if ($user->roles()->where('role_id', $role->id)->exists()) {
            $message = 'User already has this role';

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $message
                ], 400);
            }

            return back()->with('error', $message);
        }

        $user->assignRole($role);
        $message = "Role '{$role->name}' assigned to {$user->name}";

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $role
            ]);
        }

        return back()->with('success', $message);
    }

    /**
     * Remove role from user
     */
    public function removeRoleFromUser(Request $request, User $user): JsonResponse|RedirectResponse
    {
        $request->validate([
            'role' => ['required', 'string', Rule::exists('roles', 'name')]
        ]);

        $role = Role::where('name', $request->role)->first();

        if (!$user->roles()->where('role_id', $role->id)->exists()) {
            $message = 'User does not have this role';

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $message
                ], 400);
            }

            return back()->with('error', $message);
        }

        $user->removeRole($role);
        $message = "Role '{$role->name}' removed from {$user->name}";

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $role
            ]);
        }

        return back()->with('success', $message);
    }

    /**
     * Grant permission directly to user
     */
    public function assignPermissionToUser(Request $request, User $user): JsonResponse|RedirectResponse
    {
        $request->validate([
            'permission' => ['required', 'string', Rule::exists('permissions', 'name')]
        ]);

        $permission = Permission::where('name', $request->permission)->first();

        if ($user->permissions()->where('permission_id', $permission->id)->exists()) {
            $message = 'User already has this permission';

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $message
                ], 400);
            }

            return back()->with('error', $message);
        }

        $user->grantPermission($permission);
        $message = "Permission '{$permission->name}' granted to {$user->name}";

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $permission
            ]);
        }

        return back()->with('success', $message);
    }

    /**
     * Revoke permission from user
     */
    public function removePermissionFromUser(Request $request, User $user): JsonResponse|RedirectResponse
    {
        $request->validate([
            'permission' => ['required', 'string', Rule::exists('permissions', 'name')]
        ]);

        $permission = Permission::where('name', $request->permission)->first();

        if (!$user->permissions()->where('permission_id', $permission->id)->exists()) {
            $message = 'User does not have this permission';

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $message
                ], 400);
            }

            return back()->with('error', $message);
        }

        $user->revokePermission($permission);
        $message = "Permission '{$permission->name}' revoked from {$user->name}";

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $permission
            ]);
        }

        return back()->with('success', $message);
    }

    /**
     * Create a new role
     */
    public function createRole(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'unique:roles,name'],
            'description' => ['nullable', 'string']
        ]);

        $role = Role::create($request->only(['name', 'description']));
        $message = "Role '{$role->name}' created";

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $role
            ], 201);
        }

        return back()->with('success', $message);
    }

    /**
     * Create a new permission
     */
    public function createPermission(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'unique:permissions,name'],
            'description' => ['nullable', 'string']
        ]);

        $permission = Permission::create($request->only(['name', 'description']));
        $message = "Permission '{$permission->name}' created";

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $permission
            ], 201);
        }

        return back()->with('success', $message);
    }

    /**
     * Assign permission to role
     */
    public function assignPermissionToRole(Request $request, Role $role): JsonResponse|RedirectResponse
    {
        $request->validate([
            'permission' => ['required', 'string', Rule::exists('permissions', 'name')]
        ]);

        $permission = Permission::where('name', $request->permission)->first();

        if ($role->permissions()->where('permission_id', $permission->id)->exists()) {
            $message = 'Role already has this permission';

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $message
                ], 400);
            }

            return back()->with('error', $message);
        }

        $role->grantPermission($permission);
        $message = "Permission '{$permission->name}' assigned to role '{$role->name}'";

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $permission
            ]);
        }

        return back()->with('success', $message);
    }

    /**
     * Remove permission from role
     */
    public function removePermissionFromRole(Request $request, Role $role): JsonResponse|RedirectResponse
    {
        $request->validate([
            'permission' => ['required', 'string', Rule::exists('permissions', 'name')]
        ]);

        $permission = Permission::where('name', $request->permission)->first();

        if (!$role->permissions()->where('permission_id', $permission->id)->exists()) {
            $message = 'Role does not have this permission';

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $message
                ], 400);
            }

            return back()->with('error', $message);
        }

        $role->revokePermission($permission);
        $message = "Permission '{$permission->name}' removed from role '{$role->name}'";

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $permission
            ]);
        }

        return back()->with('success', $message);
    }
}
