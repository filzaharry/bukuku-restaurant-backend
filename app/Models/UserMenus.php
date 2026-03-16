<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserMenus extends Model
{
    use HasFactory;

    protected $table = 'user_menus';

    protected $fillable = [
        'id',
        'name',
        'media',
        'level',
        'permission',
        'status',
        'is_parent',
        'url',
        'master',
        'sort_sub',
        'sort_master',
        'icon_id',
        'created_by_id',
        'updated_by_id',
        'deleted_by_id',
        'created_at',
        'updated_at',
    ];

    public function icon()
    {
        return $this->belongsTo(MasterIcons::class, 'icon_id');
    }
}
