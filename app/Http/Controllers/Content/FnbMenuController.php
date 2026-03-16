<?php

namespace App\Http\Controllers\Content;

use App\Helpers\AttachmentHelper;
use App\Helpers\AuthHelper;
use App\Helpers\FilterHelper;
use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Content\FnbMenu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FnbMenuController extends Controller
{
    // List all data
    public function index(Request $request)
    {
        try {
            AuthHelper::requireAuth();

            $query = FnbMenu::with([
                'category' => function ($query) {
                    $query->whereNull('deleted_by_id')->where('status', 1);
                },
            ])->whereNull('deleted_by_id');

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
    
            $data = FnbMenu::find($id);
    
            if (!$data) {
                return ResponseHelper::jsonResponse(404, 'Data not found', null);
            }
    
            // Convert model ke array
            $result = $data;
    
            return ResponseHelper::jsonResponse(200, 'Data fetched successfully', $result);
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
                'name' => 'required|string|max:255|unique:master_fnb_menu,name',
                'category_id' => 'required|int',
                'description' => 'required|string',
                'price' => 'required|string',
            
                'image' => 'nullable|file|mimes:jpg,jpeg,png',
            ]);
            
            DB::beginTransaction();

            // Handle image (optional)
            $filePath = 'fnb/images';
            $fileData = AttachmentHelper::handleAttachment(
                $request->file('image'),
                $filePath,
                null,
                'create'
            );

            $menu = FnbMenu::create([
                'name' => $validated['name'],
                'image' => $fileData['path'] ?? null,
                'category_id' => $validated['category_id'],
                'description' => $validated['description'],
                'price' => $validated['price'],
                'status' => 1,
                'created_by_id' => AuthHelper::getAuthUserId(),
            ]);

            DB::commit();

            return ResponseHelper::jsonResponse(201, 'Data created successfully', $menu);
        } catch (ValidationException $e) {
            return ResponseHelper::jsonResponse(422, 'Validation error', collect($e->errors())->flatten()->all());
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHelper::jsonResponse(500, 'Failed to create data', $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        try {
            AuthHelper::requireAuth();

            $menu = FnbMenu::find($id);

            if (!$menu) {
                return ResponseHelper::jsonResponse(404, 'Data not found', null);
            }

            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255|unique:master_fnb_menu,name,' . $id,
                'category_id' => 'sometimes|int',
                'description' => 'nullable|string',
                'price' => 'nullable|string',
                'status' => 'nullable|in:0,1',
                'image' => 'nullable|file|mimes:jpg,jpeg,png',
                'extras' => 'array',
                'extras.*' => 'integer|exists:master_fnb_extra,id',
                'levels' => 'array',
                'levels.*' => 'integer|exists:master_fnb_level,id'
            ]);

            DB::beginTransaction();

            $updatePayload = array_merge(
                $validated,
                ['updated_by_id' => AuthHelper::getAuthUserId()]
            );

            // Handle image update
            if ($request->hasFile('image')) {
                $filePath = 'fnb/images';
                $fileData = AttachmentHelper::handleAttachment(
                    $request->file('image'),
                    $filePath,
                    $menu->image,
                    'update'
                );

                if ($fileData) {
                    $updatePayload['image'] = $fileData['path'];
                }
            }

            $menu->update($updatePayload);

            DB::commit();

            return ResponseHelper::jsonResponse(200, 'Data updated successfully', $menu);
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

            $data = FnbMenu::whereNull('deleted_by_id')->find($id);

            if (!$data) {
                return ResponseHelper::jsonResponse(404, 'Data not found', null);
            }

            DB::beginTransaction();

            // Hapus file jika ada
            if (!empty($data->image)) {
                AttachmentHelper::deleteAttachment($data->image);
            }

            // Soft delete
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
