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

    public function show($id)
    {
        try {
            // 1. Auth check
            AuthHelper::requireAuth();

            // 2. Ambil order + relasi dasar
            $order = Order::with([
                'details.fnb:id,name,price,image',
            ])->findOrFail($id);

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


    public function kitchen(Request $request)
    {
        try {
            AuthHelper::requireAuth();

            $query = Order::with(['details.fnb:id,name,image'])
                ->where('status', 1)
                ->orderBy('created_at', 'desc');

            $data = FilterHelper::filterAndPaginate($query, $request);

            $data['data'] = collect($data['data'])->map(function ($order) {
                return [
                    'id' => $order->id,
                    'table_id' => $order->table_id,
                    'order_code' => $order->order_code,
                    'customer_name' => $order->customer_name,
                    'created_at' => $order->created_at,
                    'details' => $order->details->map(function ($detail) {
                        return [
                            'quantity' => $detail->quantity,
                            'fnb' => [
                                'name' => $detail->fnb->name ?? null
                            ]
                        ];
                    })
                ];
            });

            return ResponseHelper::jsonResponse(200, 'Kitchen orders fetched successfully', $data);
        } catch (\Exception $e) {
            return ResponseHelper::jsonResponse(500, 'Failed to fetch kitchen orders', $e->getMessage());
        }
    }
}
