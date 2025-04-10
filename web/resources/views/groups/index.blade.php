@extends('layouts.app')

@section('content')
    @if (session()->has('status'))
        <div class="alert alert-success mb-1">
            {{ session()->get('status') }}
        </div>
    @endif

    <div>
        <a href="{{ route('groups.create') }}" class="btn btn-primary float-start mt-1">Add Group</a>
    </div>

    <table id="datatable" class="table table-striped" style="width:100%">
        <thead>
            <tr>
                <th>Id</th>
                <th>Name</th>
                <th>Description</th>
                <th>Number Of Cameras</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($groups as $group)
                <tr>
                    <td>{{ $group->id }}</td>
                    <td>{{ $group->name }}</td>
                    @if ($group->description)
                        <td>{{ $group->description }}</td>
                    @else
                        <td></td>
                    @endif
                    <td>{{ $group->cameras->count() }}</td>
                    <td>
                        {{-- @can('update group') --}}
                        <a href="{{ url('groups/' . $group->id . '/edit') }}" class="btn btn-success">Edit</a>
                        {{-- @endcan --}}

                        {{-- @can('delete group') --}}
                        <form id="delete-group-form" method="POST" style="display: none;">
                            @csrf
                            @method('DELETE')
                        </form>
                        <button onclick="confirmDelete('{{ route('groups.destroy', $group->id) }}')"
                            class="btn btn-danger">Delete</button>
                        {{-- @endcan --}}
                    </td>
                </tr>
            @endforeach

        </tbody>
    </table>
    <script>
        setTimeout(function() {
            $('.alert').fadeOut('slow');
        }, 3000);

        function confirmDelete(url) {
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    let form = document.getElementById('delete-group-form');
                    form.action = url;
                    form.submit();
                }
            });
        }
    </script>
@endsection
