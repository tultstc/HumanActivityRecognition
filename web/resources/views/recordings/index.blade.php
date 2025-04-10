@extends('layouts.app')

@section('content')
    <div>
        <div class="row">
            <div class="grid grid-cols-12 gap-2">
                <div class="col-span-3">
                    <div class="card mb-3">
                        <div class="card-header">Recording Details</div>
                        <div class="card-body">
                            <table class="table table-sm">
                                <tbody id="recording-metadata">
                                    <tr>
                                        <td colspan="2" class="text-center">Select a recording to view
                                            details</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-header">Navigation</div>
                        <div class="card-body p-0">
                            <div id="recordings-tree" class="max-h-[450px] overflow-auto"></div>
                        </div>
                    </div>
                </div>
                <div class="col-span-9">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span id="current-recording-title">Select a recording</span>
                            <div class="btn-group">
                                <button class="btn btn-sm btn-primary" id="play-btn" disabled>
                                    <i class="fas fa-play"></i> Play
                                </button>
                                <button class="btn btn-sm btn-secondary" id="pause-btn" disabled>
                                    <i class="fas fa-pause"></i> Pause
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-12 mb-3 text-center">
                                    <div id="recording-player" class="border flex flex-col items-center">
                                        <img src="{{ asset('images/no_single.jpg') }}" alt="Select a recording"
                                            class="w-[1080px] h-[600px]" id="recording-placeholder">
                                        <img src="" alt="" class="d-none w-[1080px] h-[600px]"
                                            id="video-stream">
                                    </div>
                                </div>
                            </div>

                            <div class="row mt-3">
                                <div class="col-md-12">
                                    <div class="d-flex align-items-center">
                                        <label for="fps-control" class="me-2">FPS:</label>
                                        <input type="range" class="form-range w-25" id="fps-control" min="1"
                                            max="30" value="10">
                                        <span id="fps-value" class="ms-2">10</span>

                                        <div class="ms-auto">
                                            <span id="frame-counter">0/0</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <link rel="stylesheet" href="css/jstree.min.css" />
    <script src="js/jstree.min.js"></script>
    <script src="js/event-recordings.js"></script>
@endsection
