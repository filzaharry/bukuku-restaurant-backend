<?php

namespace App\Http\Controllers\Config;

use App\Http\Controllers\Controller;
use App\Models\UserAccess;
use App\Models\UserMenus;
use Illuminate\Http\Request;
use App\Helpers\AuthHelper;
use App\Helpers\ResponseHelper;
use App\Models\User;

class AccessController extends Controller
{
    public function access(Request $request)
    {
        try {
            AuthHelper::requireAuth();

            $menu_slug = '/' . $request->slug;
            $menu = UserMenus::where('url', $menu_slug)->firstOrFail();
            $user = User::find(auth()->id());

            $access = UserAccess::where('level_id', $user->level_id)
                ->where('menu_id', $menu->id)
                ->get();

            $formattedAccess = $access->map(function($item) {
                return [
                    'type' => $item->permission,
                    'status' => (bool)$item->status
                ];
            });

            return ResponseHelper::jsonResponse(200, 'Access retrieved', $formattedAccess);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ResponseHelper::jsonResponse(404, 'Menu not found');
        } catch (\Exception $e) {
            return ResponseHelper::jsonResponse(500, 'Failed to fetch access');
        }
    }
}
