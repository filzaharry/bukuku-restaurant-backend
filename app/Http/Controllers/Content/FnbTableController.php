<?php

namespace App\Http\Controllers\Content;

use App\Helpers\AuthHelper;
use App\Helpers\FilterHelper;
use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Content\FnbTable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FnbTableController extends Controller
{
    // List all data
    public function index(Request $request)
    {
        try {
            AuthHelper::requireAuth();

            $query = FnbTable::whereNull('deleted_by_id');

            $result = FilterHelper::filterAndPaginate($query, $request);

            return ResponseHelper::jsonResponse(200, 'Data list fetched successfully', $result);
        } catch (\Exception $e) {
            return ResponseHelper::jsonResponse(500, 'Failed to fetch data list', $e->getMessage());
        }
    }

    // Get count of available tables
    public function count()
    {
        try {
            AuthHelper::requireAuth();

            $count = FnbTable::whereNull('deleted_by_id')->where('status', 1)->count();

            return ResponseHelper::jsonResponse(200, 'Data count fetched successfully', [
                'count' => $count
            ]);
        } catch (\Exception $e) {
            return ResponseHelper::jsonResponse(500, 'Failed to fetch data count', $e->getMessage());
        }
    }

    // Show single data
    public function show($id)
    {
        try {
            AuthHelper::requireAuth();

            $data = FnbTable::whereNull('deleted_by_id')->find($id);

            if (!$data) {
                return ResponseHelper::jsonResponse(404, 'Data not found', null);
            }

            return ResponseHelper::jsonResponse(200, 'Data fetched successfully', $data);
        } catch (\Exception $e) {
            return ResponseHelper::jsonResponse(500, 'Failed to fetch data', $e->getMessage());
        }
    }

    // Store new data
    public function store(Request $request)
    {
        try {
            AuthHelper::requireAuth();

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'status' => 'nullable|integer|in:0,1',
            ]);

            DB::beginTransaction();

            $table = FnbTable::create([
                'unique_id' => 'TBL-' . strtoupper(Str::random(6)),
                'name' => $validated['name'],
                'status' => $validated['status'] ?? 1,
                'created_by_id' => AuthHelper::getAuthUserId(),
            ]);

            DB::commit();

            return ResponseHelper::jsonResponse(201, 'Data created successfully', $table);
        } catch (ValidationException $e) {
            return ResponseHelper::jsonResponse(422, 'Validation error', collect($e->errors())->flatten()->all());
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHelper::jsonResponse(500, 'Failed to create data', $e->getMessage());
        }
    }

    // Update data
    public function update(Request $request, $id)
    {
        try {
            AuthHelper::requireAuth();

            $table = FnbTable::whereNull('deleted_by_id')->find($id);

            if (!$table) {
                return ResponseHelper::jsonResponse(404, 'Data not found', null);
            }

            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'status' => 'sometimes|required|integer|in:0,1',
            ]);

            DB::beginTransaction();

            $table->update(array_merge($validated, [
                'updated_by_id' => AuthHelper::getAuthUserId(),
            ]));

            DB::commit();

            return ResponseHelper::jsonResponse(200, 'Data updated successfully', $table);
        } catch (ValidationException $e) {
            return ResponseHelper::jsonResponse(422, 'Validation error', collect($e->errors())->flatten()->all());
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHelper::jsonResponse(500, 'Failed to update data', $e->getMessage());
        }
    }

    // Soft delete data
    public function destroy($id)
    {
        try {
            AuthHelper::requireAuth();

            $data = FnbTable::whereNull('deleted_by_id')->find($id);

            if (!$data) {
                return ResponseHelper::jsonResponse(404, 'Data not found', null);
            }

            DB::beginTransaction();

            $data->update([
                'deleted_by_id' => AuthHelper::getAuthUserId(),
                'deleted_at' => now(),
            ]);

            DB::commit();

            return ResponseHelper::jsonResponse(200, 'Data deleted successfully', null);
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHelper::jsonResponse(500, 'Failed to delete data', $e->getMessage());
        }
    }
}
