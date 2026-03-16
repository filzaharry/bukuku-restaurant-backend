<?php

namespace App\Models\Content;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FnbCategory extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'master_fnb_category';

    protected $fillable = [
        'name',
        'image',
        'status',
        'created_by_id',
        'updated_by_id',
        'deleted_by_id',
        'deleted_at',
    ];


    public function fnbs()
    {
        return $this->hasMany(FnbMenu::class, 'category_id')->whereNull('deleted_by_id');
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
