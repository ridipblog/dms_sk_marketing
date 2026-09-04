# Implement Sidebar-Based Role Access Control

This plan details the implementation of a dynamic role-based access control system that leverages `config/sidebar.php` as the single source of truth for route permissions, preventing unauthorized users (like the Finance Team) from accessing restricted areas (like Slab Management).

## User Review Required

> [!IMPORTANT]
> The middleware will use `config/sidebar.php` as the central configuration for permissions. 
> - If a route is **not** defined in the sidebar, it will be allowed by default. 
> - If a route shares a prefix with a sidebar route (e.g. `accounts.cash_discount_slabs.list` and `accounts.cash_discount_slabs.index`), it will inherit the sidebar's role restrictions for that prefix.
>
> Please review the design of the Restricted Page and confirm if this prefix-based permission inheritance is acceptable.

## Open Questions

- Are there any routes that should always bypass this permission check besides authentication routes?
- Does the "Restricted Access" page need a specific company logo, or can it use a clean, modern design? 

## Proposed Changes

---

### App\Http\Middleware\CheckSidebarPermissions.php
[NEW] `CheckSidebarPermissions.php`
- Create a new middleware.
- Retrieve the current route name and the active role from the session (`session('active_role_name')`).
- Iterate through `config('sidebar.menu')` to find a matching route prefix.
- If a match is found and the user's role is not in the `roles` array (and it's not `['*']`), redirect the user to the `restricted` route.

---

### bootstrap/app.php
[MODIFY] `bootstrap/app.php`
- Register the new `CheckSidebarPermissions` middleware so it can be used globally or attached to route groups. We'll add it to the web middleware group so it runs on all web routes.

---

### app\Http\Controllers\DashboardController.php
[MODIFY] `DashboardController.php`
- Add a new method `restricted()` that returns the restricted access view.

---

### routes\dashboard\dashboardRoute.php
[MODIFY] `dashboardRoute.php`
- Add the `restricted` route: `Route::get('/restricted', [DashboardController::class, 'restricted'])->name('restricted');`

---

### resources\views\errors\restricted.blade.php
[NEW] `restricted.blade.php`
- Create a beautifully designed "403 Restricted Access" page.
- It will feature a modern, clean UI, an alert icon or illustration, and a button to redirect the user back to the dashboard.

## Verification Plan

### Manual Verification
1. Log in with a user whose active role is "Finance Team".
2. Attempt to manually navigate to `/accounts/cash-discount-slabs`.
3. Verify that the system redirects to the new `/restricted` page.
4. Verify that other authorized routes (e.g. `/dashboard` or `/accounts/order-details`) remain accessible.
5. Log in as an "Admin User" and verify that `/accounts/cash-discount-slabs` remains fully accessible.
