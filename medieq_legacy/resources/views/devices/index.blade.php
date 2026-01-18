@extends('layouts.app')

@section('content')
<div class="container mx-auto p-4">
    <h2 class="text-2xl font-semibold mb-4">Device Groups</h2>
    <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
        @foreach ($devices as $device)
            <a href="{{ route('device.group', $device->device_group) }}" 
               class="block bg-blue-500 text-white p-4 rounded-lg text-center">
                {{ $device->device_group }}
            </a>
        @endforeach
    </div>
</div>
@endsection
