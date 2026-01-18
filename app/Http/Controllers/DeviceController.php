<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Device;
use App\Models\ChargingChart;
use App\Models\BMEChart;

class DeviceController extends Controller
{
    public function index()
    {
       
        $devices = Device::select('device_group')->distinct()->get();
        return view('admin.dashboard', compact('devices'));
    }

    public function showGroup($group)
    {
        $devices = Device::where('device_group', $group)->get();
        return view('devices.group', compact('devices', 'group'));
    }

    public function show($id)
    {
        // Fetch the correct device
        $device = Device::findOrFail($id);

        // Fetch Charging and BME Chart data for this device
        $chargingCharts = ChargingChart::where('device_id', $device->device_id)->get();
        $bmeCharts = BMEChart::where('device_id', $device->device_id)->get();

        return view('devices.show', compact('device', 'chargingCharts', 'bmeCharts'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'device_name' => 'required',
            'device_id' => 'required|unique:devices',
            'device_group' => 'required',
            'model_no' => 'required',
            'serial_no' => 'required'
        ]);

        Device::create($request->all());
        return redirect()->route('admin.dashboard')->with('success', 'Device added successfully');
    }

    public function destroy($id)
    {
        Device::findOrFail($id)->delete();
        return redirect()->route('admin.dashboard')->with('success', 'Device removed successfully');
    }
}
