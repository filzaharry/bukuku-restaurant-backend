<?php

namespace App\Http\Controllers\Config;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Helpers\AuthHelper;
use App\Helpers\ResponseHelper;
use App\Helpers\FilterHelper;
use App\Models\MasterParams;
use Illuminate\Validation\ValidationException;

class ParamController extends Controller
{
    public function index(Request $request)
    {
        try {
            AuthHelper::requireAuth();

            $query = MasterParams::select(
                'id',
                'param',
                'value',
                'description',
                'status',
                'created_at'
            )
                ->whereNull('deleted_by_id');

            $result = FilterHelper::filterAndPaginate($query, $request);

            return ResponseHelper::jsonResponse(200, 'Param list fetched successfully', $result);
        } catch (\Exception $e) {
            return ResponseHelper::jsonResponse(500, 'Failed to fetch param list', $e->getMessage());
        }
    }

    public function show($id)
    {
        try {
            AuthHelper::requireAuth();

            $param = MasterParams::whereNull('deleted_by_id')
                ->where('id', $id)
                ->first();

            if (!$param) {
                return ResponseHelper::jsonResponse(404, 'Param not found');
            }

            return ResponseHelper::jsonResponse(200, 'Param fetched successfully', [
                'id' => $param->id,
                'name' => $param->param,
                'value' => $param->value,
                'desc' => $param->description,
                'created_at' => $param->created_at
            ]);
        } catch (\Exception $e) {
            return ResponseHelper::jsonResponse(500, 'Failed to fetch param', $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        try {
            AuthHelper::requireAuth();

            $param = MasterParams::whereNull('deleted_by_id')
                ->where('id', $id)
                ->first();

            if (!$param) {
                return ResponseHelper::jsonResponse(404, 'Param not found');
            }

            $validated = $request->validate([
                'value' => 'sometimes|required|string|max:255',
                'description' => 'sometimes|required'
            ]);

            $param->update($validated);

            return ResponseHelper::jsonResponse(200, 'Param updated successfully', $param);
        } catch (ValidationException $e) {
            $errors = collect($e->errors())->flatten()->all();
            return ResponseHelper::jsonResponse(422, 'Validation error', $errors);
        } catch (\Exception $e) {
            return ResponseHelper::jsonResponse(500, 'Failed to update param', $e->getMessage());
        }
    }
}
