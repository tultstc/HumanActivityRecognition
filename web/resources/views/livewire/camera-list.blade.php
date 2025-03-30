<div class="px-4 h-full flex flex-col">
    <div class="grid grid-cols-12 gap-4 flex-1">
        <!-- Sidebar Filters - 3 cols -->
        <div class="h-[420px] border col-span-2 rounded-md shadow p-4">
            <x-cameras.search :sort="$this->sort" :search="$this->search" />
        </div>

        <!-- Camera Grid - 9 cols -->
        <div class="col-span-10 flex flex-col">
            <!-- Camera Grid -->
            <div class="grid grid-cols-4 gap-4">
                @foreach ($this->cameras as $camera)
                    <x-cameras.camera-item wire:key="{{ $camera->id }}" :camera="$camera" />
                @endforeach
            </div>

            <!-- Pagination - Now in a dedicated section with proper spacing -->
            <div class="py-6">
                {{ $this->cameras->onEachSide(1)->links() }}
            </div>
        </div>
    </div>
</div>
