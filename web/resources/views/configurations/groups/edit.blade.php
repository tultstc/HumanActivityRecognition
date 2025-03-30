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
                        <h4>Edit Group
                            <a href="{{ route('configurations.groups') }}" class="btn btn-danger float-end">Back</a>
                        </h4>
                    </div>
                    <div class="card-body">
                        <form action="{{ url('configurations/groups/' . $group->id) }}" method="POST">
                            @csrf
                            @method('PUT')

                            <div class="mb-3">
                                <label for="">Name</label>
                                <input type="text" name="name" value="{{ $group->ten }}" class="form-control" />
                            </div>
                            <div class="mb-3">
                                <label for="">Description</label>
                                <input type="text" name="description" value="{{ $group->mota }}" class="form-control" />
                            </div>
                            <div class="mb-3">
                                <button type="submit" class="btn btn-primary">Update</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('subHeader')
    <div class="flex items-center gap-4">
        <a class="no-underline text-[#6A6E76]" href="{{ route('configurations.areas') }}">Areas</a>
        <a class="no-underline text-[#6A6E76]" href="{{ route('configurations.positions') }}">Positions</a>
        <a class="no-underline text-[#6A6E76]" href="{{ route('configurations.groups') }}">Group</a>
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
