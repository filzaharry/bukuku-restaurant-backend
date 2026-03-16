<?php

namespace App\Http\Controllers\Config;

use App\Helpers\AuthHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\UserLevel;
use App\Helpers\FilterHelper;
use App\Helpers\ResponseHelper;
use App\Models\UserAccess;
use App\Models\UserMenus;
use App\Models\UserPermission;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UserLevelController extends Controller
{
    // List all user levels
    public function index(Request $request)
    {
        try {
            AuthHelper::requireAuth();
            $query = UserLevel::select('id', 'name', 'status', 'created_at'); // No join needed

            $result = FilterHelper::filterAndPaginate($query, $request);
            return ResponseHelper::jsonResponse(200, 'User level list fetched successfully', $result);
        } catch (\Exception $e) {
            return ResponseHelper::jsonResponse(500, 'Failed to fetch user level list', $e->getMessage());
        }
    }

    // Show a single user level
    public function show($id)
    {
        try {
            AuthHelper::requireAuth();
            $userLevel = UserLevel::whereNull('deleted_by_id')->where('id', $id)->first(); // Updated query
            if (!$userLevel) {
                return ResponseHelper::jsonResponse(404, 'User level not found', null);
            }
            return ResponseHelper::jsonResponse(200, 'User level fetched successfully', $userLevel);
        } catch (\Exception $e) {
            return ResponseHelper::jsonResponse(500, 'Failed to fetch user level', $e->getMessage());
        }
    }

    // Create a new user level
    public function store(Request $request)
    {
        try {
            AuthHelper::requireAuth();
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:user_levels,name',
            ]);
    
            DB::beginTransaction();
            try {
                // Create user level
                $userLevel = UserLevel::create([
                    'name' => $validated['name'],
                    'status' => 1,
                    'created_by_id' => AuthHelper::getAuthUserId()
                ]);
    
                // Get all menus
                $menus = UserMenus::whereNull('deleted_by_id')->get();
    
                // Create permissions and access for each menu
                foreach ($menus as $menu) {
                    // Get access types from menu
                    $accessTypes = $menu->access ? explode(';', $menu->access) : ['access'];
    
                    foreach ($accessTypes as $type) {
    
                        // Create access for each permission
                        UserAccess::create([
                            'level_id' => $userLevel->id,
                            'menu_id' => $menu->id,
                            'permission' => $type,
                            'status' => 1,
                            'created_by_id' => AuthHelper::getAuthUserId()
                        ]);
                    }
                }
    
                DB::commit();
                return ResponseHelper::jsonResponse(201, 'User level created successfully', $userLevel);
            } catch (\Exception $e) {
                DB::rollback();
                throw $e;
            }
        } catch (ValidationException $e) {
            $flattenedErrors = collect($e->errors())->flatten()->all();
            return ResponseHelper::jsonResponse(422, 'Validation error', $flattenedErrors);
        } catch (\Exception $e) {
            return ResponseHelper::jsonResponse(500, 'Failed to create user level', $e->getMessage());
        }
    }

    // Update an existing user level
    public function update(Request $request, $id)
    {
        try {
            AuthHelper::requireAuth();
            $userLevel = UserLevel::whereNull('deleted_by_id')->where('id', $id)->first(); // Updated query
            if (!$userLevel) {
                return ResponseHelper::jsonResponse(404, 'User level not found', null);
            }

            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255',
            ]);

            $userLevel->update($validated);

            return ResponseHelper::jsonResponse(200, 'User level updated successfully', $userLevel);
        } catch (ValidationException $e) {
            $flattenedErrors = collect($e->errors())->flatten()->all();
            return ResponseHelper::jsonResponse(422, 'Validation error', $flattenedErrors);
        } catch (\Exception $e) {
            return ResponseHelper::jsonResponse(500, 'Failed to update user level', $e->getMessage());
        }
    }

    // Delete a user level
    public function destroy($id)
    {
        try {
            AuthHelper::requireAuth();
            $userLevel = UserLevel::whereNull('deleted_by_id')->where('id', $id)->first(); // Updated query

            if (!$userLevel) {
                return ResponseHelper::jsonResponse(404, 'User level not found', null);
            }

            $userLevel->update([
                'deleted_by_id' => AuthHelper::getAuthUserId()
            ]);

            return ResponseHelper::jsonResponse(200, 'User level deleted successfully', null);
        } catch (\Exception $e) {
            return ResponseHelper::jsonResponse(500, 'Failed to delete user level', $e->getMessage());
        }
    }

    // Access a user level
    public function showAccess($level_id)
    {
        try {
            AuthHelper::requireAuth();
            
            // Cek apakah level exists
            $userLevel = UserLevel::whereNull('deleted_by_id')->where('id', $level_id)->first();
            if (!$userLevel) {
                return ResponseHelper::jsonResponse(404, 'User level not found', null);
            }
    
            // Ambil semua menu dan access yang tersedia
            $menuAccess = UserMenus::select(
                'user_menus.id as menu_id',
                'user_menus.name as menu_name',
                'user_menus.url',
                'user_menus.permission',
                DB::raw('COALESCE(user_access.status, 0) as status')
            )
            ->whereNull('user_menus.deleted_by_id')
            ->leftJoin('user_access', function($join) use ($level_id) {
                $join->on('user_menus.id', '=', 'user_access.menu_id')
                    ->where('user_access.level_id', '=', $level_id)
                    ->whereNull('user_access.deleted_by_id');
            })
            ->orderBy('user_menus.name')
            ->get();
    
            // Format response dengan memecah permission dari menu
            $formattedAccess = $menuAccess->map(function($menu) use ($level_id) {
                $permissions = explode(';', $menu->permission ?? 'access');
                
                return [
                    'menu_id' => $menu->menu_id,
                    'menu_name' => $menu->menu_name,
                    'permissions' => collect($permissions)->map(function($permission) use ($menu, $level_id) {
                        return [
                            'type' => $permission,
                            'status' => (bool)$menu->status
                        ];
                    })->values()
                ];
            });
    
            return ResponseHelper::jsonResponse(200, 'Access list fetched successfully', $formattedAccess);
        } catch (\Exception $e) {
            return ResponseHelper::jsonResponse(500, 'Failed to fetch access list', $e->getMessage());
        }
    }

    public function updateAccess($id, Request $request)
    {
        try {
            AuthHelper::requireAuth();
            
            // Validasi request
            $validated = $request->validate([
                'menus' => 'required|array',
                'menus.*.menu_id' => 'required|integer',
                'menus.*.permissions' => 'required|array',
                'menus.*.permissions.*.type' => 'required|string',
                'menus.*.permissions.*.status' => 'required|boolean'
            ]);
    
            // Cek apakah level exists
            $userLevel = UserLevel::whereNull('deleted_by_id')->where('id', $id)->first();
            if (!$userLevel) {
                return ResponseHelper::jsonResponse(404, 'User level not found', null);
            }
    
            DB::beginTransaction();
            try {
                foreach ($validated['menus'] as $menuData) {
                    // Cek apakah menu exists
                    $menu = UserMenus::whereNull('deleted_by_id')
                        ->where('id', $menuData['menu_id'])
                        ->first();
    
                    if (!$menu) {
                        continue; // Skip jika menu tidak ditemukan
                    }
    
                    // Update status untuk setiap permission
                    foreach ($menuData['permissions'] as $permission) {
                        $access = UserAccess::where('menu_id', $menuData['menu_id'])
                            ->where('level_id', $id)
                            ->where('permission', $permission['type'])
                            ->whereNull('deleted_by_id')
                            ->first();
    
                        if ($access) {
                            $access->update([
                                'status' => $permission['status'],
                                'updated_by_id' => AuthHelper::getAuthUserId()
                            ]);
                        }
                    }
                }
    
                DB::commit();
                return ResponseHelper::jsonResponse(200, 'Access status updated successfully', null);
            } catch (\Exception $e) {
                DB::rollback();
                throw $e;
            }
        } catch (ValidationException $e) {
            $flattenedErrors = collect($e->errors())->flatten()->all();
            return ResponseHelper::jsonResponse(422, 'Validation error', $flattenedErrors);
        } catch (\Exception $e) {
            return ResponseHelper::jsonResponse(500, 'Failed to update access status', $e->getMessage());
        }
    }
}
