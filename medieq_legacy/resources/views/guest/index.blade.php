@extends('layouts.app')

@section('content')
<div class="container mx-auto p-4">
    <h2 class="text-2xl font-semibold mb-4">Device Groups</h2>
    
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
        @foreach($groups as $group)
            <a href="{{ route('guest.group', $group) }}" 
               class="block bg-blue-500 text-white p-3 rounded-lg text-center shadow-md hover:bg-blue-700">
                {{ $group }}
            </a>
        @endforeach
    </div>
</div>
@endsection
