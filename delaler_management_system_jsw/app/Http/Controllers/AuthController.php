<?php

namespace App\Http\Controllers;

use App\Models\UserRoleCompany;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Throwable;

class AuthController extends Controller
{
    /**
     * Show the login form.
     *
     * @return \Illuminate\View\View
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Handle an authentication attempt.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        try {
            // 1. Validate the input
            $validator = Validator::make($request->all(), [
                'phone_number' => 'required|numeric|digits_between:10,15',
                'password' => 'required|string',
            ]);

            // Return early if validation fails
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first()
                ]);
            }

            // 2. Prepare credentials
            $credentials = [
                'phone' => $request->phone_number,
                'password' => $request->password,
            ];

            // 3. Attempt authentication
            if (Auth::attempt($credentials, $request->boolean('remember'))) {
                $request->session()->regenerate();

                $user = Auth::user();
                $mappings = $user->userRoleCompanies()->with(['company', 'role'])->get();

                // If no mapping is found, log out and return error
                if ($mappings->isEmpty()) {
                    Auth::logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();

                    return response()->json([
                        'success' => false,
                        'message' => 'No role assigned to this user. Please contact your administrator.'
                    ]);
                }

                // If the user only has 1 company/role mapping, auto-select it.
                if ($mappings->count() === 1) {
                    $mapping = $mappings->first();
                    session([
                        'active_map_id' => $mapping->id,
                        'active_company_id' => $mapping->company_id,
                        'active_company_name' => $mapping->company->company_name ?? 'N/A',
                        'active_role_id' => $mapping->role_id,
                        'active_role_name' => $mapping->role->role_name ?? 'N/A',
                    ]);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Login successful. Redirecting...',
                    'redirect_url' => route('dashboard')
                ]);
            }

            // 4. Handle failed authentication
            return response()->json([
                'success' => false,
                'message' => 'The provided credentials do not match our records.'
            ]);
        } catch (\Throwable $e) {
            // 5. Catch any unexpected errors
            Log::error('Login Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing your request. Please try again later.'
            ], 500);
        }
    }
    // public function showForgotPasswordForm() { ... }
    // public function resetPassword(Request $request) { ... }
    /**
     * Handle an authentication logout.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        try {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login');
        } catch (\Throwable $e) {
            Log::error('Logout Error: ' . $e->getMessage());

            return redirect()->route('login');
        }
    }

    /**
     * Set the user's active company and role context in the session.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function setActiveContext(Request $request)
    {
        try {
            $request->validate([
                'mapping_id' => 'required|integer|exists:user_role_companies,id',
            ]);

            $mapping = UserRoleCompany::with(['company', 'role'])
                ->where('id', $request->mapping_id)
                ->where('user_id', Auth::id())
                ->first();

            if (!$mapping) {
                return back()->with('error', 'Invalid role and company selection. You do not have permission for this context.');
            }

            session([
                'active_map_id' => $mapping->id,
                'active_company_id' => $mapping->company_id,
                'active_company_name' => $mapping->company->company_name ?? 'N/A',
                'active_role_id' => $mapping->role_id,
                'active_role_name' => $mapping->role->role_name ?? 'N/A',
            ]);

            return back()->with('success', 'Active role and company updated successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e; // Let Laravel handle validation redirect
        } catch (\Throwable $e) {
            Log::error('setActiveContext Error: ' . $e->getMessage());
            return back()->with('error', 'An error occurred while setting your active context. Please try again.');
        }
    }
}
