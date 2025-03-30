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
                        <h4>Create Area
                            <a href="{{ route('locations.areas') }}" class="btn btn-danger float-end">Back</a>
                        </h4>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('locations.areas.store') }}" method="POST">
                            @csrf

                            <div class="mb-3">
                                <label for="">Name</label>
                                <input type="text" name="name" class="form-control" required />
                            </div>
                            <div class="mb-3">
                                <label for="">Code</label>
                                <input type="text" name="code" class="form-control" />
                            </div>
                            <div class="mb-3">
                                <label for="">Description</label>
                                <input type="text" name="description" class="form-control" />
                            </div>
                            <div class="mb-3">
                                <button type="submit" class="btn btn-primary">Save</button>
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
        <a class="no-underline text-[#6A6E76]" href="{{ route('locations.areas') }}">Areas</a>
        <a class="no-underline text-[#6A6E76]" href="{{ route('locations.positions') }}">Positions</a>
    </div>
@endsection
