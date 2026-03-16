<?php

namespace App\Http\Controllers\Content;

use App\Helpers\FilterHelper;
use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Content\FnbCategory;
use App\Models\Content\FnbMenu;
use App\Models\Content\FnbTable;
use Illuminate\Http\Request;

class PosController extends Controller
{
    /**
     * Get table by unique ID (used for QR code scan)
     */
    public function getTableByUniqueId($uniqueId)
    {
        try {
            $table = FnbTable::whereNull('deleted_by_id')
                ->where('status', 1)
                ->where('unique_id', $uniqueId)
                ->first();

            if (!$table) {
                return ResponseHelper::jsonResponse(404, 'Table not found or inactive', null);
            }

            return ResponseHelper::jsonResponse(200, 'Table fetched successfully', $table);
        } catch (\Exception $e) {
            return ResponseHelper::jsonResponse(500, 'Failed to fetch table', $e->getMessage());
        }
    }

    /**
     * Get list of active categories
     */
    public function getCategories(Request $request)
    {
        try {
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

            return ResponseHelper::jsonResponse(200, 'Category list fetched successfully', $result);
        } catch (\Exception $e) {
            return ResponseHelper::jsonResponse(500, 'Failed to fetch category list', $e->getMessage());
        }
    }

    /**
     * Get list of active items
     */
    public function getItems(Request $request)
    {
        try {
            $query = FnbMenu::with([
                'category' => function ($query) {
                    $query->select('id', 'name');
                }
            ])
                ->whereNull('deleted_by_id')
                ->where('status', 1) // Only active items
                ->select(
                    'id',
                    'name',
                    'description',
                    'price',
                    'image',
                    'status',
                    'category_id'
                )
                ->orderBy('name', 'asc');

            if ($request->has('category_id') && $request->category_id != '') {
                $query->where('category_id', $request->category_id);
            }

            $result = FilterHelper::filterAndPaginate($query, $request, [
                'name',
                'description',
                'price'
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

            return ResponseHelper::jsonResponse(200, 'Item list fetched successfully', $result);
        } catch (\Exception $e) {
            return ResponseHelper::jsonResponse(500, 'Failed to fetch item list', $e->getMessage());
        }
    }
}
