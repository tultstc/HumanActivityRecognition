@extends('layouts.app')

@section('content')
    <div class="grid grid-cols-2 gap-2 h-[85vh]">
        <div class="relative h-full w-full overflow-hidden">
            <button id="dateRangeBtn" class="btn btn-primary float-end ml-2">
                <i class="fas fa-calendar"></i>
            </button>
            <table id="datatableScroll" class="table table-striped w-full">
                <thead>
                    <tr>
                        <th>Id</th>
                        <th>{{ __('messages.start_error_time') }}</th>
                        <th>{{ __('messages.end_error_time') }}</th>
                        @can('delete event')
                            <th>{{ __('messages.action') }}</th>
                        @endcan
                    </tr>
                </thead>
            </table>
        </div>
        <div id="eventDetail" class="p-2 h-full w-full">
            <div class="grid grid-cols-2 gap-2 mb-2">
                <p class="text-muted mb-0">Next Cleanup: <span id="next-cleanup">--</span></p>
                <select class="form-select" id="cleanup-period" name="period">
                    <option value="1">After 1 day</option>
                    <option value="3">After 3 days</option>
                    <option value="5">After 5 days</option>
                    <option value="10">After 10 days</option>
                    <option value="30">After 1 month</option>
                    <option value="60">After 2 months</option>
                    <option value="90">After 3 months</option>
                </select>
            </div>
            <div class="no-event-selected text-center text-gray-500">
                {{ __('messages.event_alert') }}
            </div>

            <div class="event-detail-content hidden h-full">
                <div class="grid grid-rows-3 gap-4 h-full">
                    <div class="row-span-2 h-full">
                        <div class="relative h-full w-full bg-[#212631]">
                            <img id="detail-image" src="" alt="Picture"
                                class="absolute inset-0 w-full h-full object-contain">
                        </div>
                    </div>
                    <div class="row-span-1">
                        <h3 class="text-xl font-bold mb-4">{{ __('messages.event_detail') }}</h3>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="inline-flex">
                                <p class="font-semibold mr-2 mb-0">{{ __('messages.description') }}:</p>
                                <p id="" class="break-words whitespace-pre-wrap">{{ __('messages.event_description') }}</p>
                            </div>
                            <div class="inline-flex">
                                <p class="font-semibold mr-2 mb-0">Camera:</p>
                                <p id="detail-camera" class="mb-0"></p>
                            </div>
                            <div class="inline-flex">
                                <p class="font-semibold mr-2 mb-0">{{ __('messages.start_error_time') }}:</p>
                                <p id="detail-start-time" class="mb-0"></p>
                            </div>
                            <div class="inline-flex">
                                <p class="font-semibold mr-2 mb-0">{{ __('messages.end_error_time') }}:</p>
                                <p id="detail-end-time" class="mb-0"></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Cleanup --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const DEFAULT_PERIOD = '90';
            const nextCleanupElement = document.getElementById('next-cleanup');
            const cleanupPeriodSelect = document.getElementById('cleanup-period');

            function loadSettings() {
                fetch('/api/settings/event-cleanup', {
                        method: 'GET',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        const period = data.period || DEFAULT_PERIOD;
                        cleanupPeriodSelect.value = period;

                        if (data.next_cleanup) {
                            nextCleanupElement.textContent = data.next_cleanup;
                        } else {
                            updateCleanupPeriod(period);
                        }
                    })
                    .catch(error => {
                        console.error('Error loading settings:', error);
                        cleanupPeriodSelect.value = DEFAULT_PERIOD;
                        updateCleanupPeriod(DEFAULT_PERIOD);
                    });
            }

            function updateCleanupPeriod(period) {
                fetch('/api/settings/event-cleanup', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            period: period
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        nextCleanupElement.textContent = data.next_cleanup;
                    })
                    .catch(error => {
                        console.error('Error updating cleanup period:', error);
                    });
            }

            loadSettings();

            cleanupPeriodSelect.addEventListener('change', function() {
                updateCleanupPeriod(this.value);
                Swal.fire({
                    title: 'Success!',
                    text: 'Successfully updated the event cleanup period.',
                    icon: 'success',
                    confirmButtonText: 'OK'
                });
            });
        });
    </script>
    {{-- Detail --}}
    <script>
        $(document).ready(function() {
            const urlParams = new URLSearchParams(window.location.search);
            const selectedEventId = urlParams.get('selected');
            const pageNumber = urlParams.get('page') || 1;

            let startDate = '';
            let endDate = '';

            const dateRangePicker = flatpickr("#dateRangeBtn", {
                mode: "range",
                dateFormat: "m/d/Y",
                onChange: function(selectedDates) {
                    if (selectedDates.length === 2) {
                        startDate = flatpickr.formatDate(selectedDates[0], "m/d/Y");
                        endDate = flatpickr.formatDate(selectedDates[1], "m/d/Y");
                        table.draw();
                    }
                },
                onClose: function(selectedDates) {
                    if (selectedDates.length !== 2) {
                        startDate = '';
                        endDate = '';
                        table.draw();
                    }
                }
            });

            const table = new DataTable("#datatableScroll", {
                processing: true,
                serverSide: true,
                dom: '<"d-flex justify-between align-items-center"<"col"B><"col"f>>rt<"d-flex justify-between mt-2"<"col"l><"col flex justify-end"p>>',
                paging: true,
                fixedColumns: true,
                scrollCollapse: true,
                scrollY: '650px',
                lengthMenu: [10, 25, 50, 100, {
                    label: 'All',
                    value: -1
                }],
                pageLength: 25,
                displayStart: (pageNumber - 1) * 25,
                order: [
                    [1, 'desc']
                ],
                layout: {
                    topStart: {
                        buttons: ['copy', 'csv', 'excel', 'pdf', 'print']
                    }
                },
                ajax: {
                    url: "{{ route('events.data') }}",
                    type: "GET",
                    data: function(d) {
                        d.page = pageNumber;
                        d.start_date = startDate;
                        d.end_date = endDate;
                        return d;
                    }
                },
                columns: [{
                        data: 'id',
                        name: 'id'
                    },
                    {
                        data: 'start_error_time',
                        name: 'start_error_time'
                    },
                    {
                        data: 'end_error_time',
                        name: 'end_error_time'
                    },
                    @can('delete event')
                        {
                            data: 'action',
                            name: 'action',
                            orderable: false,
                            searchable: false
                        }
                    @endcan
                ],
                drawCallback: function(settings) {
                    $('#datatableScroll tbody tr').addClass('cursor-pointer');
                    if (selectedEventId) {
                        setTimeout(() => {
                            const row = $(`#datatableScroll tbody tr`).filter(function() {
                                return table.row(this).data()?.id === parseInt(
                                    selectedEventId);
                            });
                            if (row.length) {
                                row.addClass('selected');
                                handleRowSelection(row, selectedEventId);
                            }
                        }, 100);
                    }
                }
            });

            function handleRowSelection(row, eventId) {
                row[0].scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });

                fetch(`/events/${eventId}`)
                    .then(response => response.json())
                    .then(event => {
                        updateEventDetails(event);
                    })
                    .catch(error => console.error('Error:', error));
            }

            function updateEventDetails(event) {
                $('.no-event-selected').addClass('hidden');
                $('.event-detail-content').removeClass('hidden');

                $('#detail-start-time').text(event.start_error_time);
                $('#detail-end-time').text(event.end_error_time);
                $('#detail-description').text(event.description);
                $('#detail-camera').text(event.camera['name']);
                $('#detail-image').attr('src', `http://${window.location.hostname}:15440/image/${event.url}`);
            }

            $('#datatableScroll tbody').on('click', 'tr', function() {
                const rowData = table.row(this).data();
                const eventId = rowData.id;
                const currentPage = table.page() + 1;

                $('#datatableScroll tbody tr').removeClass('selected');
                $(this).addClass('selected');

                const newUrl = `${window.location.pathname}?page=${currentPage}&selected=${eventId}`;
                history.pushState(null, '', newUrl);

                handleRowSelection($(this), eventId);
            });

            $(window).on('change', function() {
                table.draw();
            });
        });

        function deleteEvent(eventId) {
            Swal.fire({
                title: 'Are you sure?',
                text: "This action cannot be undone!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: `/events/${eventId}/delete`,
                        type: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            if (response.success) {
                                Swal.fire('Deleted!', response.message, 'success');
                                $('#datatableScroll').DataTable().ajax.reload();
                            } else {
                                Swal.fire('Error!', response.message, 'error');
                            }
                        },
                        error: function(xhr) {
                            Swal.fire('Error!', 'Failed to delete the event.', 'error');
                        }
                    });
                }
            });
        }
    </script>
@endsection
