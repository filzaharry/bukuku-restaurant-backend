<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserAccess extends Model
{
    use HasFactory;

    protected $table = 'user_access';
    
    protected $fillable = [
        'id', 
        'name', 
        'menu_id', 
        'level_id', 
        'permission', 
        'status',
        'created_by_id', 
        'updated_by_id', 
        'deleted_by_id',
        'created_at',
        'updated_at',
    ];

    // public function level() 
    // {
    //     return $this->belongsTo(UserLevel::class);
    // }
}
