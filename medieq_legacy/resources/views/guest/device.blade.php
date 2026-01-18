@extends('layouts.app')

@section('content')
<div class="container mx-auto p-4">
    <h2 class="text-2xl font-semibold mb-4">{{ $device->device_name }} (ID: {{ $device->device_id }})</h2>

    <div class="bg-white p-6 rounded-lg shadow-md mb-6">
        <h3 class="text-xl font-semibold">{{ $device->device_name }} ({{ $device->device_id }})</h3>
        <p><strong>Model No:</strong> {{ $device->model_no }}</p>
        <p><strong>Serial No:</strong> {{ $device->serial_no }}</p>
        <p><strong>Device Group:</strong> {{ $device->device_group }}</p>
    </div>
    {{-- Charging Chart --}}
    <h3 class="text-xl font-semibold mt-6">Charging Chart</h3>
    @if ($chargingCharts->isEmpty())
        <p class="text-red-500">No charging data available.</p>
    @else
        <table class="w-full border-collapse border border-gray-300">
            <thead>
                <tr class="bg-blue-500 text-white">
                    <th class="border p-2">Date</th>
                    <th class="border p-2">Start Time</th>
                    <th class="border p-2">End Time</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($chargingCharts as $chart)
                    <tr>
                        <td class="border p-2">{{ $chart->charging_date }}</td>
                        <td class="border p-2">{{ $chart->charging_start }}</td>
                        <td class="border p-2">{{ $chart->charging_end }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    {{-- BME Chart --}}
    <h3 class="text-xl font-semibold mt-6">BME Chart</h3>
    @if ($bmeCharts->isEmpty())
        <p class="text-red-500">No BME data available.</p>
    @else
        <table class="w-full border-collapse border border-gray-300">
            <thead>
                <tr class="bg-green-500 text-white">
                    <th class="border p-2">Send Date</th>
                    <th class="border p-2">Receive Date</th>
                    <th class="border p-2">Reason</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($bmeCharts as $bme)
                    <tr>
                        <td class="border p-2">{{ $bme->send_date }}</td>
                        <td class="border p-2">{{ $bme->receive_date ?? 'Pending' }}</td>
                        <td class="border p-2">{{ $bme->reason }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection
