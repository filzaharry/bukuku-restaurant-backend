<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserDevice extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'firebase_id',
        'device_imei',
        'device_name',
        'device_os',
        'device_platform',
        'app_version',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
