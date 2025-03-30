@extends('layouts.app')

@section('content')
    <div>
        <a href="{{ route('locations.areas.create') }}" class="btn btn-primary float-end">Add Area</a>
    </div>

    <table id="datatable" class="table table-striped" style="width:100%">
        <thead>
            <tr>
                <th>Id</th>
                <th>Name</th>
                <th>Description</th>
                <th>Code</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($areas as $area)
                <tr>
                    <td>{{ $area->id }}</td>
                    <td>{{ $area->ten }}</td>
                    @if ($area->mota)
                        <td>{{ $area->mota }}</td>
                    @else
                        <td></td>
                    @endif
                    <td>{{ $area->ma }}</td>
                    <td>
                        {{-- @can('update area') --}}
                        <a href="{{ url('locations/areas/' . $area->id . '/edit') }}" class="btn btn-success">Edit</a>
                        {{-- @endcan --}}

                        {{-- @can('delete area') --}}
                        <a href="{{ url('locations/areas/' . $area->id . '/delete') }}" class="btn btn-danger mx-2">Delete</a>
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
