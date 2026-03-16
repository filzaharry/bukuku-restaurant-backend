<?php

namespace App\Http\Controllers\Config;

use App\Helpers\AuthHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Helpers\FilterHelper;
use App\Helpers\ResponseHelper;
use App\Http\Requests\RegisterRequest;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;


class UserController extends Controller
{
    // List all users
    public function index(Request $request)
    {
        try {
            AuthHelper::requireAuth();
            $query = User::with(['userLevel' => function ($query) {
                $query->select('id', 'name as level_name')
                    ->whereNull('deleted_by_id');
            }])->select('id', 'name', 'email', 'status', 'phone', 'created_at', 'level_id');

            $result = FilterHelper::filterAndPaginate($query, $request);
            return ResponseHelper::jsonResponse(200, 'User list fetched successfully', $result);
        } catch (\Exception $e) {
            return ResponseHelper::jsonResponse(500, 'Failed to fetch user list', $e->getMessage());
        }
    }

    // Show user logged in
    public function navbar()
    {
        try {
            $auth = AuthHelper::requireAuth();
            $user = User::select('users.name', 'user_levels.name as level_name')
                ->join('user_levels', 'users.level_id', '=', 'user_levels.id')
                ->whereNull('users.deleted_by_id')
                ->where('users.id', $auth['id'])
                ->first();
                
            if (!$user) {
                return ResponseHelper::jsonResponse(404, 'User not found', null);
            }
            return ResponseHelper::jsonResponse(200, 'User fetched successfully', $user);
        } catch (\Exception $e) {
            return ResponseHelper::jsonResponse(500, 'Failed to fetch user', $e->getMessage());
        }
    }

    // Show a single user
    public function show($id)
    {
        try {
            AuthHelper::requireAuth();
            $user = User::whereNull('deleted_by_id')->where('id', $id)->first(); // Updated query
            if (!$user) {
                return ResponseHelper::jsonResponse(404, 'User not found', null);
            }
            return ResponseHelper::jsonResponse(200, 'User fetched successfully', $user);
        } catch (\Exception $e) {
            return ResponseHelper::jsonResponse(500, 'Failed to fetch user', $e->getMessage());
        }
    }

    // Create a new user
    public function store(RegisterRequest $request)
    {
        try {
            AuthHelper::requireAuth();
            $validated = $request->validated();

            User::create([
                'name'     => $validated['fullname'],
                'email'    => $validated['email'],
                'phone'    => $validated['phone'],
                'level_id' => $request['level_id'],
                'status'   => 1, // 1 for active, 0 for inactive
                'password' => Hash::make($validated['password']),
                'username' => preg_replace('/[^a-z0-9]/', '', Str::lower($validated['fullname'])),
            ]);
            

            return ResponseHelper::jsonResponse(201, 'User created successfully', null);
        } catch (ValidationException $e) {
            $flattenedErrors = collect($e->errors())->flatten()->all();
            return ResponseHelper::jsonResponse(422, 'Validation error', $flattenedErrors);
        } catch (\Exception $e) {
            return ResponseHelper::jsonResponse(500, 'Failed to create user', $e->getMessage());
        }
    }

    // Update an existing user
    public function update(Request $request, $id)
    {
        try {
            AuthHelper::requireAuth();
            $user = User::whereNull('deleted_by_id')->where('id', $id)->first();
            if (!$user) {
                return ResponseHelper::jsonResponse(404, 'User not found', null);
            }
    
            $data = [
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'status' => $request->status,
                'level_id' => $request->level_id
            ];
    
            if ($request->has('password')) {
                $data['password'] = Hash::make($request->password);
            }
    
            $user->update($data);
    
            return ResponseHelper::jsonResponse(200, 'User updated successfully', $user);
        } catch (\Exception $e) {
            return ResponseHelper::jsonResponse(500, 'Failed to update user', $e->getMessage());
        }
    }

    // Delete a user
    public function destroy($id)
    {
        try {
            AuthHelper::requireAuth();
            $user = User::find($id);

            if (!$user) {
                return ResponseHelper::jsonResponse(404, 'User not found', null);
            }

            $user->update([
                'deleted_by_id' => AuthHelper::getAuthUserId()
            ]);

            return ResponseHelper::jsonResponse(200, 'User deleted successfully', null);
        } catch (\Exception $e) {
            return ResponseHelper::jsonResponse(500, 'Failed to delete user', $e->getMessage());
        }
    }
}
