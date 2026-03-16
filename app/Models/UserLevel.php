<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserLevel extends Model
{
    use HasFactory;

    protected $table = 'user_levels';

    protected $fillable = [
        'id', 
        'name', 
        'status', 
        'created_by_id', 
        'updated_by_id', 
        'deleted_by_id',
        'created_at',
        'updated_at',
    ];
    
    // Jika Anda ingin menghubungkan ke relasi 'users'
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