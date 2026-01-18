<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BmeChart extends Model
{
    use HasFactory;

    protected $fillable = [
        'device_name',
        'device_id',
        'device_group',
        'reason',
        'send_date',
        'receive_date',
    ];
}