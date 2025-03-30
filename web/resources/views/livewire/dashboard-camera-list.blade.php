<div>
    <div class="grid grid-cols-12 gap-2">
        <div class="col-span-3 relative p-3 shadow-md !rounded-none">
            <h5 class="text-lg font-semibold mb-2">Layout</h5>
            <div class="mb-4">
                <div class="list-group-item" wire:click.prevent="toggleLayoutOptions">
                    <i class="fas fa-angle-{{ $layoutOptionsOpen ? 'down' : 'right' }}"></i>
                    <i class="fas fa-table"></i>
                    Options
                </div>

                @if ($layoutOptionsOpen)
                    <div class="list-group !rounded-none">
                        <div class="list-group-item !pl-[40px] cursor-pointer" wire:click="changeLayout(2)">
                            <i class="fas fa-th"></i>
                            2x2
                        </div>
                        <div class="list-group-item !pl-[40px] cursor-pointer" wire:click="changeLayout(3)">
                            <i class="fas fa-th"></i>
                            2x3
                        </div>
                        <div class="list-group-item !pl-[40px] cursor-pointer" wire:click="changeLayout(4)">
                            <i class="fas fa-th"></i>
                            2x4
                        </div>
                    </div>
                @endif
            </div>

            <div class="mb-4">
                <h5 class="text-lg font-semibold mb-2">Locations</h5>
                <div class="list-group">
                    @foreach ($this->locationGroups as $index => $location)
                        <div>
                            <div class="list-group-item" wire:click.prevent="toggleNode('{{ $index }}')">
                                <i class="fas fa-angle-{{ $openNodes[$index] ? 'down' : 'right' }}"></i>
                                <i class="fas fa-building"></i>
                                {{ $location['label'] }}
                            </div>
                            @if ($openNodes[$index])
                                @foreach ($location['children'] as $positionIndex => $position)
                                    <a href="#"
                                        class="list-group-item !pl-[40px] {{ $selectedLocationId == $position['id'] ? 'active' : '' }}"
                                        wire:click.prevent="selectLocation('{{ $position['id'] }}')">
                                        <i class="fas fa-video"></i>
                                        {{ $position['label'] }}
                                    </a>
                                @endforeach
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="col-span-9">
            <div class="grid-stack">
                @foreach ($this->cameras as $camera)
                    <div class="grid-stack-item" gs-id="canvas-{{ $camera['id'] }}">
                        <div class="grid-stack-item-content">
                            <canvas class="w-full h-full" id="canvas-{{ $camera['id'] }}"></canvas>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<script src="node_modules/gridstack/dist/gridstack-all.js"></script>

<script>
    window.csrfToken = '{{ csrf_token() }}';

    document.addEventListener('livewire:initialized', () => {
        Livewire.on('changeLayout', (data) => {
            changeLayout(data.columns);
        });
    });

    let currentLayout = {
        column: 12,
        itemWidth: 6,
        maxRow: 2
    };

    const grid = GridStack.init({
        column: currentLayout.column,
        maxRow: currentLayout.maxRow,
        cellHeight: '300px',
        float: false
    });

    // Set default 2x2 layout when page loads
    function setDefaultLayout() {
        const items = grid.engine.nodes;
        items.forEach((item, index) => {
            const row = Math.floor(index / 2); // 2 columns
            const col = index % 2;

            if (row < currentLayout.maxRow) {
                grid.update(item.el, {
                    x: col * 6, // 6 units width per item for 2x2
                    y: row,
                    w: 6,
                    h: 1
                });
            }
        });
        saveLayout();
    }

    function changeLayout(columns) {
        const newItemWidth = Math.floor(12 / columns);

        currentLayout = {
            column: 12,
            itemWidth: newItemWidth,
            maxRow: 2
        };

        grid.column(currentLayout.column);
        grid.opts.maxRow = currentLayout.maxRow;

        const items = grid.engine.nodes;
        items.forEach((item, index) => {
            const row = Math.floor(index / columns);
            const col = index % columns;

            if (row < currentLayout.maxRow) {
                grid.update(item.el, {
                    x: col * newItemWidth,
                    y: row,
                    w: newItemWidth,
                    h: 1
                });
            }
        });

        saveLayout();
    }

    function saveLayout() {
        const layout = grid.engine.nodes.map(node => ({
            id: node.el.querySelector('canvas').id,
            x: node.x,
            y: node.y,
            w: node.w,
            h: node.h
        }));

        fetch('/save-layout', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': window.csrfToken
                },
                body: JSON.stringify({
                    layout,
                    gridConfig: currentLayout
                })
            })
            .then(response => response.json())
            .catch(error => console.error('Error saving layout:', error));
    }

    // Load saved layout or use default 2x2
    fetch('/get-layout')
        .then(response => response.json())
        .then(data => {
            if (data.layout && Array.isArray(data.layout) && data.layout.length > 0) {
                if (data.gridConfig) {
                    currentLayout = data.gridConfig;
                    grid.column(currentLayout.column);
                    grid.opts.maxRow = currentLayout.maxRow;
                }

                data.layout.forEach(item => {
                    const gridItem = grid.engine.nodes.find(
                        node => node.el.querySelector('canvas').id === item.id
                    );
                    if (gridItem) {
                        grid.update(gridItem.el, {
                            x: item.x,
                            y: item.y,
                            w: item.w || currentLayout.itemWidth,
                            h: item.h || 1
                        });
                    }
                });
            } else {
                // If no saved layout, set default 2x2
                setDefaultLayout();
            }
        })
        .catch(error => {
            console.error('Error loading layout:', error);
            // If error loading, still set default layout
            setDefaultLayout();
        });

    // Save layout on change
    grid.on('change', saveLayout);
</script>
