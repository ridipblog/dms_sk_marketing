<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Models\Company;
use App\Models\UserRoleCompany;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Crypt;
use App\Modules\ReuseModule;
use Illuminate\Support\Facades\DB;

class UserRoleController extends Controller
{
    public function index()
    {
        try {
            return view('users.roles.index');
        } catch (\Throwable $e) {
            Log::error('User Roles Index Error: ' . $e->getMessage());
            return view('users.roles.index', [
                'errorMessage' => 'An error occurred while loading the role management page.'
            ]);
        }
    }

    public function list(Request $request)
    {
        try {
            $query = User::query();

            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            }

            $page = $request->input('page', 1);
            $users = $query->latest()->paginate(10, ['*'], 'page', $page);

            $html = view('users.roles.partials.table', compact('users'))->render();

            return response()->json([
                'success' => true,
                'html' => $html
            ]);
        } catch (\Throwable $e) {
            Log::error('User Roles List Fetch Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while loading users.',
                'html' => '<div class="alert alert-danger">Failed to load users. Please try again.</div>'
            ], 500);
        }
    }

    public function mappings($encrypted_id)
    {
        try {
            $id = Crypt::decryptString($encrypted_id);
            $user = User::findOrFail($id);

            // Fetch existing mappings for this user including their parent/reporting user
            $mappings = UserRoleCompany::with(['role', 'company', 'parent'])
                ->where('user_id', $id)
                ->get();

            // Calculate is_editable check for each mapping
            foreach ($mappings as $mapping) {
                $hasInvoices = DB::table('invoices')->where('created_by', $mapping->id)->whereNull('deleted_at')->exists();
                $mapping->is_editable = !$hasInvoices;
            }

            $activePriority = ReuseModule::getActiveRolePriority();

            // Fetch all active companies and roles for the dropdowns
            $companies = Company::where('status', 'active')->get();
            $roles = Role::where('status', 'active')
                ->where('priority', '>', $activePriority)
                ->get();

            // Return the modal HTML
            $html = view('users.roles.partials.manage_modal', compact('user', 'mappings', 'companies', 'roles'))->render();

            return response()->json([
                'success' => true,
                'html' => $html
            ]);
        } catch (\Throwable $e) {
            Log::error('User Role Mappings Fetch Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching user mappings.'
            ], 500);
        }
    }

    public function store(Request $request, $encrypted_id)
    {
        try {
            $id = Crypt::decryptString($encrypted_id);
            $user = User::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'company_id' => 'required|exists:companies,id',
                'role_id' => 'required|exists:roles,id',
                'parent_id' => 'nullable|exists:users,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first()
                ], 200);
            }

            $activePriority = ReuseModule::getActiveRolePriority();
            $assignedRole = Role::find($request->role_id);
            if (!$assignedRole || $assignedRole->priority <= $activePriority) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to assign this role.'
                ], 200);
            }

            // Verify if the role is part of the hierarchy and requires a parent/reporting user
            $roleName = $assignedRole->role_name;
            $needsParent = array_key_exists($roleName, config('hierarchy.relations', []));

            if ($needsParent && !$request->parent_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Reporting To (Parent User) is required for this role.'
                ], 200);
            }

            // Prevent duplicate context
            $exists = UserRoleCompany::where('user_id', $user->id)
                ->where('company_id', $request->company_id)
                ->where('role_id', $request->role_id)
                ->where('parent_id', $request->parent_id ?: null)
                ->exists();

            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'This user is already assigned to this Role and Company with this reporting manager.'
                ], 200);
            }

            try {
                UserRoleCompany::create([
                    'user_id' => $user->id,
                    'company_id' => $request->company_id,
                    'role_id' => $request->role_id,
                    'parent_id' => $request->parent_id ?: null
                ]);
            } catch (\Illuminate\Database\QueryException $e) {
                // Handle duplicate entry constraint gracefully
                if ($e->getCode() == 23000) {
                    return response()->json([
                        'success' => false,
                        'message' => 'This user is already assigned to this Role and Company. To change managers,'
                    ], 200);
                }
                throw $e;
            }

            return response()->json([
                'success' => true,
                'message' => 'Role and Company assigned successfully.'
            ]);
        } catch (\Throwable $e) {
            Log::error('User Role Store Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while assigning the role.'
            ], 500);
        }
    }

    public function destroy($encrypted_mapping_id)
    {
        try {
            $mapping_id = Crypt::decryptString($encrypted_mapping_id);
            $mapping = UserRoleCompany::findOrFail($mapping_id);

            // Double check: if mapping has invoices, reject deletion
            $hasInvoices = DB::table('invoices')->where('created_by', $mapping->id)->whereNull('deleted_at')->exists();
            if ($hasInvoices) {
                return response()->json([
                    'success' => false,
                    'message' => 'This mapping cannot be removed because it has associated invoices.'
                ], 200);
            }

            $mapping->delete();

            return response()->json([
                'success' => true,
                'message' => 'Mapping removed successfully.'
            ]);
        } catch (\Throwable $e) {
            Log::error('User Role Mapping Delete Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while removing the mapping.'
            ], 500);
        }
    }

    public function getParentUsers(Request $request)
    {
        try {
            $roleId = $request->input('role_id');
            $companyId = $request->input('company_id');

            if (!$roleId) {
                return response()->json(['success' => true, 'data' => []]);
            }

            $role = Role::find($roleId);
            if (!$role) {
                return response()->json(['success' => false, 'message' => 'Role not found.'], 404);
            }

            // Determine parent role name based on hierarchy configuration
            $roleName = $role->role_name;
            $parentRoleName = config("hierarchy.relations.{$roleName}");

            if (!$parentRoleName) {
                return response()->json(['success' => true, 'data' => []]);
            }

            $parentRole = Role::where('role_name', $parentRoleName)->first();
            if (!$parentRole) {
                return response()->json(['success' => true, 'data' => []]);
            }

            // Retrieve active users mapped to the parent role
            $parentUsers = User::where('status', 'active')
                ->whereHas('userRoleCompanies', function ($q) use ($parentRole, $companyId) {
                    $q->where('role_id', $parentRole->id);
                    if ($companyId) {
                        $q->where('company_id', $companyId);
                    }
                })
                ->get(['id', 'name']);

            return response()->json([
                'success' => true,
                'data' => $parentUsers
            ]);
        } catch (\Throwable $e) {
            Log::error('Get Parent Users Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while loading hierarchy users.'
            ], 500);
        }
    }

    public function updateMapping(Request $request, $encrypted_mapping_id)
    {
        try {
            $mappingId = Crypt::decryptString($encrypted_mapping_id);
            $mapping = UserRoleCompany::findOrFail($mappingId);

            // Double check: if mapping has invoices, reject edit
            $hasInvoices = DB::table('invoices')->where('created_by', $mapping->id)->whereNull('deleted_at')->exists();
            if ($hasInvoices) {
                return response()->json([
                    'success' => false,
                    'message' => 'This mapping cannot be edited because it has associated invoices.'
                ], 200);
            }

            $validator = Validator::make($request->all(), [
                'company_id' => 'required|exists:companies,id',
                'role_id' => 'required|exists:roles,id',
                'parent_id' => 'nullable|exists:users,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first()
                ], 200);
            }

            $activePriority = ReuseModule::getActiveRolePriority();
            $assignedRole = Role::find($request->role_id);
            if (!$assignedRole || $assignedRole->priority <= $activePriority) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to assign this role.'
                ], 200);
            }

            // Verify if the role is part of the hierarchy and requires a parent/reporting user
            $roleName = $assignedRole->role_name;
            $needsParent = array_key_exists($roleName, config('hierarchy.relations', []));

            if ($needsParent && !$request->parent_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Reporting To (Parent User) is required for this role.'
                ], 200);
            }

            // Prevent duplicate context (excluding current mapping ID)
            $exists = UserRoleCompany::where('user_id', $mapping->user_id)
                ->where('company_id', $request->company_id)
                ->where('role_id', $request->role_id)
                ->where('id', '!=', $mapping->id)
                ->exists();

            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'This user is already assigned to this Role and Company in another mapping.'
                ], 200);
            }

            // Check duplicate unique constraints
            try {
                $mapping->update([
                    'company_id' => $request->company_id,
                    'role_id' => $request->role_id,
                    'parent_id' => $request->parent_id ?: null
                ]);
            } catch (\Illuminate\Database\QueryException $e) {
                if ($e->getCode() == 23000) {
                    return response()->json([
                        'success' => false,
                        'message' => 'This user is already assigned to this Role and Company. Duplicate context is not allowed.'
                    ], 200);
                }
                throw $e;
            }

            return response()->json([
                'success' => true,
                'message' => 'Role and Company mapping updated successfully.'
            ]);
        } catch (\Throwable $e) {
            Log::error('Update Mapping Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating the mapping.'
            ], 500);
        }
    }

    public function getMappingDetails($encrypted_mapping_id)
    {
        try {
            $mappingId = Crypt::decryptString($encrypted_mapping_id);
            $mapping = UserRoleCompany::findOrFail($mappingId);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $encrypted_mapping_id,
                    'company_id' => $mapping->company_id,
                    'role_id' => $mapping->role_id,
                    'parent_id' => $mapping->parent_id
                ]
            ]);
        } catch (\Throwable $e) {
            Log::error('Get Mapping Details Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch mapping details.'
            ], 500);
        }
    }
}
