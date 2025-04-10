<div id="cameraModal" class="fixed inset-0 bg-black bg-opacity-50 flex hidden items-center justify-center">
    <div class="bg-system rounded-lg p-6 w-[800px]">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold">Select Camera</h3>
            <button onclick="closeCameraModal()" class="hover:text-gray-700">
                <i class="fa-solid fa-times"></i>
            </button>
        </div>

        <div class="grid grid-cols-4 gap-2">
            @foreach ($cameras as $camera)
                <div onclick="selectCamera({{ $camera->id }}, '{{ $camera->name }}')"
                    class="flex items-center p-3 rounded-lg hover:bg-gray-300 hover:text-black cursor-pointer border">
                    <i class="fa-solid fa-camera-retro mr-2"></i>
                    <span>{{ $camera->name }}</span>
                </div>
            @endforeach
        </div>
    </div>
</div>
