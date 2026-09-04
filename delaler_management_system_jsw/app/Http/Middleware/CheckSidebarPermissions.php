<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CheckSidebarPermissions
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $currentRouteName = $request->route() ? $request->route()->getName() : null;
        $activeRole = session('active_role_name');

        if (!$currentRouteName || !$activeRole) {
            return $next($request);
        }

        // Do not check auth, logout, dashboard, or restricted routes
        if (in_array($currentRouteName, ['login', 'login.submit', 'logout', 'set.active.context', 'dashboard', 'dashboard.data', 'restricted'])) {
            return $next($request);
        }

        $hasAccess = true; // Default to true if route is not defined in sidebar

        try {
            $sidebarMenu = config('sidebar.menu', []);
            $activeCompanyId = session('active_company_id');

            $checkAccess = function ($items) use (&$checkAccess, $currentRouteName, $activeRole, $activeCompanyId, &$hasAccess) {
                foreach ($items as $item) {
                    $isMatch = false;

                    // Check if current route matches the main route
                    if (isset($item['route']) && $currentRouteName === $item['route']) {
                        $isMatch = true;
                    }

                    // Check if current route is in api_routes array
                    if (!$isMatch && isset($item['api_routes']) && is_array($item['api_routes'])) {
                        if (in_array($currentRouteName, $item['api_routes'])) {
                            $isMatch = true;
                        }
                    }

                    if ($isMatch) {
                        // Check Role
                        $allowedRoles = $item['roles'] ?? ['*'];
                        if (!in_array('*', $allowedRoles) && !in_array($activeRole, $allowedRoles)) {
                            $hasAccess = false;
                            return; // Stop checking further once we find a restriction violation
                        }

                        // Check Company
                        $allowedCompanies = $item['companies'] ?? ['*'];
                        if (!in_array('*', $allowedCompanies) && !in_array((string)$activeCompanyId, $allowedCompanies)) {
                            $hasAccess = false;
                            return; // Stop checking further once we find a restriction violation
                        }
                    }

                    if (isset($item['submodules'])) {
                        $checkAccess($item['submodules']);
                    }
                }
            };

            $checkAccess($sidebarMenu);
        } catch (\Throwable $e) {
            Log::error('CheckSidebarPermissions Error: ' . $e->getMessage());
            $hasAccess = false; // Fail-closed on system error
        }

        if (!$hasAccess) {
            // Check if it's an AJAX request to return JSON
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized access.'], 403);
            }
            return redirect()->route('restricted');
        }

        return $next($request);
    }
}
