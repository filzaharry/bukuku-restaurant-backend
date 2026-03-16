<?php

namespace App\Http\Controllers\Content;

use App\Helpers\AuthHelper;
use App\Helpers\FilterHelper;
use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Content\FnbMenu;
use App\Models\Content\Order;
use App\Models\Content\OrderReturn;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;

class FnbOrderController extends Controller
{
    public function index(Request $request)
    {
        try {
            AuthHelper::requireAuth();

            $query = Order::orderBy('created_at', 'desc');

            if ($request->has('statuses')) {
                $statuses = explode(',', $request->statuses);
                $query->whereIn('status', $statuses);
            }

            $orders = FilterHelper::filterAndPaginate($query, $request);

            return ResponseHelper::jsonResponse(200, 'Order queue fetched successfully', $orders);
        } catch (\Exception $e) {
            return ResponseHelper::jsonResponse(500, 'Failed to fetch order queue', $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            AuthHelper::requireAuth();

            $validated = $request->validate([
                'order_code' => 'required|string|unique:orders,order_code',
                'customer_name' => 'required|string',
                'customer_image' => 'nullable|string',
                'customer_phone' => 'nullable|string',
                'payment_method' => 'required|string',
                'payment_qris' => 'nullable|string',
                'delivery_id' => 'nullable|integer',
                'subtotal' => 'required|numeric',
                'tax' => 'required|numeric',
                'total' => 'required|numeric',
                'status' => 'required|integer',
                'items' => 'required|array',
                'items.*.fnb_id' => 'required|integer',
                'items.*.level_id' => 'nullable|integer',
                'items.*.extra_id' => 'nullable|integer',
                'items.*.price' => 'required|numeric',
                'items.*.quantity' => 'nullable|integer|min:1',
            ]);

            DB::beginTransaction();

            $order = Order::create([
                ...Arr::except($validated, ['items']),
                'created_by_id' => auth()->id(),
            ]);

            foreach ($validated['items'] as $item) {
                $order->details()->create([
                    'fnb_id' => $item['fnb_id'],
                    'level_id' => $item['level_id'] ?? null,
                    'extra_id' => $item['extra_id'] ?? null,
                    'price' => $item['price'],
                    'quantity' => $item['quantity'] ?? 1,
                ]);
            }

            DB::commit();

            return ResponseHelper::jsonResponse(201, 'Order created successfully', $order);
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHelper::jsonResponse(500, 'Failed to create order', $e->getMessage());
        }
    }

    public function show($id)
    {
        try {
            // 1. Auth check
            AuthHelper::requireAuth();

            // 2. Ambil order + relasi dasar
            $order = Order::with([
                'details.fnb:id,name,price,image',
                'details.level:id,name,price',
                'delivery:id,name,price,tax_percent',
            ])->findOrFail($id);

            // 3. Tangani extra_id yang pakai koma
            foreach ($order->details as $detail) {
                if (!empty($detail->extra_id)) {
                    // Ubah string "1,2,3" jadi array [1,2,3]
                    $extraIds = array_filter(explode(',', $detail->extra_id));

                    // Ambil data extra dari master
                    $extras = \App\Models\Content\FnbExtra::whereIn('id', $extraIds)
                        ->get(['id', 'name', 'price']);

                    // Simpan ke property baru (tidak menimpa relasi)
                    $detail->extras = $extras;
                } else {
                    $detail->extras = collect(); // kalau tidak ada extras
                }
            }

            // 4. Group order details seperti sebelumnya
            $groupedDetails = $this->groupOrderDetails($order->details);

            // 5. Convert ke array
            $response = $order->toArray();

            // 6. Hapus field yang tidak perlu
            unset(
                $response['created_by_id'],
                $response['updated_by_id'],
                $response['deleted_by_id'],
                $response['deleted_at'],
                $response['items']
            );

            // 7. Hitung subtotal, tax, total
            $subtotal = collect($groupedDetails)->sum(fn($item) => floatval($item['totalPrice']));
            $taxPercent = $order->delivery->tax_percent ?? 0;
            $tax = ($taxPercent / 100) * $subtotal;
            $deliveryPrice = floatval($order->delivery->price ?? 0);
            $total = $subtotal + $tax + $deliveryPrice;

            // 8. Set final values
            $response['subtotal'] = number_format($subtotal, 0, '.', '');
            $response['tax'] = number_format($tax, 0, '.', '');
            $response['total'] = number_format($total, 0, '.', '');
            $response['details'] = array_values($groupedDetails);

            // 9. Return JSON response
            return ResponseHelper::jsonResponse(200, 'Order fetched successfully', $response);
        } catch (\Exception $e) {
            return ResponseHelper::jsonResponse(404, 'Order not found', $e->getMessage());
        }
    }

    private function groupOrderDetails($details)
    {
        $grouped = [];

        foreach ($details as $detail) {
            $fnbId = $detail->fnb_id;
            $levelId = $detail->level_id;
            $key = $fnbId . '-' . ($levelId ?? 'null');

            // Default values
            $fnbPrice   = floatval($detail->fnb->price ?? 0);
            $levelPrice = floatval($detail->level->price ?? 0);
            $extraPrice = floatval($detail->extra->price ?? 0);
            $quantity   = $detail->quantity ?? 1;

            // Create new group if not exists
            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'fnb_id'     => $fnbId,
                    'fnb_name'   => $detail->fnb->name ?? '',
                    'fnb_image'   => $detail->fnb->image ?? '',
                    'quantity'   => 0,
                    'price'      => $fnbPrice,
                    'level'      => [],
                    'extras'     => [],
                    'totalPrice' => 0,
                ];

                if ($detail->level) {
                    $grouped[$key]['level'][] = [
                        'id'    => $detail->level->id,
                        'name'  => $detail->level->name,
                        'price' => $levelPrice,
                    ];
                }
            }

            // Tambah quantity
            $grouped[$key]['quantity'] += $quantity;

            // Tambah extras jika ada
            if ($detail->extra) {
                $grouped[$key]['extras'][] = [
                    'id'    => $detail->extra->id,
                    'name'  => $detail->extra->name,
                    'price' => $extraPrice,
                ];
            }

            // Hitung totalPrice untuk group ini
            $extraTotal = collect($grouped[$key]['extras'])->sum('price');
            $levelTotal = collect($grouped[$key]['level'])->sum('price');
            $basePrice  = $grouped[$key]['price'];
            $quantityTotal = $grouped[$key]['quantity'];

            $grouped[$key]['totalPrice'] = number_format(
                ($basePrice + $levelTotal + $extraTotal) * $quantityTotal,
                0,
                '.',
                ''
            );
        }

