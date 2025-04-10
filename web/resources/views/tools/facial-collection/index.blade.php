@extends('layouts.app')

@section('content')
    <div>
        <div class="grid grid-cols-12 gap-4 mb-4">
            <div class="col-span-6">
                <div class="form-group">
                    <label for="person-name" class="form-label">Name:</label>
                    <input type="text" id="person-name" class="form-control" placeholder="Enter person's name">
                </div>
            </div>
            <div class="col-span-6">
                <div class="form-group">
                    <label for="camera-select" class="form-label">Select a camera:</label>
                    <select id="camera-select" class="form-control" onchange="updateCamera()">
                        @foreach ($cameras as $camera)
                            <option value="{{ $camera->id }}">{{ $camera->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-12 gap-4">
            <div class="col-span-8 ">
                <div class="card">
                    <div class="card-body">
                        <img id="camera-stream" src="" alt="Camera Stream" class="img-fluid">
                    </div>
                </div>
            </div>
            <div class="col-span-4">
                <div class="card">
                    <div class="card-header">
                        Photos taken
                    </div>
                    <div class="card-body h-[265px] max-h-[265px] overflow-auto">
                        <div id="captured-images" class="d-flex flex-wrap">
                        </div>
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-header">
                        Face database information
                        <button class="btn btn-sm btn-primary float-end" onclick="refreshDatabaseInfo()">
                            <i class="fas fa-sync-alt"></i> Refresh
                        </button>
                    </div>
                    <div class="card-body h-[265px] max-h-[265px] overflow-auto">
                        <div id="database-info-content">
                            <p>Loading information...</p>
                        </div>
                    </div>
                </div>

                <div class="mt-3">
                    <div class="float-end flex item-center gap-2">
                        <button id="capture-btn" class="btn btn-primary btn-block" onclick="captureImage()">Capture</button>
                        <button id="save-btn" class="btn btn-success btn-block" onclick="saveFaces()">Save</button>
                        <button id="process-btn" class="btn btn-info btn-block" onclick="processDatabase()">Process
                            database</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="previewModal" tabindex="-1" role="dialog" aria-labelledby="previewModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="previewModalLabel">Image Preview</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-center" id="previewModalBody">
                        <img id="preview-image" src="" alt="Preview" class="img-fluid">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="resultModal" tabindex="-1" role="dialog" aria-labelledby="resultModalLabel"
            aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="resultModalLabel">Results</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" id="resultModalBody">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="js/bootstrap.bundle.min.js"></script>
    <script src="js/facial-collection.js"></script>
@endsection
