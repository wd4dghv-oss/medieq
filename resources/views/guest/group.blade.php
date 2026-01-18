@extends('layouts.app')

@section('content')
<div class="container mx-auto p-4">
    <h2 class="text-2xl font-semibold mb-4">{{ $group }} Devices</h2>

    <ul class="list-disc ml-6">
        @foreach($devices as $device)
            <li>
                <a href="{{ route('guest.device', $device->device_id) }}" class="text-blue-500">
                    {{ $device->device_name }} (ID: {{ $device->device_id }})
                </a>
            </li>
        @endforeach
    </ul>
</div>
@endsection
