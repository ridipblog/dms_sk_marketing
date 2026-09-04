<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        try {
            return view('users.index');
        } catch (\Throwable $e) {
            Log::error('User Index Error: ' . $e->getMessage());
            return view('users.index', [
                'errorMessage' => 'An error occurred while loading the user data. Some features may not work correctly.'
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
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('designation', 'like', "%{$search}%");
                });
            }

            if ($request->has('status') && !empty($request->status)) {
                $query->where('status', $request->status);
            }

            $page = $request->input('page', 1);
            $users = $query->latest()->paginate(10, ['*'], 'page', $page);

            $html = view('users.partials.table', compact('users'))->render();

            return response()->json([
                'success' => true,
                'html' => $html
            ]);
        } catch (\Throwable $e) {
            Log::error('User List Fetch Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while loading users.',
                'html' => '<div class="alert alert-danger">Failed to load users. Please try again.</div>'
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'phone' => 'required|string|max:20|unique:users,phone',
                'email' => 'required|string|email|max:255|unique:users,email',
                'designation' => 'nullable|string|max:255',
                'status' => 'required|in:active,inactive,blocked'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first()
                ], 200);
            }

            $data = $validator->validated();
            $data['password'] = Hash::make('12345678'); // Default password

            User::create($data);

            return response()->json([
                'success' => true,
                'message' => 'User created successfully.'
            ]);
        } catch (\Throwable $e) {
            Log::error('User Creation Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while creating the user.'
            ], 500);
        }
    }

    public function findByPhone(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'phone' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first()
                ], 200);
            }

            $user = User::where('phone', $request->phone)->first();

            if ($user) {
                return response()->json([
                    'success' => true,
                    'user' => $user
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ]);
        } catch (\Throwable $e) {
            Log::error('User Find By Phone Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while searching for the user.'
            ], 500);
        }
    }

    public function edit($encrypted_id)
    {
        try {
            $id = Crypt::decryptString($encrypted_id);
            $user = User::findOrFail($id);

            return response()->json([
                'success' => true,
                'user' => $user
            ]);
        } catch (\Throwable $e) {
            Log::error('User Edit Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching the user.'
            ], 500);
        }
    }

    public function show($encrypted_id)
    {
        try {
            $id = Crypt::decryptString($encrypted_id);
            $user = User::findOrFail($id);

            $html = view('users.partials.view_modal', compact('user'))->render();

            return response()->json([
                'success' => true,
                'html' => $html
            ]);
        } catch (\Throwable $e) {
            Log::error('User View Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching the user details.'
            ], 500);
        }
    }

    public function update(Request $request, $encrypted_id)
    {
        try {
            $id = Crypt::decryptString($encrypted_id);
            $user = User::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'phone' => 'required|string|max:20|unique:users,phone,' . $user->id,
                'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
                'designation' => 'nullable|string|max:255',
                'status' => 'required|in:active,inactive,blocked'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first(),
                    'errors' => $validator->errors()
                ], 200);
            }

            $data = $validator->validated();

            $user->update($data);

            return response()->json([
                'success' => true,
                'message' => 'User updated successfully.'
            ]);
        } catch (\Throwable $e) {
            Log::error('User Update Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating the user.'
            ], 500);
        }
    }
}
