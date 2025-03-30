@extends('layouts.app')
@section('links')
    <link rel="stylesheet" href="/css/codemirror.css">
@endsection

@section('content')
    <div class="h-[80%]">
        <div class="h-full">
            @if ($errors->any())
                <ul class="alert alert-warning">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            @endif
            {{-- Form --}}
            <div>
                <form action="{{ url('cameras/' . $camera->id) }}" method="POST">

                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-2">
                        {{-- Draw ROI --}}
                        <div class="shadow-md p-2">
                            <h5>{{ __('messages.roi') }}</h5>
                            <div class="col-span-12">
                                <input type="hidden" name="mask" id='roiPoints'>
                                @include('cameras.partials.polygon')
                            </div>
                        </div>

                        {{-- Metadata --}}
                        <div class="shadow-inner p-2">
                            <div class="flex items-center justify-between">
                                <h5>{{ __('messages.metadata') }}</h5>
                                <a href="{{ route('cameras') }}" class="btn btn-danger">{{ __('messages.back') }}</a>
                            </div>
                            <div class="grid grid-cols-12 gap-3">
                                <div class="col-span-1">
                                    <label for="id" class="form-label">Id</label>
                                    <input type="text" class="form-control" id="id" name="id"
                                        value="{{ $camera->id }}" placeholder="1">
                                    <div class="valid-feedback">
                                        Looks good!
                                    </div>
                                </div>

                                <div class="col-span-5">
                                    <label for="name" class="form-label">{{ __('messages.name') }}</label>
                                    <input type="text" class="form-control" id="name" name="name"
                                        value="{{ $camera->name }}" placeholder="MKK052 Pano 7000">
                                    <div class="valid-feedback">
                                        Looks good!
                                    </div>
                                </div>
                                <div class="col-span-3">
                                    <label for="status" class="form-label">{{ __('messages.status') }}</label>
                                    <select class="form-control" name="status">
                                        <option {{ $camera->status == 0 ? 'selected' : '' }} value=0>
                                            {{ __('messages.inactive') }}</option>
                                        <option {{ $camera->status == 1 ? 'selected' : '' }} value=1>
                                            {{ __('messages.active') }}</option>
                                    </select>
                                    <div class="invalid-feedback">
                                        Please select a valid status.
                                    </div>
                                </div>
                                <div class="col-span-3">
                                    <label for="model_id" class="form-label">{{ __('messages.model') }}</label>
                                    <select class="form-control" name="model_id">
                                        @foreach ($models as $model)
                                            <option {{ $camera->model->id == $model->id ? 'selected' : '' }}
                                                value="{{ $model->id }}">{{ $model->name }}</option>
                                        @endforeach
                                    </select>
                                    <div class="invalid-feedback">
                                        Please select a valid model.
                                    </div>
                                </div>
                                <div class="col-span-12">
                                    <label for="stream_url" class="form-label">{{ __('messages.url') }}</label>
                                    <input type="text" class="form-control" id="stream_url" name="stream_url"
                                        value="{{ $camera->stream_url }}"
                                        placeholder="{{ __('messages.url_place_holder') }}">
                                    <div class="valid-feedback">
                                        Looks good!
                                    </div>
                                </div>
                                <div class="col-span-12">
                                    <label for="config" class="form-label">{{ __('messages.config') }}</label>
                                    <textarea class="!bg-none" id="config" name="config">{{ json_encode($camera->config, JSON_PRETTY_PRINT) }}</textarea>
                                    <div class="valid-feedback">
                                        Looks good!
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center justify-end mt-4">
                                <button class="btn btn-primary" type="submit">{{ __('messages.update') }}</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        window.polygonData = {!! json_encode($camera) !!};
    </script>
    <script src="/js/polygon.js"></script>
    <script src="/js/codemirror.min.js"></script>
    <script src="/js/codemirrorjs.min.js"></script>
    {{-- <script src="js/select2.min.js"></script> --}}
    <script>
        var editor = CodeMirror.fromTextArea(document.getElementById("config"), {
            lineNumbers: true,
            mode: "javascript",
            theme: "default",
            viewportMargin: Infinity
        });

        // $(document).ready(function() {
        //     $('.select2_search').select2({
        //         placeholder: "Select an option",
        //         allowClear: true
        //     });
        // });

        $('form').submit(function() {
            $('#config').val(editor.getValue());
        });
    </script>
@endsection
