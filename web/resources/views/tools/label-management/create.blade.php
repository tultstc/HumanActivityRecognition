@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4>{{ __('Add Labels') }}</h4>
                        <a href="{{ route('label-management') }}" class="btn btn-secondary">{{ __('messages.back') }}</a>
                    </div>
                    <div class="card-body">
                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form action="{{ route('label-management.store') }}" method="POST">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label required">{{ __('messages.name') }}</label>
                                <input type="text" name="name" class="form-control" value="{{ old('name') }}"
                                    required />
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="type">{{ __('Type') }}</label>
                                <select class="form-control" name="type">
                                    <option value="action" selected>{{ __('Action') }}</option>
                                    <option value="object">{{ __('Object') }}</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="status">{{ __('messages.status') }}</label>
                                <select class="form-control" name="status">
                                    <option value=0 selected>{{ __('NG') }}</option>
                                    <option value=1>{{ __('OK') }}</option>
                                </select>
                            </div>
                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary">{{ __('messages.save') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
