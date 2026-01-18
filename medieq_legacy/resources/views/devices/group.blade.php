@extends('layouts.app')

@section('content')
<div class="container mx-auto p-4">
    <h2 class="text-2xl font-semibold mb-4">{{ $group }} Devices</h2>
    

    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @foreach ($devices as $device)
            <a href="{{ route('device.show', $device->device_id) }}" 
               class="block bg-gray-200 p-4 rounded-lg shadow-md hover:bg-gray-300">
                <strong>{{ $device->device_name }}</strong> (ID: {{ $device->device_id }})
            </a>
        @endforeach
    </div>
</div>


@endsection
