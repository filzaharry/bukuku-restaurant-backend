<?php

namespace App\Http\Controllers\Config;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Helpers\AuthHelper;
use App\Helpers\ResponseHelper;
use App\Models\Content\FnbCategory;
use App\Models\Content\FnbTable;
use App\Models\UserLevel;

class DropdownController extends Controller
{
    public function userLevel(Request $request)
    {
        try {
            AuthHelper::requireAuth();

            $data = UserLevel::select('id', 'name')
                ->whereNull('deleted_by_id')
                ->when($request->query('search'), function ($query, $search) {
                    $query->where('name', 'like', '%' . $search . '%');
                }, function ($query) {
                    $query->limit(10);
                })
                ->orderBy('name')
                ->get();

            return ResponseHelper::jsonResponse(200, 'User level dropdown fetched', $data);
        } catch (\Exception $e) {
            return ResponseHelper::jsonResponse(500, 'Failed to fetch user level dropdown', $e->getMessage());
        }
    }

    public function fnbCategory(Request $request)
    {
        try {
            AuthHelper::requireAuth();

            $cacheKey = 'dropdown_fnb_category_' . md5(json_encode($request->all()));
            $cached = \Illuminate\Support\Facades\Redis::get($cacheKey);
            
            if ($cached) {
                return ResponseHelper::jsonResponse(200, 'dropdown fetched from cache', json_decode($cached, true));
            }

            $data = FnbCategory::select('id', 'name')
                ->whereNull('deleted_by_id')
                ->when($request->query('search'), function ($query, $search) {
                    $query->where('name', 'like', '%' . $search . '%');
                }, function ($query) {
                    $query->limit(10);
                })
                ->orderBy('name')
                ->get();

            \Illuminate\Support\Facades\Redis::setex($cacheKey, 3600, json_encode($data));

            return ResponseHelper::jsonResponse(200, 'dropdown fetched', $data);
        } catch (\Exception $e) {
            return ResponseHelper::jsonResponse(500, 'Failed to fetch dropdown', $e->getMessage());
        }
    }

    public function fnbTable(Request $request)
    {
        try {
            AuthHelper::requireAuth();

            $cacheKey = 'dropdown_fnb_table_' . md5(json_encode($request->all()));
            $cached = \Illuminate\Support\Facades\Redis::get($cacheKey);
            
            if ($cached) {
                return ResponseHelper::jsonResponse(200, 'dropdown fetched from cache', json_decode($cached, true));
            }

            $data = FnbTable::select('id', 'name')
                ->where('status', 0)
                ->whereNull('deleted_by_id')
                ->when($request->query('search'), function ($query, $search) {
                    $query->where('name', 'like', '%' . $search . '%');
                }, function ($query) {
                    $query->limit(10);
                })
                ->orderBy('name')
                ->get();

            // Cache tables for a short time to prevent DB spike but keep relatively fresh
            \Illuminate\Support\Facades\Redis::setex($cacheKey, 30, json_encode($data));

            return ResponseHelper::jsonResponse(200, 'dropdown fetched', $data);
        } catch (\Exception $e) {
            return ResponseHelper::jsonResponse(500, 'Failed to fetch dropdown', $e->getMessage());
        }
    }
}
