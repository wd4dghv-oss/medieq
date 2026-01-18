<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Device;
use App\Models\ChargingChart;
use App\Models\BmeChart;

class AdminController extends Controller
{
    // Show admin dashboard
    public function index()
    {
        return view('admin.dashboard');
    }

    // Store a new device
    public function storeDevice(Request $request)
    {
        $request->validate([
            'device_name' => 'required|string',
            'device_id' => 'required|string',
            'device_group' => 'required|string',
            'model_no' => 'required|string',
            'serial_no' => 'required|string',
            'date_added' => 'required|date',
        ]);

        Device::create([
            'device_name' => $request->device_name,
            'device_id' => $request->device_id,
            'device_group' => $request->device_group,
            'model_no' => $request->model_no,
            'serial_no' => $request->serial_no,
            'date_added' => $request->date_added,
        ]);

        return redirect()->route('admin.index');
    }

    // Store charging data
    
    // Store BME data
    public function storeBme(Request $request)
    {
        $request->validate([
            'device_name' => 'required|string',
            'device_id' => 'required|string',
            'device_group' => 'required|string',
            'reason' => 'required|string',
            'send_date' => 'required|date',
            'receive_date' => 'nullable|date',
        ]);

        BmeChart::create([
            'device_name' => $request->device_name,
            'device_id' => $request->device_id,
            'device_group' => $request->device_group,
            'reason' => $request->reason,
            'send_date' => $request->send_date,
            'receive_date' => $request->receive_date,
        ]);

        return redirect()->route('admin.index');
    }
}
