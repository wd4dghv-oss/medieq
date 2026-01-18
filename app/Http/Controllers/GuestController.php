<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Device;
use App\Models\ChargingChart;
use App\Models\BmeChart;

class GuestController extends Controller
{
    public function index()
    {
        $groups = ['Cardiac Monitor', 'Infusion Pump', 'Syringe Pump', 'CPAP', 'Scan Machine', 'Pulse Oximeter', 'Other'];
        return view('guest.index', compact('groups'));
    }

    public function showGroup($group)
    {
        $devices = Device::where('device_group', $group)->get();
        return view('guest.group', compact('group', 'devices'));
    }

    public function showDevice($device)
    {
        $device = Device::where('device_id', $device)->firstOrFail();
        $chargingCharts = ChargingChart::where('device_id', $device->device_id)->get();
        $bmeCharts = BmeChart::where('device_id', $device->device_id)->get();
        
        return view('guest.device', compact('device', 'chargingCharts', 'bmeCharts'));
    }
}
