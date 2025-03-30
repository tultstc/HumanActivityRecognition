@props(['name' => 'locationId', 'areas'])

<div x-data="{
    isOpen: false,
    selectedValue: '',
    selectedLabel: '',
    areas: {{ json_encode($areas) }},
    expandedNodes: new Set({{ json_encode(collect($areas)->map(fn($area) => 'area-' . $area['id'])->all()) }}),

    toggleDropdown() {
        this.isOpen = !this.isOpen;
    },

    toggleNode(nodeId) {
        if (this.expandedNodes.has(nodeId)) {
            this.expandedNodes.delete(nodeId);
        } else {
            this.expandedNodes.add(nodeId);
        }
    },

    isExpanded(nodeId) {
        return this.expandedNodes.has(nodeId);
    },

    selectItem(value, label) {
        this.selectedValue = value;
        this.selectedLabel = label;
        this.isOpen = false;
    }
}" class="relative">
    {{-- Hidden input for form submission --}}
    <input type="hidden" :name="'{{ $name }}'" :value="selectedValue">

    {{-- Input field --}}
    <div @click="toggleDropdown"
        class="w-full cursor-pointer bg-white border border-gray-300 rounded-md px-[12px] py-[6px] text-left focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
        <span x-text="selectedLabel || 'Select an option'" class="block truncate"></span>
        <span class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none">
            <svg class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="none" stroke="currentColor">
                <path d="M7 7l3-3 3 3m0 6l-3 3-3-3" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
        </span>
    </div>

    {{-- Dropdown menu --}}
    <div x-show="isOpen" @click.away="isOpen = false" x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 transform scale-95" x-transition:enter-end="opacity-100 transform scale-100"
        x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 transform scale-100"
        x-transition:leave-end="opacity-0 transform scale-95"
        class="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg">
        <ul class="max-h-60 overflow-auto !pl-0 !mb-0">
            {{-- Areas --}}
            @foreach ($areas as $area)
                <li class="group">
                    <div class="flex items-center px-3 py-2 hover:bg-gray-100 cursor-pointer text-gray-900 font-medium"
                        @click="toggleNode('area-{{ $area['id'] }}')">
                        <span class="mr-2" x-show="!isExpanded('area-{{ $area['id'] }}')">&plus;</span>
                        <span class="mr-2" x-show="isExpanded('area-{{ $area['id'] }}')">&minus;</span>
                        {{ $area['ten'] }}
                    </div>
                    {{-- Positions --}}
                    @if (isset($area['positions']) && count($area['positions']) > 0)
                        <ul class="pl-6" x-show="isExpanded('area-{{ $area['id'] }}')"
                            x-transition:enter="transition-all ease-out duration-200"
                            x-transition:enter-start="opacity-0 max-h-0"
                            x-transition:enter-end="opacity-100 max-h-screen"
                            x-transition:leave="transition-all ease-in duration-200"
                            x-transition:leave-start="opacity-100 max-h-screen"
                            x-transition:leave-end="opacity-0 max-h-0">
                            @foreach ($area['positions'] as $position)
                                <li>
                                    <div @click.stop="selectItem('{{ $position['id'] }}', '{{ $position['ten'] }}')"
                                        class="pl-5 px-3 py-2 hover:bg-gray-100 cursor-pointer text-gray-700">
                                        {{ $position['ten'] }}
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </li>
            @endforeach
        </ul>
    </div>
</div>
