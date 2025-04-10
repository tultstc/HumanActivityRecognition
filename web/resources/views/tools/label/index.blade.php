@extends('layouts.app')

@section('content')
    <div class="h-[85vh] grid grid-cols-12">
        {{-- Tools Bar --}}
        <div class="col-span-1 border">
            <div class="grid grid-rows-7 p-2 gap-4 h-full">
                <div onclick="openCameraModal()"
                    class="flex flex-col items-center justify-center hover:bg-gray-300 hover:text-black rounded-md cursor-pointer">
                    <i class="fa-solid fa-camera-retro text-[22px]"></i>
                    <p class="mb-1">Cameras</p>
                    <p class="mb-0">(C)</p>
                </div>
                <div onclick="openDirectoryModal()"
                    class="flex flex-col items-center justify-center hover:bg-gray-300 hover:text-black rounded-md cursor-pointer">
                    <i class="fa-regular fa-folder-open text-[22px]"></i>
                    <p class="mb-1">Change Save Dir</p>
                    <p class="mb-0">(D)</p>
                </div>
                <div onclick="zoomIn()"
                    class="flex flex-col items-center justify-center hover:bg-gray-300 hover:text-black rounded-md cursor-pointer">
                    <i class="fa-solid fa-magnifying-glass-plus text-[22px]"></i>
                    <p class="mb-1">Zoom In</p>
                    <p class="mb-0">(+)</p>
                </div>
                <div onclick="zoomOut()"
                    class="flex flex-col items-center justify-center hover:bg-gray-300 hover:text-black rounded-md cursor-pointer">
                    <i class="fa-solid fa-magnifying-glass-minus text-[22px]"></i>
                    <p class="mb-1">Zoom Out</p>
                    <p class="mb-0">(-)</p>
                </div>
                <div onclick="handleNext()"
                    class="flex flex-col items-center justify-center hover:bg-gray-300 hover:text-black rounded-md cursor-pointer">
                    <i class="fa-solid fa-circle-right text-[22px]"></i>
                    <p class="mb-1">Next</p>
                    <p class="mb-0">(N)</p>
                </div>
                <div onclick="detectPose()"
                    class="flex flex-col items-center justify-center hover:bg-gray-300 hover:text-black rounded-md cursor-pointer">
                    <i class="fa-solid fa-brain text-[22px]"></i>
                    <p class="mb-1">Label</p>
                    <p class="mb-0">(L)</p>
                </div>
                <div onclick="handleSave()"
                    class="flex flex-col items-center justify-center hover:bg-gray-300 hover:text-black rounded-md cursor-pointer">
                    <i class="fa-solid fa-floppy-disk text-[22px]"></i>
                    <p class="mb-1">Save</p>
                    <p class="mb-0">(S)</p>
                </div>
            </div>
        </div>
        {{-- Image --}}
        <div class="col-span-8 border overflow-hidden">
            <img id="currentFrame" src="http://localhost:15440/get_snapshot/1" alt="Current Frame"
                class="w-full h-full object-contain">
        </div>
        {{-- Label Information --}}
        <div class="grid grid-rows-12 col-span-3 border">
            <div class="row-span-5 border p-2">
                <p class="mb-2">Box Labels</p>
                <div class="mb-3">
                    <input type="text" id="actionLabel" placeholder="Enter action label"
                        class="w-full p-2 border rounded-md form-control">
                    <div id="labelIndex" class="text-sm text-gray-600 mt-1"></div>
                </div>
                <div class="mb-3">
                    <select id="datasetType" class="w-full p-2 border rounded-md form-control">
                        <option value="train">Training Set</option>
                        <option value="val">Validation Set</option>
                        <option value="test">Test Set</option>
                    </select>
                </div>
                <div class="flex items-center gap-2 p-2 border rounded-md w-full hover:bg-gray-300 hover:text-black  cursor-pointer mb-2"
                    onclick="updateLabel()">
                    <i class="fa-solid fa-pen-to-square"></i>
                    <p class="mb-0">Update Label</p>
                </div>
                <div class="border rounded-md p-2">
                    <p class="font-semibold mb-2">Current Label Mappings:</p>
                    <div id="labelMappings" class="text-sm overflow-y-auto max-h-[40px]"></div>
                </div>
            </div>
            <div class="row-span-7 border p-2">
                <div class="row">
                    <div class="col-12">
                        <p class="">Key Points</p>
                        <div class="max-h-[400px] overflow-y-auto">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Point</th>
                                        <th>X</th>
                                        <th>Y</th>
                                        <th>Confidence</th>
                                    </tr>
                                </thead>
                                <tbody id="keypointsTable">
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('tools.label.partials.camera-modal')

    <!-- Directory Selection Modal -->
    <div id="directoryModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
        <div class="bg-system rounded-lg p-6 w-[500px]">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold">Select Save Directory</h3>
                <button onclick="closeDirectoryModal()" class="text-gray-500 hover:text-gray-700">
                    <i class="fa-solid fa-times"></i>
                </button>
            </div>

            <!-- Current Path -->
            <div class="mb-4">
                <p class="text-sm text-gray-600">Current Path:</p>
                <p id="currentPath" class="font-mono text-sm"></p>
            </div>

            <!-- Directory List -->
            <div class="max-h-[300px] overflow-y-auto border rounded-md">
                <div id="directoryList" class="p-2 space-y-2"></div>
            </div>

            <!-- Create New Directory -->
            <div class="mt-4 flex gap-2">
                <input type="text" id="newDirName" placeholder="New directory name"
                    class="flex-1 border rounded-md px-2 py-1">
                <button onclick="createNewDirectory()"
                    class="bg-blue-500 text-white px-4 py-1 rounded-md hover:bg-blue-600">
                    Create
                </button>
            </div>

            <!-- Buttons -->
            <div class="mt-4 flex justify-end gap-2">
                <button onclick="closeDirectoryModal()"
                    class="px-4 py-2 border rounded-md hover:bg-gray-300 hover:text-black">
                    Cancel
                </button>
                <button onclick="selectDirectory()" class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600">
                    Select
                </button>
            </div>
        </div>
    </div>

    <script src="js/label-image.js"></script>
@endsection
