@extends('layouts.app')
@section('links')
    <link rel="stylesheet" href="css/codemirror.min.css">
    <style>
        .alert-fade {
            transition: opacity 0.5s ease-in-out;
        }

        .alert-hide {
            opacity: 0;
        }
    </style>
@endsection

@section('content')
    <div class="h-[80%]">
        <div class="h-full">
            @if ($errors->any())
                <div id="error-alerts" class="alert alert-warning alert-fade">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div>
                <form action="{{ route('cameras.store') }}" method="POST">
                    @csrf
                    <div class="col-span-9">
                        <div class="flex items-center justify-between">
                            <h5>{{ __('messages.metadata') }}</h5>
                            <a href="{{ route('cameras') }}" class="btn btn-danger">{{ __('messages.back') }}</a>
                        </div>
                        <div class="grid grid-cols-12 gap-3">
                            <div class="col-span-1">
                                <label for="id" class="form-label">ID</label>
                                <input type="number" class="form-control" id="id" name="id"
                                    value="{{ old('id') }}" placeholder="1" required>
                                <div class="valid-feedback">
                                    Looks good!
                                </div>
                            </div>
                            <div class="col-span-5">
                                <label for="name" class="form-label">{{ __('messages.name') }}</label>
                                <input type="text" class="form-control" id="name" name="name"
                                    value="{{ old('name') }}" placeholder="MKK052 Pano 7000" required>
                                <div class="valid-feedback">
                                    Looks good!
                                </div>
                            </div>
                            <div class="col-span-2">
                                <label for="model_id" class="form-label">{{ __('messages.models') }}</label>
                                <select required class="form-control" name="model_id">
                                    @foreach ($models as $model)
                                        <option value="{{ $model->id }}"
                                            {{ old('model_id', $model->name == 'Model Default' ? $model->id : '') == $model->id ? 'selected' : '' }}>
                                            {{ $model->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback">
                                    Please select a valid model.
                                </div>
                            </div>
                            <div class="col-span-2">
                                <label for="status" class="form-label">{{ __('messages.status') }}</label>
                                <select required class="form-control" name="status">
                                    <option value="0" {{ old('status') == '0' ? 'selected' : '' }}>
                                        {{ __('messages.inactive') }}
                                    </option>
                                    <option value="1" {{ old('status') == '1' ? 'selected' : '' }}>
                                        {{ __('messages.active') }}
                                    </option>
                                </select>
                                <div class="invalid-feedback">
                                    Please select a valid status.
                                </div>
                            </div>
                            <div class="col-span-2">
                                <label for="groups" class="form-label">Select Group</label>
                                <select name="groups[]" id="groups" class="form-control" multiple required>
                                    @foreach ($groups as $group)
                                        <option value="{{ $group->id }}">{{ $group->name }} (ID: {{ $group->id }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-span-12">
                                <label for="stream_url" class="form-label">{{ __('messages.url') }}</label>
                                <input type="text" class="form-control" id="stream_url" name="stream_url"
                                    value="{{ old('stream_url') }}" placeholder="{{ __('messages.url_place_holder') }}"
                                    required>
                                <div class="valid-feedback">
                                    Looks good!
                                </div>
                            </div>
                            <div class="col-span-12">
                                <label for="config" class="form-label">{{ __('messages.config') }}</label>
                                <textarea id="config" name="config">{{ old('config') }}</textarea>
                                <div class="valid-feedback">
                                    Looks good!
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md w-full flex items-end justify-end my-3">
                        <button class="btn btn-primary" type="submit">{{ __('messages.save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script src="js/select2.min.js"></script>
    <script src="/js/codemirror.min.js"></script>
    <script src="/js/codemirrorjs.min.js"></script>
    <script>
        var editor = CodeMirror.fromTextArea(document.getElementById("config"), {
            lineNumbers: true,
            mode: "javascript",
            theme: "default",
            viewportMargin: Infinity
        });

        @if (old('config'))
            editor.setValue(@json(old('config')));
        @endif

        $(document).ready(function() {
            $('.select2_search').select2({
                placeholder: "Select an option",
                allowClear: true
            });

            if (document.getElementById('error-alerts')) {
                setTimeout(function() {
                    document.getElementById('error-alerts').classList.add('alert-hide');
                }, 3000);

                setTimeout(function() {
                    document.getElementById('error-alerts').style.display = 'none';
                }, 3500);
            }
        });

        $('form').submit(function() {
            $('#config').val(editor.getValue());
        });
    </script>
@endsection
