<?php

namespace App\Http\Controllers\Content;

use App\Helpers\AttachmentHelper;
use App\Http\Controllers\Controller;
use App\Helpers\AuthHelper;
use App\Helpers\FilterHelper;
use App\Helpers\ResponseHelper;
use App\Models\Content\FnbCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FnbCategoryController extends Controller
{
    // List all data
    public function index(Request $request)
    {
        try {
            AuthHelper::requireAuth();

            $query = FnbCategory::select('id', 'image', 'name', 'status', 'created_at')
                ->whereNull('deleted_by_id');

            $result = FilterHelper::filterAndPaginate($query, $request);

            return ResponseHelper::jsonResponse(200, 'Data list fetched successfully', $result);
        } catch (\Exception $e) {
            return ResponseHelper::jsonResponse(500, 'Failed to fetch data list', $e->getMessage());
        }
    }

    // Show single data
    public function show($id)
    {
        try {
            AuthHelper::requireAuth();

            $data = FnbCategory::whereNull('deleted_by_id')
                ->select('id', 'name', 'image', 'status', 'created_at')
                ->find($id);

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
                'name' => 'required|string|max:255|unique:master_fnb_category,name',
            ]);

            DB::beginTransaction();

            $data = FnbCategory::create([
                'name' => $validated['name'],
                'status' => 1, // on
                'created_by_id' => AuthHelper::getAuthUserId(),
            ]);

            DB::commit();

            return ResponseHelper::jsonResponse(201, 'Data created successfully', $data);
        } catch (ValidationException $e) {
            return ResponseHelper::jsonResponse(422, 'Validation error', collect($e->errors())->flatten()->all());
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHelper::jsonResponse(500, 'Failed to create data', $e->getMessage());
        }
    }

    // Update existing data
    public function update(Request $request, $id)
    {
        try {
            AuthHelper::requireAuth();

            $data = FnbCategory::find($id);

            if (!$data) {
                return ResponseHelper::jsonResponse(404, 'Data not found', null);
            }

            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255|unique:master_fnb_category,name,' . $id,
                'status' => 'nullable|in:0,1',
                'image' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
            ]);

            DB::beginTransaction();

            $updatePayload = array_merge(
                $validated,
                ['updated_by_id' => AuthHelper::getAuthUserId()]
            );

            // Handle image update (if file uploaded)
            if ($request->hasFile('image')) {
                $filePath = 'fnb-category/images';
                $fileData = AttachmentHelper::handleAttachment(
                    $request->file('image'),
                    $filePath,
                    $data->image, // existing image path
                    'update'
                );

                if ($fileData) {
                    $updatePayload['image'] = $fileData['path'];
                }
            }

            $data->update($updatePayload);

            DB::commit();

            return ResponseHelper::jsonResponse(200, 'Data updated successfully', $data);
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

            $data = FnbCategory::whereNull('deleted_by_id')->find($id);

            if (!$data) {
                return ResponseHelper::jsonResponse(404, 'Data not found', null);
            }

            DB::beginTransaction();

             // Hapus file jika ada
            if (!empty($data->image)) {
                AttachmentHelper::deleteAttachment($data->image);
            }

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
