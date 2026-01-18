@extends('layouts.app')

@section('content')
<div class="container mx-auto p-6">
    <h2 class="text-2xl font-bold mb-6">Admin Dashboard</h2>

    <!-- Device Group Buttons -->
    <div class="container mx-auto p-4">
        <h2 class="text-2xl font-semibold mb-4">Device Management</h2>
    
        <!-- Device Group Buttons -->
        <div class="mb-6">
            <h3 class="text-xl font-semibold mb-4">Device Groups</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                @foreach(['Cardiac Monitor', 'Infusion Pump', 'Syringe Pump', 'CPAP', 'Scan Machine', 'Pulse Oximeter', 'Other'] as $group)
                    <button 
                        class="bg-blue-500 text-white p-2 rounded text-center block w-full sm:w-auto"
                        onclick="toggleDeviceList('{{ Str::slug($group) }}')">
                        {{ $group }}
                    </button>
                @endforeach
            </div>
        </div>
    
        <!-- Device List for Each Group -->
        @foreach(['Cardiac Monitor', 'Infusion Pump', 'Syringe Pump', 'CPAP', 'Scan Machine', 'Pulse Oximeter', 'Other'] as $group)
            <div id="device-list-{{ Str::slug($group) }}" class="device-list bg-white p-6 rounded-lg shadow-md mb-6 hidden">
                <h3 class="text-xl font-semibold mb-4">{{ $group }} Devices</h3>
                
                @php
                    $devices = App\Models\Device::where('device_group', $group)->get();
                @endphp
    
                @if($devices->isEmpty())
                    <p>No devices available in this group.</p>
                @else
                    <ul>
                        @foreach($devices as $device)
                            <li>
                                <a href="{{ route('devices.show', $device->id) }}" class="text-blue-500 hover:underline">
                                    {{ $device->device_name }} ({{ $device->device_id }})
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endforeach
    </div>
    
    <!-- JavaScript to Show/Hide Device Groups -->
    <script>
        function toggleDeviceList(group) {
            document.querySelectorAll('.device-list').forEach(div => div.classList.add('hidden'));
            document.getElementById('device-list-' + group).classList.toggle('hidden');
        }
    </script>

    <!-- Add Device Button -->
    <button onclick="openModal('deviceModal')" class="bg-green-500 text-white px-4 py-2 rounded-md">Add Device</button>

    <!-- Charging Chart Button -->
    <button onclick="openModal('ChargingChartModal')" class="bg-yellow-500 text-white px-4 py-2 rounded mb-4">
        Add Charging Data
    </button>


    <!-- BME Chart Button -->
    <button onclick="openModal('BMEChartModal')" class="bg-purple-500 text-white px-4 py-2 rounded-md ml-2">Add BME Data</button>

    <!-- Add Device Modal -->
    <div id="deviceModal" class="fixed inset-0 flex items-center justify-center bg-gray-800 bg-opacity-50 hidden">
        <div class="bg-white p-6 rounded-lg shadow-md w-96">
            <h3 class="text-xl font-semibold mb-4">Add New Device</h3>
            <form action="{{ route('admin.store_device') }}" method="POST">
                @csrf
                <div class="mb-4">
                    <label for="device_name" class="block text-sm font-semibold">Device Name</label>
                    <input type="text" name="device_name" id="device_name" class="w-full p-2 border rounded-md" required>
                </div>
                <div class="mb-4">
                    <label for="device_id" class="block text-sm font-semibold">Device ID</label>
                    <input type="text" name="device_id" id="device_id" class="w-full p-2 border rounded-md" required>
                </div>
                <div class="mb-4">
                    <label for="device_group" class="block text-sm font-semibold">Device Group</label>
                    <select name="device_group" id="device_group" class="w-full p-2 border rounded-md" required>
                        <option value="Cardiac Monitor">Cardiac Monitor</option>
                        <option value="Infusion Pump">Infusion Pump</option>
                        <option value="Syringe Pump">Syringe Pump</option>
                        <option value="CPAP">CPAP</option>
                        <option value="Scan Machine">Scan Machine</option>
                        <option value="Pulse Oximeter">Pulse Oximeter</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label for="model_no" class="block text-sm font-semibold">Model No</label>
                    <input type="text" name="model_no" id="model_no" class="w-full p-2 border rounded-md" required>
                </div>
                <div class="mb-4">
                    <label for="serial_no" class="block text-sm font-semibold">Serial No</label>
                    <input type="text" name="serial_no" id="serial_no" class="w-full p-2 border rounded-md" required>
                </div>
                <div class="mb-4">
                    <label for="date_added" class="block text-sm font-semibold">Date Added</label>
                    <input type="date" name="date_added" id="date_added" class="w-full p-2 border rounded-md" required>
                </div>
                <div class="flex justify-between">
                    <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-md">Submit</button>
                    <button type="button" onclick="closeModal('deviceModal')" class="bg-red-500 text-white px-4 py-2 rounded-md">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    <!-- Charging Chart Modal -->
    <div id="ChargingChartModal" class="fixed inset-0 flex items-center justify-center bg-gray-800 bg-opacity-50 hidden">
        <div class="bg-white p-6 rounded-lg shadow-md w-96">
            <h3 class="text-xl font-semibold mb-4">Add Charging Data</h3>
            <form action="{{ route('admin.store_charging') }}" method="POST">
                @csrf
                <div class="mb-4">
                    <label class="block text-sm font-semibold">Date</label>
                    <input type="date" name="charging_date" class="w-full p-2 border rounded-md" required>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-semibold">Device Name</label>
                    <input type="text" name="device_name" class="w-full p-2 border rounded-md" required>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-semibold">Device ID</label>
                    <input type="text" name="device_id" class="w-full p-2 border rounded-md" required>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-semibold">Device Group</label>
                    <select name="device_group" class="w-full p-2 border rounded-md" required>
                        <option value="Cardiac Monitor">Cardiac Monitor</option>
                        <option value="Infusion Pump">Infusion Pump</option>
                        <option value="Syringe Pump">Syringe Pump</option>
                        <option value="CPAP">CPAP</option>
                        <option value="Scan Machine">Scan Machine</option>
                        <option value="Pulse Oximeter">Pulse Oximeter</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-semibold">Charging Start Time</label>
                    <input type="time" name="charging_start" class="w-full p-2 border rounded-md" required>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-semibold">Charging Off Time</label>
                    <input type="time" name="charging_end" class="w-full p-2 border rounded-md" required>
                </div>
                <div class="flex justify-between">
                    <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-md">Submit</button>
                    <button type="button" onclick="closeModal('ChargingChartModal')" class="bg-red-500 text-white px-4 py-2 rounded-md">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    <!-- BME Chart Modal -->
