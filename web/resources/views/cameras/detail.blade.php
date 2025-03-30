@extends('layouts.app')

@section('content')
    <div class="grid grid-cols-12 h-[80vh] gap-2">
        <div class="col-span-2 h-full bg-white rounded-md shadow p-3 relative">
            <button class="absolute top-1 right-2">
                <svg class="icon">
                    <use xlink:href="node_modules/@coreui/icons/sprites/free.svg#cil-menu"></use>
                </svg>
            </button>
            <h4>Location</h4>
            @foreach ($areas as $area)
                <div>
                    {{ $area->ten }}
                    @foreach ($area->positions as $position)
                        <br>
                        <div class="pl-3">{{ $position->ten }}</div>
                    @endforeach
                </div>
            @endforeach
        </div>

        <div class="col-span-6 h-full">
            <div class="grid grid-rows-10 gap-2 h-full">
                <div class="row-span-7 bg-gray-200 flex items-center justify-center ">
                    <svg class="h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                    {{-- <canvas class="!w-full !h-full" id="canvas-{{ $camera['id'] }}"></canvas> --}}
                </div>

                <div class="row-span-3 bg-gray-200 p-3">
                    <h4>Metadata</h4>
                </div>
            </div>
        </div>

        <div class="col-span-4 h-full bg-white rounded-md shadow relative">
            <button class="absolute top-1 left-2">
                <svg class="icon">
                    <use xlink:href="node_modules/@coreui/icons/sprites/free.svg#cil-menu"></use>
                </svg>
            </button>
        </div>
    </div>
@endsection
