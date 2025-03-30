<x-app-layout add_class='mt-0'>
    @section('content')
        <div class="flex flex-col">
            {{-- Statistical --}}
            <div class="card-group">
                {{-- Total Cam --}}
                <div class="card">
                    <div class="card-body p-2">
                        <div class="flex items-center justify-between">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                                class="bi bi-camera-reels-fill text-[#3399FF]" viewBox="0 0 16 16">
                                <path d="M6 3a3 3 0 1 1-6 0 3 3 0 0 1 6 0" />
                                <path d="M9 6a3 3 0 1 1 0-6 3 3 0 0 1 0 6" />
                                <path
                                    d="M9 6h.5a2 2 0 0 1 1.983 1.738l3.11-1.382A1 1 0 0 1 16 7.269v7.462a1 1 0 0 1-1.406.913l-3.111-1.382A2 2 0 0 1 9.5 16H2a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2z" />
                            </svg>
                            <h5 class="mb-0">{{ $stats['total'] }}</h5>
                            <h5 class="mb-0">{{ __('messages.total_cameras') }}</h5>
                        </div>
                        <div class="progress progress-thin mt-2 mb-0">
                            <div class="progress-bar bg-info" role="progressbar" style="width: 100%" aria-valuenow="25"
                                aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                </div>
                {{-- Activated Cam --}}
                <div class="card">
                    <div class="card-body p-2">
                        <div class="flex item-center justify-between">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                                class="bi bi-camera-video-fill text-[#1B9E3E]" viewBox="0 0 16 16">
                                <path fill-rule="evenodd"
                                    d="M0 5a2 2 0 0 1 2-2h7.5a2 2 0 0 1 1.983 1.738l3.11-1.382A1 1 0 0 1 16 4.269v7.462a1 1 0 0 1-1.406.913l-3.111-1.382A2 2 0 0 1 9.5 13H2a2 2 0 0 1-2-2z" />
                            </svg>
                            <h5 class="mb-0">{{ $stats['active'] }}</h5>
                            <h5 class="mb-0">{{ __('messages.activated_cameras') }}</h5>
                        </div>
                        <div class="progress progress-thin mt-2 mb-0">
                            @if ($stats['active'] !== 0)
                                <div class="progress-bar bg-success" role="progressbar"
                                    style="width: {{ round(($stats['active'] / $stats['total']) * 100, 1) }}%"
                                    aria-valuenow="25" aria-valuemin="0" aria-valuemax="100"></div>
                            @else
                                <div class="progress-bar bg-success" role="progressbar" style="width: 0%" aria-valuenow="25"
                                    aria-valuemin="0" aria-valuemax="100"></div>
                            @endif
                        </div>
                    </div>
                </div>
                {{-- Inactivated Cam --}}
                <div class="card">
                    <div class="card-body p-2">
                        <div class="flex item-center justify-between">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                                class="bi bi-camera-video-off-fill text-[#E55353]" viewBox="0 0 16 16">
                                <path fill-rule="evenodd"
                                    d="M10.961 12.365a2 2 0 0 0 .522-1.103l3.11 1.382A1 1 0 0 0 16 11.731V4.269a1 1 0 0 0-1.406-.913l-3.111 1.382A2 2 0 0 0 9.5 3H4.272zm-10.114-9A2 2 0 0 0 0 5v6a2 2 0 0 0 2 2h5.728zm9.746 11.925-10-14 .814-.58 10 14z" />
                            </svg>
                            <h5 class="mb-0">{{ $stats['offline'] }}</h5>
                            <h5 class="mb-0">{{ __('messages.inactivated_cameras') }}</h5>
                        </div>
                        <div class="progress progress-thin mt-2 mb-0">
                            @if ($stats['offline'] !== 0)
                                <div class="progress-bar bg-danger" role="progressbar"
                                    style="width: {{ round(($stats['offline'] / $stats['total']) * 100, 1) }}%"
                                    aria-valuenow="25" aria-valuemin="0" aria-valuemax="100"></div>
                            @else
                                <div class="progress-bar bg-danger" role="progressbar" style="width: 0%" aria-valuenow="25"
                                    aria-valuemin="0" aria-valuemax="100"></div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Cameras and Events --}}
            <div class="h-full grid grid-cols-12 flex-1 overflow-hidden border">
                {{-- Camera Show --}}
                <div class="col-span-10 h-full">
                    <div id="cameraContainer" class="h-full overflow-hidden flex items-center justify-center">
                        <img id="cameraFrame" src="/images/blank.png" class="w-full h-[81.5vh] object-contain"></img>
                    </div>
                </div>

                {{-- Events --}}
                <div class="flex flex-col justify-between col-span-2 border h-full w-full p-2 overflow-y-auto">
                    <div>
                        <div class="flex justify-between items-center">
                            <h5 class="text-lg font-bold mb-0">{{ __('messages.events') }}</h5>
                            <button id="selectCameras" class="btn btn-primary">
                                <i class="fa-solid fa-screwdriver-wrench"></i>
                            </button>
                        </div>

                        <hr class="m-2">
                        @foreach ($events as $event)
                            <a href="{{ url('/events') }}?selected={{ $event->id }}&page={{ $eventsWithPage->firstWhere('id', $event->id)['page'] }}"
                                class="no-underline">
                                <div class="card mb-2">
                                    <img src="" data-url="{{ $event->url }}" class="card-img-top dynamic-image"
                                        alt="Events">
                                    <div class="card-body p-2">
                                        <div class="flex justify-between items-center mb-2">
                                            <span
                                                class="text-sm font-semibold">{{ optional($event->camera)->name ?? 'Deleted camera' }}</span>
                                            <p class="text-xs mb-0">{{ $event->start_error_time }}</p>
                                        </div>
                                        <p class="text-xs mb-0">
                                            <b>Description: </b>Detect an object in restricted zone
                                        </p>
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <!-- Camera List Popup -->
        <div id="cameraListPopup" class="hidden fixed inset-0 bg-black bg-opacity-50 items-center justify-center">
            <div class="dash-theme top-[25%] left-[35%] p-4 rounded-lg w-1/3">
                <!-- Grid Layout Selection -->
                <div class="mb-4 rounded">
                    <h5 class="text-md font-semibold mb-3">{{ __('messages.grid_layout') }}</h5>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block mb-1">{{ __('messages.rows') }}</label>
                            <input type="number" id="gridRows" min="1" value="1" class="form-control">
                        </div>
                        <div>
                            <label class="block mb-1">{{ __('messages.columns') }}</label>
                            <input type="number" id="gridColumns" min="1" value="1" class="form-control">
                        </div>
                    </div>
                    <div id="gridWarning" class="hidden mt-2 text-sm text-red-500">
                        {{ __('messages.grid_layout_warning') }}
                    </div>
                </div>
                <!-- Select Camera Selection -->
                <div class="mb-4 rounded max-h-[40vh] overflow-y-auto">
                    <div class="flex items-center justify-between mb-3">
                        <h5 class="text-md font-semibold mb-0">{{ __('messages.select_camera') }}</h5>
                        <div class="space-x-2">
                            <button id="selectAllCameras" class="px-3 py-1 bg-blue-500 text-white rounded text-sm">
                                <i class="fa-solid fa-check"></i>
                            </button>
                            <button id="deselectAllCameras" class="px-3 py-1 bg-gray-500 text-white rounded text-sm">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>
                    </div>
                    <div id="cameraList" class="grid grid-cols-3">
                        @foreach ($cameras as $camera)
                            <div class="camera-item flex items-center p-2 ">
                                <input type="checkbox" class="camera-checkbox mr-3 cursor-pointer"
                                    data-camera-id="{{ $camera->id }}">
                                <span class="mr-3"><i class="fa-solid fa-video"></i></span>
                                {{ $camera->name }}
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="mt-4 flex justify-between">
                    <button id="applySelection"
                        class="px-4 py-2 bg-blue-500 text-white rounded">{{ __('messages.apply') }}</button>
                    <button id="closeCameraList"
                        class="px-4 py-2 bg-red-500 text-white rounded">{{ __('messages.close') }}</button>
                </div>
            </div>
        </div>
        <script>
            window.userPreferences = @json($userPreferences);
        </script>
        <script type="module" src="js/dashboard-event.js"></script>
        <script src="js/dashboard.js"></script>
    @endsection
</x-app-layout>