<div id="BMEChartModal" class="fixed inset-0 flex items-center justify-center bg-gray-800 bg-opacity-50 hidden">
    <div class="bg-white p-6 rounded-lg shadow-md w-96">
        <h3 class="text-xl font-semibold mb-4">Add BME Data</h3>
        <form action="{{ route('admin.store_bme') }}" method="POST">
            @csrf
            <div class="mb-4">
                <label for="bme_device_name" class="block text-sm font-semibold">Device Name</label>
                <input type="text" name="device_name" id="bme_device_name" class="w-full p-2 border rounded-md" required>
            </div>
            <div class="mb-4">
                <label for="bme_device_id" class="block text-sm font-semibold">Device ID</label>
                <input type="text" name="device_id" id="bme_device_id" class="w-full p-2 border rounded-md" required>
            </div>
            <div class="mb-4">
                <label for="bme_device_group" class="block text-sm font-semibold">Device Group</label>
                <select name="device_group" id="bme_device_group" class="w-full p-2 border rounded-md" required>
                    <option value="Cardiac Monitor">Cardiac Monitor</option>
                    <option value="Infusion Pump">Infusion Pump</option>
                    <option value="Syringe Pump">Syringe Pump</option>
                    <option value="CPAP">CPAP</option>
                    <option value="Scan Machine">Scan Machine</option>
                    <option value="Pulse Oximeter">Pulse Oximeter</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            <div class="mb-4">
                <label for="reason" class="block text-sm font-semibold">Reason</label>
                <input type="text" name="reason" id="reason" class="w-full p-2 border rounded-md" required>
            </div>
            <div class="mb-4">
                <label for="send_date" class="block text-sm font-semibold">Send Date</label>
                <input type="date" name="send_date" id="send_date" class="w-full p-2 border rounded-md" required>
            </div>
            <div class="mb-4">
                <label for="receive_date" class="block text-sm font-semibold">Receive Date</label>
                <input type="date" name="receive_date" id="receive_date" class="w-full p-2 border rounded-md">
            </div>
            <div class="flex justify-between">
                <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-md">Submit</button>
                <button type="button" onclick="closeModal('BMEChartModal')" class="bg-red-500 text-white px-4 py-2 rounded-md">Cancel</button>
            </div>
        </form>
    </div>
        
    </div>
</div>
</div>

<script>
    function toggleDeviceList(group) {
        const deviceList = document.getElementById('device-list-' + group);
        const allLists = document.querySelectorAll('.device-list');
        allLists.forEach(list => list.classList.add('hidden'));
        deviceList.classList.toggle('hidden');
    }

    function openModal(modalId) {
        document.getElementById(modalId).classList.remove('hidden');
    }

    function closeModal(modalId) {
        document.getElementById(modalId).classList.add('hidden');
    }
</script>
@endsection