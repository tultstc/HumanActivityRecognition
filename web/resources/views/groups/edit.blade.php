@extends('layouts.app')

@section('content')
    <div>
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
                            <a href="{{ route('groups') }}" class="btn btn-danger float-end">Back</a>
                        </h4>
                    </div>
                    <div class="card-body">
                        <form action="{{ url('groups/' . $group->id) }}" method="POST">
                            @csrf
                            @method('PUT')

                            <div class="mb-3">
                                <label for="">Name</label>
                                <input type="text" name="name" value="{{ old('name', $group->name) }}"
                                    class="form-control" />
                            </div>
                            <div class="mb-3">
                                <label for="">Description</label>
                                <input type="text" name="description"
                                    value="{{ old('description', $group->description) }}" class="form-control" />
                            </div>
                            <div class="mb-3">
                                <label for="cameras">Select Cameras</label>
                                <select name="cameras[]" id="cameras" class="form-control" multiple>
                                    @foreach ($cameras as $camera)
                                        <option value="{{ $camera->id }}"
                                            {{ in_array($camera->id, $selectedCameras) ? 'selected' : '' }}>
                                            {{ $camera->name }} (ID: {{ $camera->id }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-group">
                                <h3 class="mb-3">Cameras In Group</h3>
                                <div id="camerasContainer" class="row">
                                    @include('groups.partials.camera-list', [
                                        'cameras' => $group->cameras->take(6),
                                    ])
                                </div>

                                @if ($group->cameras->count() > 6)
                                    <div id="paginationContainer"
                                        class="d-flex justify-content-center align-items-center mt-3">
                                        <button id="prevCameras" class="btn btn-sm btn-outline-secondary mr-2"
                                            data-group-id="{{ $group->id }}" data-current-page="1" disabled>
                                            <i class="fas fa-chevron-left"></i> Previous
                                        </button>
                                        <span id="pageInfo" class="mx-3">Page <span id="currentPage">1</span> of <span
                                                id="totalPages">{{ ceil($group->cameras->count() / 6) }}</span></span>
                                        <button id="nextCameras" class="btn btn-sm btn-outline-secondary ml-2"
                                            data-group-id="{{ $group->id }}" data-current-page="1"
                                            data-total-pages="{{ ceil($group->cameras->count() / 6) }}">
                                            Next <i class="fas fa-chevron-right"></i>
                                        </button>
                                    </div>
                                @endif
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
    <script>
        $(document).ready(function() {
            function updatePagination(currentPage, totalPages) {
                $('#currentPage').text(currentPage);
                $('#prevCameras').prop('disabled', currentPage === 1);
                $('#nextCameras').prop('disabled', currentPage === totalPages);
            }

            $('#nextCameras, #prevCameras').on('click', function() {
                const button = $(this);
                const groupId = button.data('group-id');
                const currentPage = parseInt($('#currentPage').text());
                const totalPages = parseInt($('#totalPages').text());

                let newPage;
                if (button.attr('id') === 'nextCameras') {
                    newPage = currentPage + 1;
                } else {
                    newPage = currentPage - 1;
                }

                if (newPage < 1 || newPage > totalPages) return;

                $.ajax({
                    url: `/groups/${groupId}/cameras`,
                    method: 'GET',
                    data: {
                        page: newPage
                    },
                    beforeSend: function() {
                        button.prop('disabled', true).addClass('disabled');
                    },
                    success: function(response) {
                        $('#camerasContainer').html(response.html);

                        updatePagination(newPage, totalPages);

                        button.prop('disabled', false).removeClass('disabled');
                    },
                    error: function() {
                        button.prop('disabled', false).removeClass('disabled');
                        alert('Error loading cameras');
                    }
                });
            });
        });
    </script>
@endsection
