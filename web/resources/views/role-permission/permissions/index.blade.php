@extends('layouts.app')

@section('content')
    <div class="container">
        <a href="{{ url('permissions/create') }}" class="btn btn-primary float-end">Add Permission</a>
    </div>

    <table id="datatable" class="table table-striped" style="width:100%">
        <thead>
            <tr>
                <th>Name</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($permissions as $permission)
                <tr>
                    <td width="50%">{{ $permission->name }}</td>
                    <td>
                        {{-- @can('update permission') --}}
                        <a href="{{ url('permissions/' . $permission->id . '/edit') }}" class="btn btn-success">Edit</a>
                        {{-- @endcan --}}

                        {{-- @can('delete permission') --}}
                        <a href="{{ url('permissions/' . $permission->id . '/delete') }}"
                            class="btn btn-danger mx-2">Delete</a>
                        {{-- @endcan --}}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