        return $grouped;
    }

    public function updateStatus($id, $status)
    {
        try {
            AuthHelper::requireAuth();

            $data = Order::whereNull('deleted_by_id')->find($id);

            if (!$data) {
                return ResponseHelper::jsonResponse(404, 'Data not found', null);
            }

            DB::beginTransaction();

            $data->update([
                'updated_by_id' => AuthHelper::getAuthUserId(),
                'status' => $status
            ]);

            DB::commit();

            return ResponseHelper::jsonResponse(200, 'Data updated successfully', $data);
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHelper::jsonResponse(500, 'Failed to update data', $e->getMessage());
        }
    }

    public function refundOrder(Request $request)
    {
        try {
            AuthHelper::requireAuth();

            $request->validate([
                'status' => 'required',
                'order_id' => 'required|integer|exists:orders,id',
                'nominal' => 'nullable|numeric',
                'notes' => 'nullable|string',
                'detail' => 'nullable|string',
            ]);

            $order = Order::whereNull('deleted_by_id')->find($request->order_id);

            if (!$order) {
                return ResponseHelper::jsonResponse(404, 'Order not found', null);
            }

            DB::beginTransaction();

            $updateData = [
                'refund_status' => 1,
                'updated_by_id' => AuthHelper::getAuthUserId(),
            ];

            if (!is_null($request->nominal)) {
                $updateData['refund_nominal'] = $request->nominal;
            }
            if (!is_null($request->notes)) {
                $updateData['refund_notes'] = $request->notes;
            }
            if (!is_null($request->detail)) {
                $updateData['refund_detail'] = $request->detail;
            }

            // Decode detail
            $details = $request->detail ? json_decode($request->detail, true) : [];
            $refundedFnbIds = collect($details)->pluck('fnb_id')->filter()->all();

            // Update status FnbMenu yang direfund
            if (is_array($details)) {
                foreach ($details as $item) {
                    if (isset($item['fnb_id'])) {
                        $fnb = FnbMenu::find($item['fnb_id']);
                        if ($fnb) {
                            $fnb->status = 0;
                            $fnb->save();
                        }
                    }
                }
            }

            // Update order yang sedang direfund
            $order->update($updateData);

            // Cari order lain hari ini dengan status 0 atau 1
            $otherOrders = Order::whereNull('deleted_by_id')
                ->whereIn('status', [0, 1])
                ->whereDate('created_at', now()->toDateString())
                ->where('id', '!=', $order->id)
                ->get();

            foreach ($otherOrders as $otherOrder) {
                $otherFnbIds = $otherOrder->details()->pluck('fnb_id')->all();
                $matchingFnbIds = array_intersect($refundedFnbIds, $otherFnbIds);

                if (!empty($matchingFnbIds)) {
                    $otherRefundDetails = collect($details)
                        ->whereIn('fnb_id', $matchingFnbIds)
                        ->values()
                        ->all();

                    $otherOrder->update([
                        'status' => 3, // stok habis
                        'refund_notes' => $request->notes,
                        'refund_detail' => json_encode($otherRefundDetails),
                        'updated_by_id' => AuthHelper::getAuthUserId(),
                    ]);
                }
            }

            DB::commit();

            return ResponseHelper::jsonResponse(200, 'Refund processed successfully', $order);
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHelper::jsonResponse(500, 'Failed to process refund', $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        try {
            AuthHelper::requireAuth();

            $validated = $request->validate([
                'customer_name' => 'required|string',
                'customer_image' => 'nullable|string',
                'customer_phone' => 'nullable|string',
                'payment_method' => 'required|string',
                'payment_qris' => 'nullable|string',
                'delivery_id' => 'nullable|integer',
                'subtotal' => 'required|numeric',
                'tax' => 'required|numeric',
                'total' => 'required|numeric',
                'status' => 'required|integer',
                'items' => 'required|array',
                'items.*.fnb_id' => 'required|integer|exists:fnb_menus,id',
                'items.*.level_id' => 'nullable|integer|exists:fnb_levels,id',
                'items.*.extra_id' => 'nullable|integer|exists:fnb_extras,id',
                'items.*.price' => 'required|numeric',
                'items.*.quantity' => 'nullable|integer|min:1',
            ]);

            $order = Order::findOrFail($id);

            DB::beginTransaction();

            $order->update([
                ...Arr::except($validated, ['items']),
                'updated_by_id' => AuthHelper::id(),
            ]);

            // Delete existing items (simplest way)
            $order->details()->delete();

            // Recreate order details
            foreach ($validated['items'] as $item) {
                $order->details()->create([
                    'fnb_id' => $item['fnb_id'],
                    'level_id' => $item['level_id'] ?? null,
                    'extra_id' => $item['extra_id'] ?? null,
                    'price' => $item['price'],
                    'quantity' => $item['quantity'] ?? 1,
                ]);
            }

            DB::commit();

            return ResponseHelper::jsonResponse(200, 'Order updated successfully', $order);
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHelper::jsonResponse(500, 'Failed to update order', $e->getMessage());
        }
    }

    public function returnItems(Request $request)
    {
        try {
            AuthHelper::requireAuth();

            $validated = $request->validate([
                'order_id' => 'required|integer|exists:orders,id',
                'phone' => 'required|string',
                'items' => 'required|array',
                'items.*.fnb_id' => 'required|integer|exists:fnb_menus,id',
                'items.*.level_id' => 'nullable|integer|exists:fnb_levels,id',
                'items.*.extra_id' => 'nullable|integer|exists:fnb_extras,id',
                'items.*.price' => 'required|numeric',
                'items.*.quantity' => 'required|integer|min:1',
            ]);

            $order = Order::findOrFail($validated['order_id']);

            DB::beginTransaction();

            foreach ($validated['items'] as $item) {
                OrderReturn::create([
                    'order_id' => $order->id,
                    'fnb_id' => $item['fnb_id'],
                    'level_id' => $item['level_id'] ?? null,
                    'extra_id' => $item['extra_id'] ?? null,
                    'price' => $item['price'],
                    'quantity' => $item['quantity'],
                    'phone' => $validated['phone'],
                ]);
            }

            DB::commit();

            return ResponseHelper::jsonResponse(200, 'Items returned successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHelper::jsonResponse(500, 'Failed to process return', $e->getMessage());
        }
    }
}
