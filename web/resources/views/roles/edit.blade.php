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
                        <h4>{{ __('messages.edit_role') }}: {{ $role->name }}
                            <a href="{{ route('roles.index') }}"
                                class="btn btn-danger float-end">{{ __('messages.back') }}</a>
                        </h4>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('roles.update', $role->id) }}" method="POST">
                            @csrf
                            @method('PUT')

                            <div class="mb-3">
                                <label for="name">{{ __('messages.name') }}</label>
                                <input type="text" name="name" value="{{ $role->name }}" class="form-control"
                                    required />
                            </div>

                            <div class="mb-3">
                                <label for="permissions">{{ __('messages.permission') }}</label>
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>{{ __('messages.model') }}</th>
                                            <th>{{ __('messages.view') }}</th>
                                            <th>{{ __('messages.create') }}</th>
                                            <th>{{ __('messages.update') }}</th>
                                            <th>{{ __('messages.delete') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $models = [
                                                'user' => ['view user', 'create user', 'update user', 'delete user'],
                                                'role' => ['view role', 'create role', 'update role', 'delete role'],
                                                'camera' => [
                                                    'view camera',
                                                    'create camera',
                                                    'update camera',
                                                    'delete camera',
                                                ],
                                                'event' => [
                                                    'view event',
                                                    'create event',
                                                    'update event',
                                                    'delete event',
                                                ],
                                            ];
                                        @endphp

                                        @foreach ($models as $model => $permissions)
                                            <tr>
                                                <td>{{ __('messages.' . strtolower($model)) }}</td>
                                                @foreach ($permissions as $perm)
                                                    @php
                                                        $permId = $permissions = DB::table('permissions')
                                                            ->where('name', $perm)
                                                            ->value('id');
                                                        $isChecked = in_array($permId, $rolePermissions)
                                                            ? 'checked'
                                                            : '';
                                                    @endphp
                                                    <td>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox"
                                                                id="perm-{{ $perm }}" name="permissions[]"
                                                                value="{{ $permId }}" {{ $isChecked }}>
                                                            <label class="form-check-label" for="perm-{{ $perm }}">
                                                                {{ __('messages.' . strtolower(explode(' ', $perm)[0])) }}
                                                            </label>
                                                        </div>
                                                    </td>
                                                @endforeach
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
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
