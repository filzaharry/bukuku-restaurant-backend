<?php

namespace App\Http\Controllers\Config;

use App\Helpers\AuthHelper;
use App\Helpers\FilterHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Menu;
use App\Helpers\ResponseHelper;
use App\Models\UserMenus;
use Illuminate\Validation\ValidationException;

class MenuController extends Controller
{
    // List all menus
    public function index()
    {
        try {
            AuthHelper::requireAuth();
            $menus = UserMenus::whereNull('deleted_by_id')
                ->with(['icon' => function ($query) {
                    $query->select('id', 'name');
                }])->get();

            $groupedMenus = [];
            foreach ($menus as $menu) {
                $menuData = $menu->only(['id', 'name', 'level', 'url', 'status']);
                // $menuData['icon_name'] = $menu->icon ? $menu->icon->name : null; 

                if ($menu->is_parent === 'yes') {
                    $groupedMenus[$menu->id] = $menuData;
                    $groupedMenus[$menu->id]['child'] = [];
                } elseif ($menu->is_parent === 'no' && isset($groupedMenus[$menu->master])) {
                    $groupedMenus[$menu->master]['child'][] = $menuData;
                }
            }

            // Convert grouped menus to a simple array
            $result = array_values($groupedMenus);

            return ResponseHelper::jsonResponse(200, 'Menu list fetched successfully', $result);
        } catch (\Exception $e) {
            return ResponseHelper::jsonResponse(500, 'Failed to fetch menu list', $e->getMessage());
        }
    }

    // List sidebar menus
    public function sidebar($media)
    {
        try {
            $user = AuthHelper::requireAuth();
            
            // Ambil main menu menggunakan Eloquent
            $mainMenus = UserMenus::select('user_menus.id', 'user_menus.name', 'user_menus.url', 'master_icons.code as icon')
                ->join('user_access', function($join) use ($user) {
                    $join->on('user_menus.id', '=', 'user_access.menu_id')
                        ->where('user_access.level_id', '=', $user['level_id'])
                        ->where('user_access.permission', '=', 'access')
                        ->whereNull('user_access.deleted_by_id');
                })
                ->join('master_icons', 'user_menus.icon_id', '=', 'master_icons.id')
                ->where('user_menus.level', 'main')
                ->where('user_menus.media', $media)
                ->where('user_access.status', 1)
                ->where('user_menus.status', 1)
                ->whereNull('user_menus.deleted_by_id')
                ->orderBy('user_menus.sort_master', 'asc')
                ->get();
    
            // Ambil sub menu menggunakan Eloquent
            $subMenus = UserMenus::select('user_menus.master', 'user_menus.name', 'user_menus.url', 'master_icons.code as icon')
                ->join('user_access', function($join) use ($user) {
                    $join->on('user_menus.id', '=', 'user_access.menu_id')
                        ->where('user_access.level_id', '=', $user['level_id'])
                        ->where('user_access.permission', '=', 'access')
                        ->whereNull('user_access.deleted_by_id');
                })
                ->join('master_icons', 'user_menus.icon_id', '=', 'master_icons.id')
                ->where('user_menus.level', 'sub')
                ->where('user_menus.media', $media)
                ->where('user_access.status', 1)
                ->where('user_menus.status', 1)
                ->whereNull('user_menus.deleted_by_id')
                ->orderBy('user_menus.sort_sub', 'asc')
                ->get();
    
            // Susun menu
            $result = [];
            foreach ($mainMenus as $main) {
                $menuItem = [
                    'name' => $main->name,
                    'url' => $main->url,
                    'icon' => $main->icon,
                    'child' => []
                ];
    
                // Cari submenu yang terkait
                foreach ($subMenus as $sub) {
                    if ($sub->master == $main->id) {
                        $menuItem['child'][] = [
                            'name' => $sub->name,
                            'url' => $sub->url,
                            'icon' => $sub->icon
                        ];
                    }
                }
    
                $result[] = $menuItem;
            }
    
            return ResponseHelper::jsonResponse(200, 'Menu list fetched successfully', $result);
        } catch (\Exception $e) {
            return ResponseHelper::jsonResponse(500, 'Failed to fetch menu list', $e->getMessage());
        }
    }

    // Show a single menu
    public function show($id)
    {
        try {
            AuthHelper::requireAuth();
            $menu = UserMenus::whereNull('deleted_by_id')->where('id', $id)->first(); // Updated query
            if (!$menu) {
                return ResponseHelper::jsonResponse(404, 'Menu not found', null);
            }
            return ResponseHelper::jsonResponse(200, 'Menu fetched successfully', $menu);
        } catch (\Exception $e) {
            return ResponseHelper::jsonResponse(500, 'Failed to fetch menu', $e->getMessage());
        }
    }

    // Update an existing menu
    public function update(Request $request, $id)
    {
        try {
            AuthHelper::requireAuth();
            $menu = UserMenus::whereNull('deleted_by_id')->where('id', $id)->first(); // Updated query
            if (!$menu) {
                return ResponseHelper::jsonResponse(404, 'Menu not found', null);
            }

            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                // Add other fields as necessary
            ]);

            $menu->update($validated);

            return ResponseHelper::jsonResponse(200, 'Menu updated successfully', $menu);
        } catch (ValidationException $e) {
            $flattenedErrors = collect($e->errors())->flatten()->all();
            return ResponseHelper::jsonResponse(422, 'Validation error', $flattenedErrors);
        } catch (\Exception $e) {
            return ResponseHelper::jsonResponse(500, 'Failed to update menu', $e->getMessage());
        }
    }
}
