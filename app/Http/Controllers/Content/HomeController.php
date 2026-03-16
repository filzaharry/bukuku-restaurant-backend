<?php

namespace App\Http\Controllers\Content;

use App\Helpers\FilterHelper;
use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Content\FnbMenu;
use App\Models\Content\FnbCategory;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function fnbCategoryList(Request $request)
    {
        try {
            // Cek Redis
            $cached = \Illuminate\Support\Facades\Redis::get('fnb_categories');
            if ($cached) {
                return ResponseHelper::jsonResponse(200, 'Menu category list fetched successfully from cache', json_decode($cached, true));
            }

            $query = FnbCategory::whereNull('deleted_by_id')->where('status', 1);
            $result = FilterHelper::filterAndPaginate($query, $request);
            $result['data'] = collect($result['data'])->map(function ($item) {
                return [
                    'id' => $item['id'],
                    'name' => $item['name'],
                    'image' => $item['image'],
                    'status' => $item['status'],
                ];
            });

            // Set Redis
            \Illuminate\Support\Facades\Redis::setex('fnb_categories', 3600, json_encode($result)); // Cache 1 Jam

            return ResponseHelper::jsonResponse(200, 'Menu category list fetched successfully', $result);
        } catch (\Exception $e) {
            return ResponseHelper::jsonResponse(500, 'Failed to fetch menu category list', $e->getMessage());
        }
    }

    public function fnbList(Request $request)
    {
        try {
            // Note: cache with pagination can be tricky, if no parameter search is provided we can cache it
            // we will cache the exact query based on URL path parameters
            $cacheKey = 'fnb_items_' . md5(json_encode($request->all()));
            
            $cached = \Illuminate\Support\Facades\Redis::get($cacheKey);
            if ($cached) {
                return ResponseHelper::jsonResponse(200, 'Menu list fetched successfully from cache', json_decode($cached, true));
            }

            $query = FnbMenu::with([
                'category' => function ($query) {
                    $query->select('id', 'name');
                }
            ])
                ->join('master_fnb_category', 'master_fnb_category.id', '=', 'master_fnb_menu.category_id')
                ->whereNull('master_fnb_menu.deleted_by_id')
                ->select(
                    'master_fnb_menu.id',
                    'master_fnb_menu.name',
                    'master_fnb_menu.description',
                    'master_fnb_menu.price',
                    'master_fnb_menu.image',
                    'master_fnb_menu.status',
                    'master_fnb_menu.category_id'
                )
                ->orderBy('master_fnb_category.name', 'asc');

            if ($request->has('category_id') && $request->category_id != '') {
                $query->where('master_fnb_menu.category_id', $request->category_id);
            }

            $result = FilterHelper::filterAndPaginate($query, $request, [
                'master_fnb_menu.name',
                'master_fnb_menu.description',
                'master_fnb_menu.price'
            ]);

            $result['data'] = collect($result['data'])->map(function ($item) {
                return [
                    'id' => $item['id'],
                    'name' => $item['name'],
                    'description' => $item['description'],
                    'price' => $item['price'],
                    'image' => $item['image'],
                    'status' => $item['status'],
                    'category' => $item['category'] ? [
                        'id' => $item['category']['id'],
                        'name' => $item['category']['name'],
                    ] : null,
                ];
            });

            // Remember cache for 1 hour
            \Illuminate\Support\Facades\Redis::setex($cacheKey, 3600, json_encode($result));

            return ResponseHelper::jsonResponse(200, 'Menu list fetched successfully', $result);
        } catch (\Exception $e) {
            return ResponseHelper::jsonResponse(500, 'Failed to fetch menu list', $e->getMessage());
        }
    }

    public function fnbDetail($id)
    {
        try {
            $cacheKey = 'fnb_item_detail_' . $id;
            $cached = \Illuminate\Support\Facades\Redis::get($cacheKey);
            
            if ($cached) {
                return ResponseHelper::jsonResponse(200, 'FNB detail fetched successfully from cache', json_decode($cached, true));
            }

            $fnb = FnbMenu::with([
                'category:id,name',
                'levels:id,name,price',
                'extras:id,name,price'
            ])
                ->whereNull('deleted_by_id')
                ->select('id', 'name', 'description', 'price', 'image', 'status', 'category_id')
                ->find($id);

            if (!$fnb) {
                return ResponseHelper::jsonResponse(404, 'FNB item not found', null);
            }

            // Mapping hasil sesuai model Flutter
            $result = [
                'id' => $fnb->id,
                'name' => $fnb->name,
                'description' => $fnb->description,
                'price' => (string) $fnb->price, // Flutter expects string for price
                'image' => $fnb->image,
                'status' => (int) $fnb->status,
                'category' => $fnb->category ? [
                    'id' => $fnb->category->id,
                    'name' => $fnb->category->name,
                ] : null,
                'levels' => $fnb->levels->map(function ($level) {
                    return [
                        'id' => $level->id,
                        'name' => $level->name,
                        'price' => (string) $level->price,
                    ];
                })->values(), // values() biar hasilnya array index 0,1,2... bukan object keyed
                'extras' => $fnb->extras->map(function ($extra) {
                    return [
                        'id' => $extra->id,
                        'name' => $extra->name,
                        'price' => (string) $extra->price,
                    ];
                })->values(),
            ];

            // Cache untuk 1 Jam
            \Illuminate\Support\Facades\Redis::setex($cacheKey, 3600, json_encode($result));

            return ResponseHelper::jsonResponse(200, 'FNB detail fetched successfully', $result);
        } catch (\Exception $e) {
            return ResponseHelper::jsonResponse(500, 'Failed to fetch FNB detail', $e->getMessage());
        }
    }
}
