<?php

namespace App\Http\Controllers;

use App\Services\AdminRolesService;
use App\Services\AdminsService;
use Illuminate\Http\Request;

class AdminPermissionsController extends Controller
{
    public function index()
    {
        $pageTitle = __('message.permission');
        $auth_user = authSession();
        $assets = [];
        $rolesService = app(AdminRolesService::class);
        $doc = $rolesService->getCurrentAdminDoc();
        $roles = $rolesService->getRoles();
        $profile = $rolesService->permissionProfile();
        $admins = app(AdminsService::class)->listAdmins();

        return view('admin_permissions.index', compact('pageTitle', 'auth_user', 'assets', 'doc', 'roles', 'profile', 'admins'));
    }

    public function update(Request $request, string $id)
    {
        $rolesService = app(AdminRolesService::class);
        if (!$rolesService->isSuperAdmin()) {
            abort(403);
        }

        $roles = $request->input('roles', []);
        $isActive = $request->boolean('is_active', false);

        $allowed = ['super_admin', 'admin', 'sub_admin'];
        if (!is_array($roles)) {
            $roles = [];
        }
        foreach ($roles as $role) {
            if (!is_string($role) || !in_array(trim($role), $allowed, true)) {
                return redirect()->back()->withErrors(__('message.permission'));
            }
        }

        $ok = app(AdminsService::class)->updateAdminRoles($id, $roles, $isActive);
        if (!$ok) {
            return redirect()->back()->withErrors(__('message.permission'));
        }

        return redirect()->back()->withSuccess(__('message.updated'));
    }
}
