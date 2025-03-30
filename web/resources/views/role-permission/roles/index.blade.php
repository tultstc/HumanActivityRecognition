@extends('layouts.app')

@section('content')
    <div class="container">
        <a href="{{ url('roles/create') }}" class="btn btn-primary float-end">Add Role</a>
    </div>

    <table id="datatable" class="table table-striped" style="width:100%">
        <thead>
            <tr>
                <th>Name</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($roles as $role)
                <tr>
                    <td width="40%">{{ $role->name }}</td>
                    <td>
                        <a href="{{ url('roles/' . $role->id . '/give-permissions') }}" class="btn btn-warning">
                            Add / Edit Role Permission
                        </a>
                        {{-- @can('update role') --}}
                        <a href="{{ url('roles/' . $role->id . '/edit') }}" class="btn btn-success">Edit</a>
                        {{-- @endcan --}}

                        {{-- @can('delete role') --}}
                        <a href="{{ url('roles/' . $role->id . '/delete') }}" class="btn btn-danger mx-2">Delete</a>
                        {{-- @endcan --}}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
