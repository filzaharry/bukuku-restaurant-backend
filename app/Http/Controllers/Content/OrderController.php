<?php

namespace App\Http\Controllers\Content;

use App\Helpers\AttachmentHelper;
use App\Helpers\FilterHelper;
use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Content\FnbDelivery;
use App\Models\Content\FnbMenu;
use App\Models\Content\FnbExtra;
use App\Models\Content\FnbLevel;
use App\Models\Content\FnbTable;
use App\Models\Content\Order;
use App\Models\MasterParams;
use App\Models\Content\OrderDetail;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    public function createOrder(Request $request)
    {
        try {
            // Step 1: Validasi input
            $validated = $request->validate([
                'table_id' => 'required|integer|exists:master_fnb_table,id',
                'customer_name' => 'required|string|max:255',
                'customer_phone' => 'required|string|max:20',
                'items' => 'required|array|min:1',
                'items.*.fnb_id' => 'required|integer',
            ]);

            $lockKey = 'lock_order_table_' . $validated['table_id'];
            // Try to acquire lock for 10 seconds
            $lock = \Illuminate\Support\Facades\Redis::set($lockKey, 1, 'EX', 10, 'NX');
            if (!$lock) {
                return ResponseHelper::jsonResponse(429, 'Order is currently being processed for this table, please wait.', null);
            }

            DB::beginTransaction();

            // Step 2: Cek apakah meja sedang dipakai
            $table = FnbTable::where('id', $validated['table_id'])->first();
            if ($table->status == 1) {
                return ResponseHelper::jsonResponse(400, 'Table is already in use', null);
            }
            $table->update([
                'status' => 1, // 1 = used, 0 = available
            ]);

            // Step 3: Hitung subtotal dari semua item
            $subtotal = 0;
            foreach ($validated['items'] as $item) {
                $fnb = FnbMenu::findOrFail($item['fnb_id']);
                $subtotal += (float) $fnb->price;
            }

            // Step 7: Simpan order utama
            $order = Order::create([
                'order_code' => 'ORD-' . time() . rand(100, 999),
                'table_id' => $validated['table_id'],
                'customer_name' => $validated['customer_name'],
                'customer_phone' => $validated['customer_phone'],
                'subtotal' => $subtotal,
                'tax' => 11,
                'total' => $subtotal + ($subtotal * 0.11),
                'status' => 0,
            ]);

            // Step 8: Simpan order details (item, level, extra)
            foreach ($validated['items'] as $item) {
                $fnb = FnbMenu::findOrFail($item['fnb_id']);
                $basePrice = (float) $fnb->price;

                // Gabungkan semua extra_id jadi string "1,2,3"
                $extraIdString = !empty($extraIds) ? implode(',', $extraIds) : null;

                // Simpan satu baris saja ke OrderDetail
                OrderDetail::create([
                    'order_id' => $order->id,
                    'fnb_id' => $fnb->id,
                    'price' => $basePrice,
                    'quantity' => 1,
                ]);
            }

            DB::commit();

            // Clear cache related to orders and items
            \Illuminate\Support\Facades\Redis::del($lockKey);
            
            // To ensure new list changes, we should clear fnb_items related caches, 
            // since we used md5 keys in HomeController, we may need to use keys() although it's not strictly recommended
            // For now, we clean the general prefix if they existed.
            // As fnb_items use cacheKey, \Illuminate\Support\Facades\Redis::del('fnb_items_*') might not work directly without Keys.
            // But leaving it as it was:
            \Illuminate\Support\Facades\Redis::del('fnb_items');
            \Illuminate\Support\Facades\Redis::del('fnb_categories');

            // Step 9: Return response sukses
            return ResponseHelper::jsonResponse(201, 'Order created successfully', [
                'order_id' => $order->order_code,
                'payment_method' => $order->payment_method,
                'subtotal' => $subtotal,
            ]);
        } catch (ValidationException $e) {
            return ResponseHelper::jsonResponse(422, 'Validation error', collect($e->errors())->flatten()->all());
        } catch (\Exception $e) {
            DB::rollBack();
            if (isset($lockKey)) {
                \Illuminate\Support\Facades\Redis::del($lockKey);
            }
            return ResponseHelper::jsonResponse(500, 'Failed to create order', $e->getMessage());
        }
    }

    public function orderDetail($orderCode)
    {
        try {
            // 1. Ambil order + relasi dasar (tanpa extra, karena extra_id berisi banyak ID)
            $order = Order::with([
                'details.fnb:id,name,price,image',
            ])->where('order_code', $orderCode)->firstOrFail();

            // 3. Group order details (kalau perlu)
            $groupedDetails = $this->groupOrderDetails($order->details);

            // 4. Convert order ke array
            $response = $order->toArray();

            // 5. Hapus fields tidak perlu
            unset(
                $response['created_by_id'],
                $response['updated_by_id'],
                $response['deleted_by_id'],
                $response['deleted_at'],
                $response['items'] // opsional
            );

            // 6. Format angka
            $response['subtotal'] = number_format($order->subtotal, 0, '.', '');
            $response['tax'] = number_format($order->tax, 0, '.', '');
            $response['total'] = number_format($order->total, 0, '.', '');

            // 7. Masukkan grouped details
            $response['details'] = array_values($groupedDetails);

            // 8. Return response JSON
            return ResponseHelper::jsonResponse(200, 'Order fetched successfully', $response);
        } catch (\Exception $e) {
            return ResponseHelper::jsonResponse(404, 'Order not found', $e->getMessage());
        }
    }

    private function groupOrderDetails($details)
    {
        $grouped = [];

        foreach ($details as $detail) {
            $fnbId   = $detail->fnb_id;
            $key     = $fnbId;

            $quantity = $detail->quantity ?? 1;
            $price    = floatval($detail->price);

            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'fnb_id'     => $fnbId,
                    'fnb_name'   => $detail->fnb->name ?? 'Unknown',
                    'fnb_image'  => $detail->fnb->image ?? '',
                    'quantity'   => 0,
                    'price'      => $price,
                    'totalPrice' => 0,
                ];
            }

            // Tambah quantity
            $grouped[$key]['quantity'] += $quantity;

            // Hitung totalPrice
            $grouped[$key]['totalPrice'] = number_format(
                $grouped[$key]['price'] * $grouped[$key]['quantity'],
                0,
                '.',
                ''
            );
        }

        return $grouped;
    }

    public function orderList(Request $request)
    {
        try {
            $query = Order::orderByDesc('created_at')->with([
                'details.fnb:id,name',
            ]);
            $orders = FilterHelper::filterAndPaginate($query, $request, [
                'order_code',
                'customer_name',
                'customer_phone',
            ]);
            return ResponseHelper::jsonResponse(200, 'Order queue fetched successfully', $orders);
        } catch (\Exception $e) {
            return ResponseHelper::jsonResponse(500, 'Failed to fetch order queue', $e->getMessage());
        }
    }


    public function markOrderAsDone($orderCode)
    {
        try {
            $order = Order::where('order_code', $orderCode)->first();

            if (!$order) {
                return ResponseHelper::jsonResponse(404, 'Order not found', null);
            }

            if ($order->status == 1) {
                return ResponseHelper::jsonResponse(400, 'Order already marked as done', null);
            }

            $order->update([
                'status' => 1, // Done
                'updated_by_id' => null, // Optional: null since no auth
            ]);

            return ResponseHelper::jsonResponse(200, 'Order marked as done successfully', [
                'order_code' => $order->order_code,
                'status' => 1,
                'status_text' => 'Done',
            ]);
        } catch (\Exception $e) {
            return ResponseHelper::jsonResponse(500, 'Failed to update order status', $e->getMessage());
        }
    }
}
