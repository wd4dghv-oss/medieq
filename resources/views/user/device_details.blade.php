@extends('layouts.app')

@section('content')
<div class="container mx-auto p-6">
    <h2 class="text-2xl font-bold mb-6">Device Details</h2>

    <div class="bg-white p-6 rounded-lg shadow-md mb-6">
        <h3 class="text-xl font-semibold">{{ $device->device_name }} ({{ $device->device_id }})</h3>
        <p><strong>Model No:</strong> {{ $device->model_no }}</p>
        <p><strong>Serial No:</strong> {{ $device->serial_no }}</p>
        <p><strong>Device Group:</strong> {{ $device->device_group }}</p>
    </div>

    <div class="bg-gray-100 p-6 rounded-lg shadow-md mb-6">
        <h3 class="text-xl font-semibold">Charging Chart</h3>
        @if($chargingData->isEmpty())
            <p>No charging data available.</p>
        @else
            <table class="w-full border-collapse border border-gray-300">
                <thead>
                    <tr class="bg-blue-500 text-white">
                        <th class="border p-2">Date</th>
                        <th class="border p-2">Start Time</th>
                        <th class="border p-2">Off Time</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($chargingData as $data)
                        <tr>
                            <td class="border p-2">{{ $data->charging_date }}</td>
                            <td class="border p-2">{{ $data->charging_start }}</td>
                            <td class="border p-2">{{ $data->charging_end }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <div class="bg-gray-100 p-6 rounded-lg shadow-md">
        <h3 class="text-xl font-semibold">BME Chart</h3>
        @if($bmeData->isEmpty())
            <p>No BME data available.</p>
        @else
            <table class="w-full border-collapse border border-gray-300">
                <thead>
                    <tr class="bg-green-500 text-white">
                        <th class="border p-2">Reason</th>
                        <th class="border p-2">Send Date</th>
                        <th class="border p-2">Receive Date</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($bmeData as $data)
                        <tr>
                            <td class="border p-2">{{ $data->reason }}</td>
                            <td class="border p-2">{{ $data->send_date }}</td>
                            <td class="border p-2">{{ $data->receive_date ?? 'Not Received' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
@endsection
