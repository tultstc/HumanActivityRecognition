@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row">
            <div class="col-md-12">

                @if ($errors->any())
                    <ul class="alert alert-warning">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                @endif

                <div class="card">
                    <div class="card-header">
                        <h4>{{ __('messages.add_user') }}
                            <a href="{{ url('users') }}" class="btn btn-danger float-end">{{ __('messages.back') }}</a>
                        </h4>
                    </div>
                    <div class="card-body">
                        <form action="{{ url('users') }}" method="POST">
                            @csrf

                            <div class="mb-3">
                                <label for="">{{ __('messages.name') }}</label>
                                <input type="text" name="name" class="form-control" value="{{ old('name') }}"
                                    required />
                            </div>
                            <div class="mb-3">
                                <label for="">Email</label>
                                <input type="text" name="email" class="form-control" value="{{ old('email') }}"
                                    required />
                            </div>
                            <div class="mb-3">
                                <label for="">{{ __('messages.password') }}</label>
                                <input type="password" name="password" class="form-control" value="{{ old('password') }}"
                                    required />
                            </div>
                            <div class="mb-3">
                                <label for="status">{{ __('messages.status') }}</label>
                                <select class="form-control" name="status">
                                    <option value="0" selected>{{ __('messages.inactive') }}</option>
                                    <option value="1">{{ __('messages.active') }}</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="roles">{{ __('messages.roles') }}</label>
                                <select class="form-control" name="roles">
                                    @foreach ($roles as $role)
                                        <option value="{{ $role }}">{{ $role }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <button type="submit" class="btn btn-primary">{{ __('messages.save') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script src="js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.select2_search').select2({
                placeholder: "Select an option",
                allowClear: true
            });
        })
    </script>
@endsection
