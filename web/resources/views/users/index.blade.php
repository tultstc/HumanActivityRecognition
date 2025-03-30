@extends('layouts.app')

@section('content')
    @if (session()->has('status'))
        <div class="alert alert-success mb-1">
            {{ session()->get('status') }}
        </div>
    @endif

    <div>
        <a href="{{ url('users/create') }}" class="btn btn-primary float-start mt-1">{{ __('messages.add_user') }}</a>
    </div>
    <table id="datatable" class="table table-striped" style="width:100%">
        <thead>
            <tr>
                <th>Id</th>
                <th>{{ __('messages.name') }}</th>
                <th>Email</th>
                <th>{{ __('messages.roles') }}</th>
                <th>{{ __('messages.action') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($users as $user)
                <tr>
                    <td>{{ $user->id }}</td>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td>
                        @if (!empty($user->getRoleNames()))
                            @foreach ($user->getRoleNames() as $rolename)
                                <label class="badge bg-primary mx-1">{{ $rolename }}</label>
                            @endforeach
                        @endif
                    </td>
                    <td>
                        @can('update user')
                            <a href="{{ url('users/' . $user->id . '/edit') }}"
                                class="btn btn-success">{{ __('messages.edit') }}</a>
                        @endcan

                        @can('delete user')
                            <button onclick="deleteUser('{{ $user->id }}');"
                                class="btn btn-danger mx-2">{{ __('messages.delete') }}</button>
                        @endcan
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
        function deleteUser(userId) {
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
                        url: `/users/${userId}/delete`,
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
                            Swal.fire('Error!', 'An error occurred while deleting the user.',
                                'error');
                        }
                    });
                }
            });
        }
    </script>
@endsection
