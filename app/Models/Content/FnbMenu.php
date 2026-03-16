<?php

namespace App\Models\Content;

use App\Models\Pivot\FnbMenuDelivery;
use App\Models\Pivot\FnbMenuExtra;
use App\Models\Pivot\FnbMenuLevel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FnbMenu extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'master_fnb_menu';

    protected $fillable = [
        'name',
        'description',
        'price',
        'image',
        'category_id',
        'status',
        'created_by_id',
        'updated_by_id',
        'deleted_by_id',
        'deleted_at',
    ];

    public function category()
    {
        return $this->belongsTo(FnbCategory::class, 'category_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }

    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by_id');
    }
}
