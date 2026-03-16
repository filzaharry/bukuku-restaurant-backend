<?php

namespace App\Models\Content;

use App\Models\Content\FnbMenu;
use App\Models\Content\Order;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderDetail extends Model
{
    use HasFactory;

    protected $table = 'order_items';

    protected $fillable = [
        'order_id',
        'fnb_id',
        'price',
        'quantity', 
    ];

    // Relation to Order
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    // Relation to Fnb menu
    public function fnb()
    {
        return $this->belongsTo(FnbMenu::class, 'fnb_id');
    }
}
