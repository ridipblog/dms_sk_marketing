# Role & Company Selection Modal Implementation

We will pause the sidebar configuration for now and focus on establishing a strong foundation for user sessions. When a user logs in, they must select a specific Company and Role context if they belong to multiple. This context will be saved in their session and dictate all their permissions moving forward.

## Proposed Changes

### 1. Auto-selection vs Manual Selection (Login Hook)
- We will modify the `login` method in `AuthController.php`.
- After successful authentication, we will query the `user_role_companies` table for that user.
- **If they have exactly 1 mapping**: We automatically save their `company_id` and `role_id` into their session and let them proceed seamlessly.
- **If they have multiple mappings**: We do not set the session variables, which will trigger the selection modal on the next page.

### 2. The Selection Modal Partial
- We will create a new file: `resources/views/partials/role_company_modal.blade.php`.
- This modal will contain a clean, modern form (using Bootstrap 5) listing all their available Company + Role combinations.
- The modal will use `data-bs-backdrop="static" data-bs-keyboard="false"` so it **cannot be closed or bypassed** by clicking outside or pressing Escape.

### 3. Layout Integration & "Switch" Feature
- In `resources/views/layouts/app.blade.php`, we will include the new partial.
- We will add a small script at the bottom: If the user is logged in but `session('active_company_id')` is missing, we use Javascript to automatically force the modal open on page load.
- We will also add a "Switch Role/Company" button in the top navigation dropdown so users can voluntarily open the modal later to switch their active context.

### 4. Route and Controller Logic to Save Selection
- We will create a new route: `POST /set-active-context`.
- We will create a new controller (e.g., `ContextController` or add a method in `AuthController`) to handle this form submission.
- It will validate that the user actually belongs to the submitted company/role combination, save the `active_company_id` and `active_role_id` to the session, and refresh the page.

## Verification Plan
### Manual Verification
1. Log in with a user who has only **one** company and role mapping -> Verify they go straight to the dashboard with no modal.
2. Log in with a user who has **multiple** mappings -> Verify they are immediately locked behind the modal.
3. Attempt to bypass the modal -> Verify it cannot be closed.
4. Submit the modal form -> Verify the session variables are set and the modal disappears.
5. Click "Switch Role" in the navbar -> Verify the modal re-opens manually.
