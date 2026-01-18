<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ChargingChart;
use App\Models\Device; // Make sure you have this model

class ChargingChartController extends Controller
{
    // Store Charging Data
    public function store(Request $request)
    {
        $request->validate([
            'charging_date' => 'required|date',
            'device_name' => 'required|string|max:255',
            'device_id' => 'required|string|max:255',
            'device_group' => 'required|string|max:255',
            'charging_start' => 'required',
            'charging_end' => 'required',
        ]);

        ChargingChart::create([
            'charging_date' => $request->charging_date,
            'device_name' => $request->device_name,
            'device_id' => $request->device_id,
            'device_group' => $request->device_group,
            'charging_start' => $request->charging_start,
            'charging_end' => $request->charging_end,
        ]);

        return redirect()->back()->with('success', 'Charging data added successfully!');
    }

    // View All Charging Records
    public function index()
    {
        $chargingCharts = ChargingChart::latest()->get();
        return view('admin.charging_chart', compact('chargingCharts'));
    }

    public function edit($id)
    {
        $chargingChart = ChargingChart::findOrFail($id);
        return view('charging.edit', compact('chargingChart'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'charging_date' => 'required|date',
            'charging_start' => 'required',
            'charging_end' => 'required'
        ]);

        $chargingChart = ChargingChart::findOrFail($id);
        $chargingChart->update($request->all());

        return redirect()->route('device.show', $chargingChart->device_id)
                         ->with('success', 'Charging chart updated successfully');
    }

    // New method to handle barcode scan and auto insert charging chart record
    public function scanOnce($barcode)
    {
        // Find the device by barcode (device_id)
        $device = Device::where('device_id', $barcode)->first();

        if (!$device) {
            return response()->json(['message' => 'Device not found'], 404);
        }

        $startTime = now();
        $endTime = (clone $startTime)->addHours(2);

        // Create new charging chart entry
        ChargingChart::create([
            'charging_date' => $startTime->toDateString(),
            'device_name' => $device->device_name,
            'device_id' => $device->device_id,
            'device_group' => $device->device_group,
            'charging_start' => $startTime->format('H:i:s'),
            'charging_end' => $endTime->format('H:i:s'),
        ]);

        return response()->json([
            'message' => "Charging recorded for {$device->device_name} — Start: {$startTime->format('H:i')}, End: {$endTime->format('H:i')}"
        ]);
    }
    public function autoWeeklyCharging()
{
    $devices = Device::all();

    $now = now();

    foreach ($devices as $device) {
        // Check if a charging record for this device already exists in the current week
        $alreadyExists = ChargingChart::where('device_id', $device->device_id)
            ->whereBetween('charging_date', [$now->startOfWeek()->toDateString(), $now->endOfWeek()->toDateString()])
            ->exists();

        if (!$alreadyExists) {
            $startTime = $now->copy()->setTime(9, 0); // For example, charging start at 9:00 AM
            $endTime = $startTime->copy()->addHours(2);

            ChargingChart::create([
                'charging_date' => $startTime->toDateString(),
                'device_name' => $device->device_name,
                'device_id' => $device->device_id,
                'device_group' => $device->device_group,
                'charging_start' => $startTime->format('H:i:s'),
                'charging_end' => $endTime->format('H:i:s'),
            ]);
        }
    }

    return response()->json(['message' => 'Weekly charging records added/checked successfully']);
}
}
