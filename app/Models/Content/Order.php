<?php

namespace App\Models\Content;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'orders';

    protected $fillable = [
        'order_code',
        'table_id',
        'customer_name',
        'customer_phone',
        'subtotal',
        'tax',
        'total',
        'status',
        'created_by_id',
        'updated_by_id',
        'deleted_by_id',
        'deleted_at',
    ];

    public function details()
    {
        return $this->hasMany(OrderDetail::class, 'order_id');
    }

    public function table()
    {
        return $this->belongsTo(FnbTable::class, 'table_id');
    }

    // Relation to user who created
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    // Relation to user who updated
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }

    // Relation to user who deleted
    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by_id');
    }
}
