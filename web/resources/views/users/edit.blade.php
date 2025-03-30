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
                        <h4>{{ __('messages.edit_user') }}
                            <a href="{{ url('users') }}" class="btn btn-danger float-end">{{ __('messages.back') }}</a>
                        </h4>
                    </div>
                    <div class="card-body">
                        <form action="{{ url('users/' . $user->id) }}" method="POST">
                            @csrf
                            @method('PUT')

                            <div class="mb-3">
                                <label for="">{{ __('messages.name') }}</label>
                                <input type="text" name="name" value="{{ $user->name }}" class="form-control" />
                                @error('name')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label for="">Email</label>
                                <input type="text" name="email" readonly value="{{ $user->email }}"
                                    class="form-control" />
                            </div>
                            <div class="mb-3">
                                <label for="">{{ __('messages.password') }}</label>
                                <input type="text" name="password" class="form-control" />
                                @error('password')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label for="status">{{ __('messages.status') }}</label>
                                <select class="form-control" name="status">
                                    <option value="0" {{ $user->status == 0 ? 'selected' : '' }}>
                                        {{ __('messages.inactive') }}</option>
                                    <option value="1" {{ $user->status == 1 ? 'selected' : '' }}>
                                        {{ __('messages.active') }}</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="">{{ __('messages.roles') }}</label>
                                <select class="form-control" name="roles">
                                    @foreach ($roles as $role)
                                        <option value="{{ $role }}"
                                            {{ in_array($role, $userRoles) ? 'selected' : '' }}>
                                            {{ $role }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('roles')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <button type="submit" class="btn btn-primary">{{ __('messages.update') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
