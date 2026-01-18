<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BMEChart;

class BMEChartController extends Controller
{
    public function show($device_id)
    {
        $bmeEntries = BMEChart::where('device_id', $device_id)->get();
        return view('charts.bme', compact('bmeEntries', 'device_id'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'device_id' => 'required|exists:devices,id',
            'date' => 'required|date',
            'reason' => 'required',
            'send_date' => 'required|date',
            'receive_date' => 'nullable|date'
        ]);

        BMEChart::create($request->all());
        return redirect()->back()->with('success', 'BME entry added successfully');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'device_name' => 'required',
            'device_group' => 'required',
            'reason' => 'required',
            'send_date' => 'required|date',
            'receive_date' => 'nullable|date'
        ]);

        $entry = BMEChart::findOrFail($id);
        $entry->update($request->all());
        return redirect()->back()->with('success', 'BME entry updated successfully');
    }

    public function destroy($id)
    {
        BMEChart::findOrFail($id)->delete();
        return redirect()->back()->with('success', 'BME entry deleted successfully');
    }
    public function edit($id)
    {
        $bmeChart = BMEChart::findOrFail($id);
        return view('bme.edit', compact('bmeChart'));
    }

   

}