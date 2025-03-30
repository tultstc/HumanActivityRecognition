@props(['camera'])
<div class="border rounded-md shadow overflow-hidden" x-data="{
    snapshotUrl: '',
    init() {
        this.snapshotUrl = `http://${window.location.hostname}:15440/get_snapshot/{{ $camera->id }}`;
        this.loadSnapshot();

        Livewire.on('pageChanged', () => {
            this.loadSnapshot();
        });
    },
    loadSnapshot() {
        const imgElement = document.getElementById('camera-snapshot-{{ $camera->id }}');
        if (imgElement) {
            imgElement.src = this.snapshotUrl;
        }
    }
}">
    <!-- Camera Preview (placeholder) -->
    <div class="aspect-video border-b flex items-center justify-center">
        <img src="images/no_single.jpg" alt="Last Frame" class="w-full h-full" id="camera-snapshot-{{ $camera->id }}">
    </div>

    <!-- Camera Info -->
    <div class="p-3">
        <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ $camera->ten }}</h3>

        <div class="space-y-2 text-sm text-gray-600">
            <div class="flex items-center">
                <span class="font-medium mr-2">{{ __('messages.name') }}:</span>
                {{ $camera->name ?? 'N/A' }}
            </div>
            {{-- <div class="flex items-center">
                <span class="font-medium mr-2">Position:</span>
                {{ $camera->position->ten ?? 'N/A' }}
            </div> --}}
            <div class="flex items-center">
                <span class="font-medium mr-2">{{ __('messages.status') }}:</span>

                @switch($camera->status)
                    @case(0)
                        <span
                            class="inline-flex rounded-full px-2 text-xs font-semibold leading-5 bg-red-100 text-red-800">{{ __('messages.inactive') }}</span>
                    @break

                    @case(1)
                        <span
                            class="inline-flex rounded-full px-2 text-xs font-semibold leading-5 bg-green-100 text-green-800">{{ __('messages.active') }}</span>
                    @break

                    @default
                        <span
                            class="inline-flex rounded-full px-2 text-xs font-semibold leading-5 bg-red-100 text-red-800">{{ __('messages.inactive') }}</span>
                @endswitch
            </div>
        </div>

        <!-- Actions -->
        <div class="mt-2 flex items-center justify-end space-x-2">
            @can('update camera')
                <a class="nav-link" href="{{ url('cameras/' . $camera->id . '/edit') }}">
                    <i class="fa-solid fa-pencil"></i>
                </a>
            @endcan

            @can('delete camera')
                <button onclick="deleteCamera('{{ $camera->id }}');">
                    <svg class="!w-[24px] !h-[24px]">
                        <use xlink:href="node_modules/@coreui/icons/sprites/free.svg#cil-trash"></use>
                    </svg>
                </button>
            @endcan
        </div>
    </div>
</div>
<script>
    function deleteCamera(cameraId) {
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
                    url: `/cameras/${cameraId}/delete`,
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
                        Swal.fire('Error!', 'An error occurred while deleting the camera.',
                            'error');
                    }
                });
            }
        });
    }
</script>
