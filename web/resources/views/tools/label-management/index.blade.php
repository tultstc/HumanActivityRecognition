@extends('layouts.app')

@section('content')
    @if (session()->has('status'))
        <div class="alert alert-success mb-1">
            {{ session()->get('status') }}
        </div>
    @endif

    <div>
        <a href="{{ route('label-management.create') }}" class="btn btn-primary float-start mt-1">{{ __('Add Label') }}</a>
    </div>
    <table id="datatable" class="table table-striped" style="width:100%">
        <thead>
            <tr>
                <th>Id</th>
                <th>{{ __('messages.name') }}</th>
                <th>Type</th>
                <th>{{ __('messages.status') }}</th>
                <th>{{ __('messages.action') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($labels as $label)
                <tr>
                    <td>{{ $label->id }}</td>
                    <td>{{ $label->name }}</td>
                    <td>{{ $label->type }}</td>
                    <td>{{ $label->status }}</td>
                    <td>
                        {{-- @can('update label') --}}
                        <a href="{{ url('label-management/' . $label->id . '/edit') }}"
                            class="btn btn-success">{{ __('messages.edit') }}</a>
                        {{-- @endcan --}}

                        {{-- @can('delete label') --}}
                        <button onclick="deleteLabel('{{ $label->id }}');"
                            class="btn btn-danger mx-2">{{ __('messages.delete') }}</button>
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
    </script>
    <script>
        function deleteLabel(labelId) {
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
                    $.ajax({
                        url: `/label-management/${labelId}/delete`,
                        type: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            if (response.success) {
                                Swal.fire('Deleted!', response.message, 'success').then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire('Error!', response.message, 'error');
                            }
                        },
                        error: function(xhr) {
                            Swal.fire('Error!', 'An error occurred while deleting the label.',
                                'error');
                        }
                    });
                }
            });
        }
    </script>
@endsection
