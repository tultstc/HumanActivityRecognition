@extends('layouts.app')

@section('content')
    <div>
        <a href="{{ route('locations.positions.create') }}" class="btn btn-primary float-end">Add Position</a>
    </div>

    <table id="datatable" class="table table-striped" style="width:100%">
        <thead>
            <tr>
                <th>Id</th>
                <th>Name</th>
                <th>Description</th>
                <th>Area</th>
                <th>Code</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($positions as $position)
                <tr>
                    <td>{{ $position->id }}</td>
                    <td>{{ $position->ten }}</td>
                    @if ($position->mota)
                        <td>{{ $position->mota }}</td>
                    @else
                        <td></td>
                    @endif

                    @if ($position->area)
                        <td>{{ $position->area->ten }}</td>
                    @else
                        <td></td>
                    @endif
                    <td>{{ $position->ma }}</td>
                    <td>
                        {{-- @can('update position') --}}
                        <a href="{{ url('locations/positions/' . $position->id . '/edit') }}" class="btn btn-success">Edit</a>
                        {{-- @endcan --}}

                        {{-- @can('delete position') --}}
                        <a href="{{ url('locations/positions/' . $position->id . '/delete') }}"
                            class="btn btn-danger mx-2">Delete</a>
                        {{-- @endcan --}}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection

@section('subHeader')
    <div class="flex items-center gap-4">
        <a class="no-underline text-[#6A6E76]" href="{{ route('locations.areas') }}">Areas</a>
        <a class="no-underline text-[#6A6E76]" href="{{ route('locations.positions') }}">Positions</a>
    </div>
@endsection
