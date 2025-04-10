@extends('layouts.app')

@section('content')
    <div>
        <div class="row justify-content-center">
            <div class="lg:grid lg:grid-cols-2 gap-4">
                <div class="card">
                    <div class="card-header">
                        <h3>PoseC3D Data Extraction Tool</h3>
                    </div>
                    <div class="card-body">
                        <form id="extractionForm">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <h5>Input Directory</h5>
                                    <div class="input-group mb-3">
                                        <input type="text" class="form-control" id="input_folder"
                                            placeholder="Select input folder" readonly>
                                        <button class="btn btn-outline-secondary" type="button"
                                            id="browseInputBtn">Browse</button>
                                    </div>
                                    <div id="inputDirectoryTree" class="directory-tree border p-2 mb-3"
                                        style="max-height: 300px; overflow-y: auto; display: none;"></div>
                                </div>
                                <div class="col-md-6">
                                    <h5>Output Directory</h5>
                                    <div class="input-group mb-3">
                                        <input type="text" class="form-control" id="output_folder"
                                            placeholder="Select output folder" readonly>
                                        <button class="btn btn-outline-secondary" type="button"
                                            id="browseOutputBtn">Browse</button>
                                    </div>
                                    <div id="outputDirectoryTree" class="directory-tree border p-2 mb-3"
                                        style="max-height: 300px; overflow-y: auto; display: none;"></div>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <h5>Output Directory</h5>
                                <div class="col-md-6">
                                    <select class="form-select" id="device">
                                        <option value="cuda:0">CUDA:0 (Default)</option>
                                        <option value="cuda:1">CUDA:1</option>
                                        <option value="cuda:2">CUDA:2</option>
                                        <option value="cpu">CPU</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="skip_postproc">
                                        <label class="form-check-label" for="skip_postproc">
                                            Skip post-processing
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary" id="startExtractionBtn">Start
                                    Extraction</button>
                            </div>
                        </form>
                        <div id="progressSection" class="mt-4" style="display: none;">
                            <h5>Extraction Progress</h5>
                            <div class="progress mb-3">
                                <div id="extractionProgress" class="progress-bar progress-bar-striped progress-bar-animated"
                                    role="progressbar" style="width: 0%"></div>
                            </div>

                            <div class="d-flex justify-content-between mb-3">
                                <span id="progressText">Starting extraction...</span>
                                <span id="progressPercentage">0%</span>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <p><strong>Input:</strong> <span id="inputFolderDisplay"></span></p>
                                    <p><strong>Output:</strong> <span id="outputFolderDisplay"></span></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Status:</strong> <span id="statusDisplay"
                                            class="badge bg-primary">Running</span></p>
                                    <p><strong>Time Elapsed:</strong> <span id="timeElapsed">00:00:00</span></p>
                                </div>
                            </div>

                            <div class="d-grid">
                                <button class="btn btn-danger" id="cancelExtractionBtn">Cancel Extraction</button>
                            </div>
                        </div>
                        <div class="mt-4">
                            <h5>Extraction Log</h5>
                            <div id="extractionLog" class="border p-2"
                                style="height: 180px; overflow-y: auto; background-color: #f8f9fa; font-family: monospace;">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header">
                        <h3>Combine PKL Files</h3>
                    </div>
                    <div class="card-body">
                        <form id="combineForm">
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <h5>Training Data Directory</h5>
                                    <div class="input-group mb-3">
                                        <input type="text" class="form-control" id="train_folder"
                                            placeholder="Select train folder" readonly>
                                        <button class="btn btn-outline-secondary" type="button"
                                            id="browseTrainBtn">Browse</button>
                                    </div>
                                    <div id="trainDirectoryTree" class="directory-tree border p-2 mb-3"
                                        style="max-height: 300px; overflow-y: auto; display: none;"></div>
                                </div>
                                <div class="col-md-4">
                                    <h5>Validation Data Directory</h5>
                                    <div class="input-group mb-3">
                                        <input type="text" class="form-control" id="val_folder"
                                            placeholder="Select validation folder" readonly>
                                        <button class="btn btn-outline-secondary" type="button"
                                            id="browseValBtn">Browse</button>
                                    </div>
                                    <div id="valDirectoryTree" class="directory-tree border p-2 mb-3"
                                        style="max-height: 300px; overflow-y: auto; display: none;"></div>
                                </div>
                                <div class="col-md-4">
                                    <h5>Test Data Directory (Optional)</h5>
                                    <div class="input-group mb-3">
                                        <input type="text" class="form-control" id="test_folder"
                                            placeholder="Select test folder (optional)" readonly>
                                        <button class="btn btn-outline-secondary" type="button"
                                            id="browseTestBtn">Browse</button>
                                    </div>
                                    <div id="testDirectoryTree" class="directory-tree border p-2 mb-3"
                                        style="max-height: 300px; overflow-y: auto; display: none;"></div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <h5>Output PKL File</h5>
                                    <div class="input-group mb-3">
                                        <input type="text" class="form-control" id="output_file"
                                            placeholder="Output file path" value="ntu_custom_dataset.pkl">
                                    </div>
                                </div>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary" id="startCombineBtn">Combine PKL
                                    Files</button>
                            </div>
                        </form>

                        <div id="combineResultSection" class="mt-4" style="display: none;">
                            <h5>Combine Results</h5>
                            <div class="alert alert-success" id="combineResultMessage"></div>

                            <div class="card">
                                <div class="card-header">Statistics</div>
                                <div class="card-body">
                                    <ul class="list-group" id="combineStatsList">
                                    </ul>
                                </div>
                            </div>

                            <div class="mt-3">
                                <button class="btn btn-primary" id="newCombineBtn">Create New Dataset</button>
                            </div>
                        </div>

                        <div class="mt-4">
                            <h5>Combine Log</h5>
                            <div id="combineLog" class="border p-2"
                                style="height: 180px; overflow-y: auto; background-color: #f8f9fa; font-family: monospace;">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div id="previewModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="previewModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="previewModalLabel">Review Annotations</h5>
                </div>
                <div class="modal-body">
                    <p><strong>Video:</strong> <span id="currentVideoName"></span></p>
                    <p id="processingProgress" class="mb-2">Processing video</p>

                    <div class="text-center mb-3">
                        <img id="framePreview" class="img-fluid border" style="max-height: 400px;" alt="Frame Preview">
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span id="frameCounter">Frame 1 of 1</span>
                        <div class="btn-group">
                            <button id="prevFrameBtn" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-chevron-left"></i> Previous
                            </button>
                            <button id="nextFrameBtn" class="btn btn-outline-secondary btn-sm">
                                Next <i class="fas fa-chevron-right"></i>
                            </button>
                        </div>
                    </div>

                    <div class="mb-3">
                        <input type="range" class="form-range" id="frameSlider" min="0" max="0"
                            value="0">
                    </div>
                </div>
                <div class="modal-footer">
                    <button id="skipSaveBtn" class="btn btn-warning">Skip This Video</button>
                    <button id="confirmSaveBtn" class="btn btn-success">Save Annotations</button>
                </div>
            </div>
        </div>
    </div>
    <script src="js/label-video.js"></script>
@endsection
