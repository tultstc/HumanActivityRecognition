<x-app-layout add_class='mt-2'>

    @section('content')
        <div class="grid grid-cols-4">
            <div class="card col-span-1 !rounded-none">
                <div class="card-header">
                    <h5 class="card-title mb-0">Label Configurations</h5>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <h6 class="mb-2">Directory Management</h6>
                        <div class="mb-2">
                            <button class="btn btn-sm btn-primary w-full" data-coreui-toggle="modal"
                                data-coreui-target="#directoryModal">
                                <i class="cil-folder-open"></i> Manage Directories
                            </button>
                        </div>
                    </div>

                    <div class="mb-4">
                        <h6 class="mb-2">Label Management</h6>
                        <div class="input-group mb-2">
                            <input type="text" id="label_name" class="form-control" placeholder="Label name">
                            <select id="label_status" class="form-select" style="max-width: 120px;">
                                <option value="OK">OK</option>
                                <option value="NG">NG</option>
                            </select>
                            <button class="btn btn-success" onclick="addLabel()">
                                <i class="cil-plus"></i> Add
                            </button>
                        </div>
                        <div class="table-responsive max-h-[200px] overflow-y-auto">
                            <table class="table table-sm table-bordered">
                                <thead>
                                    <tr>
                                        <th>Label Name</th>
                                        <th>Status</th>
                                        <th>Action ID</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="label_table"></tbody>
                            </table>
                        </div>
                    </div>

                    <div>
                        <h6 class="mb-2">Video Naming Configuration</h6>
                        <p class="small text-muted mb-2">Format: S<span id="subject_id_display">001</span>C<span
                                id="camera_id_display">001</span>P<span id="person_id_display">001</span>R<span
                                id="repeat_id_display">001</span>A<span id="action_id_display">001</span>.mp4</p>

                        <div class="row g-2 mb-2">
                            <div class="col-md-6">
                                <label class="form-label small">Subject ID</label>
                                <input type="number" id="subject_id" class="form-control form-control-sm" value="1"
                                    min="1" max="999" onchange="updateNamingPreview()">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">Camera ID</label>
                                <input type="number" id="camera_id" class="form-control form-control-sm" value="1"
                                    min="1" max="999" onchange="updateNamingPreview()">
                            </div>
                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col-md-6">
                                <label class="form-label small">Person ID</label>
                                <input type="number" id="person_id" class="form-control form-control-sm" value="1"
                                    min="1" max="999" onchange="updateNamingPreview()">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">Repeat ID</label>
                                <input type="number" id="repeat_id" class="form-control form-control-sm" value="1"
                                    min="1" max="999" onchange="updateNamingPreview()">
                            </div>
                        </div>
                        <div class="row g-2">
                            <div class="col-md-12">
                                <label class="form-label small">Action ID (Label)</label>
                                <select id="action_id" class="form-select form-select-sm" onchange="updateNamingPreview()">
                                </select>
                            </div>
                        </div>
                        <div class="d-grid mt-3">
                            <button class="btn btn-sm btn-secondary" onclick="suggestNextIds()">
                                <i class="cil-reload"></i> Suggest Next IDs
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card col-span-2 !rounded-none">
                <div class="card-header">
                    <h5 class="card-title mb-0">RTSP Camera Stream</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <div class="input-group">
                                <span class="input-group-text">RTSP URL:</span>
                                <input type="text" id="rtsp_url" class="form-control"
                                    placeholder="rtsp://username:password@camera-ip:554/stream">
                                <button class="btn btn-primary" onclick="connectStream()">
                                    <i class="cil-video"></i> Connect
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-12">
                            <div class="border rounded p-3 bg-light d-flex justify-content-center align-items-center">
                                <canvas id="videoCanvas"></canvas>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12 text-center">
                            <button onclick="startRecording()" class="btn btn-success me-2" id="recordBtn">
                                <i class="cil-media-record"></i> <span id="recordBtnText">Start Recording</span>
                            </button>
                            <button onclick="stopRecording()" class="btn btn-danger" id="stopBtn" disabled>
                                <i class="cil-media-stop"></i> Stop Recording
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card col-span-1 !rounded-none">
                <div class="card-header">
                    <h5 class="card-title mb-0">Recorded Videos</h5>
                </div>
                <div class="card-body max-h-[750px] overflow-y-auto">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th>Filename</th>
                                    <th>Action</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="video_table"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="directoryModal" tabindex="-1" aria-labelledby="directoryModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="directoryModalLabel">Directory Management</h5>
                        <button type="button" class="btn-close" data-coreui-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body max-h-[800px] overflow-y-auto">
                        <div class="mb-3">
                            <div class="input-group mb-2">
                                <input type="text" id="directory_name" class="form-control"
                                    placeholder="New directory name">
                                <button class="btn btn-primary" onclick="createDirectory()">
                                    <i class="cil-folder-open"></i> Create Directory
                                </button>
                            </div>
                        </div>

                        <nav aria-label="directory-breadcrumb" class="mb-2">
                            <ol class="breadcrumb" id="directory_breadcrumb">
                                <li class="breadcrumb-item active">Root</li>
                            </ol>
                        </nav>

                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center py-2">
                                <h6 class="mb-0">Directories</h6>
                                <button class="btn btn-sm btn-info" onclick="loadDirectoryContents()">
                                    <i class="cil-reload"></i> Refresh
                                </button>
                            </div>
                            <ul class="list-group list-group-flush" id="directory_list">
                                <li class="list-group-item text-muted">Loading directories...</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <script src="js/jsmpeg-player.min.js"></script>
        <script src="js/record-video.js"></script>
    @endsection
</x-app-layout>
