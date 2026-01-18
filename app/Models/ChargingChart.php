<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChargingChart extends Model
{
    use HasFactory;
    protected $fillable = [
        'charging_date',
        'device_name',
        'device_id',
        'device_group',
        'charging_start',
        'charging_end',
    ];
}
