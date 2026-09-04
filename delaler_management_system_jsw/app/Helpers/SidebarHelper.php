<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Route;

class SidebarHelper
{
    /**
     * Get the filtered sidebar menu based on the user's active session.
     *
     * @return array
     */
    public static function getMenu()
    {
        $menu = config('sidebar.menu', []);
        $activeRole = session('active_role_name');
        $activeCompanyId = session('active_company_id');

        return self::filterMenu($menu, $activeRole, $activeCompanyId);
    }

    /**
     * Recursively filter the menu items.
     *
     * @param array $items
     * @param string|null $role
     * @param int|null $companyId
     * @return array
     */
    private static function filterMenu(array $items, $role, $companyId)
    {
        $filtered = [];

        foreach ($items as $item) {
            // Check if user has permission for this item
            if (!self::hasPermission($item, $role, $companyId)) {
                continue;
            }

            // If the item has submodules, recursively filter them
            if (isset($item['submodules']) && is_array($item['submodules'])) {
                $item['submodules'] = self::filterMenu($item['submodules'], $role, $companyId);

                // If it had submodules but none are accessible, skip the parent item entirely
                if (empty($item['submodules'])) {
                    continue;
                }
            }

            $filtered[] = $item;
        }

        return $filtered;
    }

    /**
     * Check if the active role and company pass the item's requirements.
     *
     * @param array $item
     * @param string|null $role
     * @param int|null $companyId
     * @return bool
     */
    private static function hasPermission(array $item, $role, $companyId)
    {
        // 1. Check Roles
        $roles = $item['roles'] ?? ['*'];
        if (!in_array('*', $roles) && !in_array($role, $roles)) {
            return false;
        }

        // 2. Check Companies
        $companies = $item['companies'] ?? ['*'];
        if (!in_array('*', $companies) && !in_array($companyId, $companies)) {
            return false;
        }

        return true;
    }

    /**
     * Helper to determine if a route is currently active.
     */
    public static function isActiveRoute($sub)
    {
        if (!$sub) {
            return false;
        }

        // Suppress errors if route doesn't exist yet while developing
        try {
            $currentRoute = Route::currentRouteName();
            if (Route::currentRouteName() === $sub['route'] || in_array($currentRoute, $sub['view_routes'])) {
                return true;
            }
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }
}
