<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Device;
use App\Models\ChargingChart;
use App\Models\BMEChart;

class UserDashboardController extends Controller
{
    public function index()
    {
        $deviceGroups = ['Cardiac Monitor', 'Infusion Pump', 'Syringe Pump', 'CPAP', 'Scan Machine', 'Pulse Oximeter', 'Other'];
        return view('user.dashboard', compact('deviceGroups'));
    }

    public function showDevice($id)
    {
        $device = Device::findOrFail($id);
        $chargingData = ChargingChart::where('device_id', $device->device_id)->get();
        $bmeData = BMEChart::where('device_id', $device->device_id)->get();

        return view('user.device_details', compact('device', 'chargingData', 'bmeData'));
    }
}