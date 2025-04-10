@extends('layouts.app')

@section('content')
    <style>
        .progress-bar {
            background-image: linear-gradient(45deg, rgba(255, 255, 255, 0.15) 25%, transparent 25%, transparent 50%, rgba(255, 255, 255, 0.15) 50%, rgba(255, 255, 255, 0.15) 75%, transparent 75%, transparent);
            background-size: 1rem 1rem;
            font-weight: bold;
            text-shadow: 1px 1px 1px rgba(0, 0, 0, 0.3);
        }

        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(0, 123, 255, 0.7);
            }

            70% {
                box-shadow: 0 0 0 5px rgba(0, 123, 255, 0);
            }

            100% {
                box-shadow: 0 0 0 0 rgba(0, 123, 255, 0);
            }
        }

        .progress.active {
            animation: pulse 2s infinite;
        }

        @keyframes confetti {
            0% {
                background-position: 0 0;
            }

            100% {
                background-position: 100px 100px;
            }
        }

        .progress-complete .progress-bar {
            background-image: linear-gradient(135deg, #28a745 25%, #218838 25%, #218838 50%, #28a745 50%, #28a745 75%, #218838 75%, #218838);
            animation: confetti 2s linear infinite !important;
            background-size: 20px 20px !important;
        }
    </style>
    <div>
        <div class="row justify-content-center">
            <div>
                <div class="card">
                    <div class="card-header">
                        <h3 class="m-0">PoseC3D Model Training</h3>
                    </div>
                    <div class="card-body">
                        <form id="training-form">
                            <div class="grid grid-cols-2 gap-4 mb-3">
                                <div>
                                    <h5>Training Parameters</h5>
                                    <div class="grid grid-cols-3 gap-3">
                                        <div class="form-group">
                                            <label for="batch_size">Batch Size</label>
                                            <input type="number" class="form-control" id="batch_size" name="batch_size"
                                                value="8">
                                        </div>
                                        <div class="form-group">
                                            <label for="num_workers">Workers</label>
                                            <input type="number" class="form-control" id="num_workers" name="num_workers"
                                                value="4">
                                        </div>
                                        <div class="form-group">
                                            <label for="max_epochs">Epochs</label>
                                            <input type="number" class="form-control" id="max_epochs" name="max_epochs"
                                                value="24">
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <h5>Dataset Configuration</h5>
                                    <div class="grid grid-cols-3 gap-3">
                                        <div class="form-group">
                                            <label for="ann_file">Annotation File</label>
                                            <select class="form-control" id="ann_file" name="ann_file">
                                                <option value="">Loading annotation files...</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label for="work_dir">Work Directory</label>
                                            <input type="text" class="form-control" id="work_dir" name="work_dir"
                                                value="train_results/slowonly_r50">
                                        </div>
                                        <div class="form-group">
                                            <label for="seed">Random Seed</label>
                                            <input type="number" class="form-control" id="seed" name="seed"
                                                value="0">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4 mb-3">
                                <div>
                                    <h5>Model Configuration</h5>
                                    <div class="grid grid-cols-3 gap-3">
                                        <div class="form-group">
                                            <label for="num_classes">Number of Classes</label>
                                            <input type="number" class="form-control" id="num_classes" name="num_classes"
                                                value="4">
                                        </div>
                                        <div class="form-group">
                                            <label for="dropout_ratio">Dropout Ratio</label>
                                            <input type="number" step="0.1" min="0" max="1"
                                                class="form-control" id="dropout_ratio" name="dropout_ratio" value="0.5">
                                        </div>
                                        <div class="form-group">
                                            <label for="clip_len">Window Size</label>
                                            <input type="number" class="form-control" id="clip_len" name="clip_len"
                                                value="50">
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <h5>Optimizer Settings</h5>
                                    <div class="grid grid-cols-3 gap-3">
                                        <div class="form-group">
                                            <label for="learning_rate">Learning Rate</label>
                                            <input type="number" step="0.0001" class="form-control" id="learning_rate"
                                                name="learning_rate" value="0.001">
                                        </div>
                                        <div class="form-group">
                                            <label for="weight_decay">Weight Decay</label>
                                            <input type="number" step="0.0001" class="form-control" id="weight_decay"
                                                name="weight_decay" value="0.0003">
                                        </div>
                                        <div class="form-group">
                                            <label for="scheduler">Learning Rate Scheduler</label>
                                            <select class="form-control" id="scheduler" name="scheduler">
                                                <option value="CosineAnnealingLR">CosineAnnealingLR</option>
                                                <option value="StepLR">StepLR</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <h5>Training Options</h5>
                                <div class="form-group">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="use_amp" name="use_amp">
                                        <label class="form-check-label" for="use_amp">
                                            Enable Automatic Mixed Precision
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary" id="start-training-btn">Start
                                        Training</button>
                                    <button type="button" class="btn btn-danger d-none" id="stop-training-btn">Stop
                                        Training</button>
                                </div>
                            </div>
                        </form>

                        <div class="mt-4" id="training-progress-container">
                            <h4>Training Progress</h4>
                            <div class="progress mb-3"
                                style="height: 20px; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);">
                                <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar"
                                    id="training-progress-bar"
                                    style="width: 0%; transition: width 0.5s ease-in-out, background-color 0.5s;"
                                    aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
                            </div>
                            <div class="card">
                                <div class="card-header">Training Logs</div>
                                <div class="card-body">
                                    <pre id="training-logs" style="max-height: 190px; overflow-y: auto;"></pre>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="js/train.js"></script>
@endsection
