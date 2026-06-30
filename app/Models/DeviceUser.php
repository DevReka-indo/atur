<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceUser extends Model
{
    protected $table = 'device_users';

    protected $fillable = [
        'device_id',
        'user_id',
    ];
}
