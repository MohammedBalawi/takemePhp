<?php

namespace App\Http\Middleware;

use App\Services\AdminRolesService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminPermissions
{
    public function handle(Request $request, Closure $next): Response
    {
        if ((bool) env('PUBLIC_ADMIN_PANEL', false)) {
            return $next($request);
        }

        $service = app(AdminRolesService::class);
        if ($service->isSuperAdmin()) {
            return $next($request);
        }

        if ($service->isSubAdmin()) {
            $routeName = optional($request->route())->getName();
            $allowed = $service->permissionProfile()['allowedRoutes'] ?? [];
            if (in_array($routeName, $allowed, true)) {
                return $next($request);
            }
            if ($request->is('home')) {
                return $next($request);
            }
            abort(403);
        }

        abort(403);
    }
}
