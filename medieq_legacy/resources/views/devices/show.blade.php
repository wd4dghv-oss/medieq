@extends('layouts.app')

@section('content')
<div class="container mx-auto p-6 bg-white shadow-lg rounded-lg" x-data="chartManager()">
    
    {{-- 📌 Barcode Scanner Section --}}
    <div class="bg-yellow-100 p-4 rounded-lg mb-6 shadow">
        <h3 class="text-xl font-semibold text-gray-800">Scan Device Barcode</h3>
        <input 
            id="barcodeInput" 
            type="text" 
            placeholder="Scan barcode here" 
            class="border p-3 rounded w-full text-lg"
            autofocus
        >
        <p class="text-sm text-gray-600 mt-1">Scan once → Start time = now, End time = +2 hours</p>
    </div>

    {{-- Existing Device Details --}}
    <h2 class="text-3xl font-bold text-gray-700 mb-6">{{ $device->device_name }} <span class="text-gray-500">(ID: {{ $device->device_id }})</span></h2>

    <div class="bg-gray-100 p-4 rounded-lg mb-6">
        <h3 class="text-xl font-semibold text-gray-800">Device Details</h3>
        <p class="text-gray-600"><strong>Group:</strong> {{ $device->device_group }}</p>
        <p class="text-gray-600"><strong>Model No:</strong> {{ $device->model_no }}</p>
        <p class="text-gray-600"><strong>Serial No:</strong> {{ $device->serial_no }}</p>
    </div>

    {{-- Charging Chart --}}
    <h3 class="text-xl font-semibold text-gray-800 mt-6">Charging Chart</h3>
    @if ($chargingCharts->count() > 0)
        <div class="overflow-x-auto" id="chargingTable">
            <table class="w-full bg-white shadow-md rounded-lg">
                <thead>
                    <tr class="bg-blue-500 text-white">
                        <th class="p-3 text-left">Date</th>
                        <th class="p-3 text-left">Start Time</th>
                        <th class="p-3 text-left">End Time</th>
                        <th class="p-3 text-left">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($chargingCharts as $chart)
                        <tr class="border-b hover:bg-gray-100 transition">
                            <td class="p-3">{{ $chart->charging_date ?? 'N/A' }}</td>
                            <td class="p-3">{{ $chart->charging_start ?? 'N/A' }}</td>
                            <td class="p-3">{{ $chart->charging_end ?? 'N/A' }}</td>
                            <td class="p-3">
                                <button class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded transition"
                                    @click="openChargingModal({{ json_encode($chart) }})">Edit</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <p class="text-red-500 mt-2">No charging data available.</p>
    @endif

    {{-- BME Chart --}}
    <h3 class="text-xl font-semibold text-gray-800 mt-6">BME Chart</h3>
    @if ($bmeCharts->count() > 0)
        <div class="overflow-x-auto">
            <table class="w-full bg-white shadow-md rounded-lg">
                <thead>
                    <tr class="bg-green-500 text-white">
                        <th class="p-3 text-left">Send Date</th>
                        <th class="p-3 text-left">Receive Date</th>
                        <th class="p-3 text-left">Reason</th>
                        <th class="p-3 text-left">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($bmeCharts as $bme)
                        <tr class="border-b hover:bg-gray-100 transition">
                            <td class="p-3">{{ $bme->send_date ?? 'N/A' }}</td>
                            <td class="p-3">{{ $bme->receive_date ?? 'Pending' }}</td>
                            <td class="p-3">{{ $bme->reason ?? 'N/A' }}</td>
                            <td class="p-3">
                                <button class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded transition"
                                    @click="openBmeModal({{ json_encode($bme) }})">Edit</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <p class="text-red-500 mt-2">No BME data available.</p>
    @endif

    {{-- Charging Chart Edit Modal --}}
    {{-- (unchanged) --}}
    
    {{-- BME Chart Edit Modal --}}
    {{-- (unchanged) --}}
</div>

{{-- Alpine.js for Modal + Barcode Script --}}
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('chartManager', () => ({
        chargingModalOpen: false,
        bmeModalOpen: false,
        chargingForm: { id: '', charging_date: '', charging_start: '', charging_end: '' },
        bmeForm: { id: '', send_date: '', receive_date: '', reason: '' },

        openChargingModal(chart) {
            this.chargingForm = { ...chart };
            this.chargingModalOpen = true;
        },

        openBmeModal(chart) {
            this.bmeForm = { ...chart };
            this.bmeModalOpen = true;
        },

        async submitChargingForm() {
            await fetch(`/charging-chart/update/${this.chargingForm.id}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify(this.chargingForm)
            });
            this.chargingModalOpen = false;
            location.reload();
        },

        async submitBmeForm() {
            await fetch(`/bme-chart/update/${this.bmeForm.id}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify(this.bmeForm)
            });
            this.bmeModalOpen = false;
            location.reload();
        }
    }));
});

// 📌 Barcode scanning handler
document.addEventListener('DOMContentLoaded', function () {
    const input = document.getElementById('barcodeInput');
    input.addEventListener('keypress', async function (e) {
        if (e.key === 'Enter') {
            let code = input.value.trim();
            if (code) {
                let res = await fetch(`/charging-scan/${code}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                let data = await res.json();
                alert(data.message);
                location.reload();
                input.value = '';
            }
        }
    });
});
</script>
@endsection
