@props(['camera'])
<style>
    .group-scroll::-webkit-scrollbar {
        display: none;
    }

    .group-scroll {
        -ms-overflow-style: none;
        scrollbar-width: none;
    }
</style>
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
            <div class="flex cursor-pointer">
                <p class="font-medium mr-2 mb-0">Group:</p>
                <div class="flex overflow-x-auto space-x-2 group-scroll" x-data="{
                    isDown: false,
                    startX: 0,
                    scrollLeft: 0,
                    init() {
                        this.$el.addEventListener('mousedown', (e) => {
                            this.isDown = true;
                            this.startX = e.pageX - this.$el.offsetLeft;
                            this.scrollLeft = this.$el.scrollLeft;
                        });
                
                        this.$el.addEventListener('mouseleave', () => {
                            this.isDown = false;
                        });
                
                        this.$el.addEventListener('mouseup', () => {
                            this.isDown = false;
                        });
                
                        this.$el.addEventListener('mousemove', (e) => {
                            if (!this.isDown) return;
                            e.preventDefault();
                            const x = e.pageX - this.$el.offsetLeft;
                            const walk = (x - this.startX) * 2;
                            this.$el.scrollLeft = this.scrollLeft - walk;
                        });
                    }
                }">
                    @foreach ($camera->groups as $group)
                        <a href="{{ route('groups.edit', $group->id) }}"
                            class="inline-flex no-underline rounded-full px-2 text-xs font-semibold leading-5 bg-blue-100 text-blue-800 mx-1 mb-0 whitespace-nowrap">
                            {{ $group->name }}
                        </a>
                    @endforeach
                </div>
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
