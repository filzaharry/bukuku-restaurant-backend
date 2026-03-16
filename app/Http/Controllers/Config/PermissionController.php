<?php

namespace App\Http\Controllers\Config;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Helpers\AuthHelper;
use App\Helpers\ResponseHelper;
use App\Models\UserAccess;
use App\Models\UserPermission;

class PermissionController extends Controller
{
    public function dropdown(Request $request)
    {
        try {
            AuthHelper::requireAuth();
            
            $permissions = UserAccess::select('id', 'name')
                ->whereNull('deleted_by_id')
                ->when($request->key, function($query) use ($request) {
                    $query->where('params', 'like', '%'.$request->key.'%');
                }, function($query) {
                    $query->limit(10);
                })
                ->orderBy('params')
                ->get();

            return ResponseHelper::jsonResponse(200, 'Permission dropdown fetched', $permissions);
            
        } catch (\Exception $e) {
            return ResponseHelper::jsonResponse(500, 'Failed to fetch permission dropdown', $e->getMessage());
        }
    }
}
