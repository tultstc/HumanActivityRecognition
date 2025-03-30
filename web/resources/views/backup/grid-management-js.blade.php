{{-- <script>
    let grid;
    let currentLayout = {
        rows: 2,
        cols: 2,
        totalCells: 4
    };
    let gridCells = []; // Track empty cells
    let addedCameras = new Set();

    function initializeGrid() {
        if (grid) {
            grid.destroy(false);
        }

        const gridWidth = 12;
        const itemWidth = Math.floor(gridWidth / currentLayout.cols);

        grid = GridStack.init({
            column: gridWidth,
            maxRow: currentLayout.rows,
            cellHeight: '50%',
            float: true,
            disableResize: true,
            disableDrag: false,
            staticGrid: false
        });

        grid.on('change', function(event, items) {
            saveLayout();
        });

        // Create empty grid cells
        createEmptyCells();

        return grid;
    }

    function createEmptyCells() {
        grid.removeAll();
        gridCells = [];
        addedCameras.clear();

        for (let i = 0; i < currentLayout.totalCells; i++) {
            const row = Math.floor(i / currentLayout.cols);
            const col = i % currentLayout.cols;
            const itemWidth = Math.floor(12 / currentLayout.cols);

            const element = createEmptyCell(i);
            grid.makeWidget(element, {
                x: col * itemWidth,
                y: row,
                w: itemWidth,
                h: 1,
                autoPosition: false,
                noResize: true,
                noMove: false
            });

            gridCells.push({
                index: i,
                cameraId: null,
                element: element
            });
        }
    }


    function createEmptyCell(index) {
        const div = document.createElement('div');
        div.className = 'grid-stack-item';
        div.setAttribute('data-cell-type', 'empty');
        div.setAttribute('data-cell-index', index);
        div.innerHTML = `
        <div class="grid-stack-item-content bg-gray-100 rounded-lg flex items-center justify-center">
            <span class="text-gray-500">Camera Slot ${index + 1}</span>
        </div>
    `;
        return div;
    }

    function createCameraCell(camera, index) {
        const div = document.createElement('div');
        div.className = 'grid-stack-item';
        const canvasId = `canvas-${camera.id}`;
        div.setAttribute('gridstack-id', canvasId);
        div.setAttribute('data-cell-type', 'camera');
        div.setAttribute('data-camera-id', camera.id);
        div.innerHTML = `
        <div class="grid-stack-item-content relative">
            <canvas class="w-full h-full" id="${canvasId}"></canvas>
        </div>
    `;
        return div;
    }

    function changeLayout(layout) {
        const [rows, cols] = layout.split('x').map(Number);
        currentLayout = {
            rows: rows,
            cols: cols,
            totalCells: rows * cols
        };

        addedCameras.clear(); // Clear the set of added cameras
        initializeGrid();
        saveLayout();
    }


    async function loadCameras(locationId) {
        try {
            const response = await fetch(`/dashboard/cameras?locationId=${locationId}`);
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);

            const cameras = await response.json();
            const camerasList = document.getElementById(`cameras-${locationId}`);

            camerasList.innerHTML = cameras.map(camera => `
                <div class="camera-item cursor-pointer p-2 hover:bg-gray-100" 
                     onclick="assignCameraToNextCell(${JSON.stringify(camera).replace(/"/g, '&quot;')})"
                     data-camera-id="${camera.id}">
                    <i class="fas fa-video"></i> ${camera.ten}
                </div>
            `).join('');

            camerasList.style.display = 'block';
        } catch (error) {
            console.error('Error loading cameras:', error);
        }
    }

    function assignCameraToNextCell(camera) {
        // Check if the camera has already been added
        if (addedCameras.has(camera.id)) {
            alert('This camera has already been added to the grid.');
            return;
        }

        // Find first empty cell or the last cell
        let targetCell = gridCells.find(cell => !cell.cameraId) || gridCells[gridCells.length - 1];

        if (!targetCell) {
            alert('No available cells to add the camera.');
            return;
        }

        const row = Math.floor(targetCell.index / currentLayout.cols);
        const col = targetCell.index % currentLayout.cols;
        const itemWidth = Math.floor(12 / currentLayout.cols);

        // Remove existing widget if any
        const existingWidget = grid.engine.nodes.find(
            node => node.x === col * itemWidth && node.y === row
        );
        if (existingWidget) {
            grid.removeWidget(existingWidget.el);
        }

        // Add camera widget
        const cameraElement = createCameraCell(camera, targetCell.index);
        grid.makeWidget(cameraElement, {
            x: col * itemWidth,
            y: row,
            w: itemWidth,
            h: 1,
            autoPosition: false,
            noResize: true,
            noMove: false
        });

        // Update cell state
        targetCell.cameraId = camera.id;
        targetCell.element = cameraElement;

        // Add camera to the set of added cameras
        addedCameras.add(camera.id);

        // Initialize stream
        initializeStream(camera.id);

        saveLayout();
    }


    function initializeStream(cameraId) {
        loadPlayer({
            url: `ws://${window.location.hostname}:3000/api/stream/${cameraId}`,
            canvas: document.getElementById(`canvas-${cameraId}`)
        });
    }

    function toggleArea(element) {
        const positions = element.nextElementSibling;
        positions.style.display = positions.style.display === 'none' ? 'block' : 'none';
    }

    function togglePosition(element) {
        // Toggle active state
        document.querySelectorAll('.location-item').forEach(item => {
            item.classList.remove('active');
        });
        element.classList.add('active');

        // Toggle cameras list
        const camerasList = element.nextElementSibling;
        camerasList.style.display = camerasList.style.display === 'none' ? 'block' : 'none';
    }

    async function saveLayout() {
        try {
            // Get all grid items and create a Map to track unique positions
            const gridItems = new Map();

            grid.engine.nodes.forEach(node => {
                const element = node.el;
                const cellType = element.getAttribute('data-cell-type');
                const cameraId = element.getAttribute('data-camera-id');
                const cellIndex = element.getAttribute('data-cell-index');

                // Create a unique position key based on x and y coordinates
                const positionKey = `${node.x},${node.y}`;

                // If this position already has an item and the current item is a camera,
                // or if this position is empty and hasn't been filled yet
                if (!gridItems.has(positionKey) || cellType === 'camera') {
                    gridItems.set(positionKey, {
                        id: cameraId || `empty-slot-${cellIndex}`,
                        type: cellType || 'empty',
                        x: node.x,
                        y: node.y,
                        w: node.w,
                        h: node.h
                    });
                }
            });

            // Convert Map values to array for the final layout
            const layout = Array.from(gridItems.values());

            const response = await fetch('/save-layout', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    layout,
                    gridConfig: {
                        column: 12,
                        itemWidth: Math.floor(12 / currentLayout.cols),
                        maxRow: currentLayout.rows
                    }
                })
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
        } catch (error) {
            console.error('Error saving layout:', error);
        }
    }

    function isCameraItem(item) {
        return item && item.type === 'camera' && !item.id.startsWith('empty-slot-');
    }

    async function getCameraById(cameraId) {
        try {
            const response = await fetch(`/cameras/${cameraId}`);
            if (!response.ok) {
                throw new Error(`Failed to fetch camera ${cameraId}: ${response.status}`);
            }
            const camera = await response.json();
            if (!camera) {
                throw new Error(`No camera found with ID ${cameraId}`);
            }
            return camera;
        } catch (error) {
            console.error('Error in getCameraById:', error);
            return null;
        }
    }

    function validateLayoutData(layout) {
        if (!Array.isArray(layout)) return false;

        return layout.every(item => {
            return (
                item &&
                typeof item.id === 'string' &&
                typeof item.type === 'string' &&
                typeof item.x === 'number' &&
                typeof item.y === 'number' &&
                typeof item.w === 'number' &&
                typeof item.h === 'number'
            );
        });
    }

    function assignCameraToCell(camera, x, y) {
        if (addedCameras.has(camera.id)) {
            return;
        }

        const itemWidth = Math.floor(12 / currentLayout.cols);
        const cameraElement = createCameraCell(camera);

        grid.makeWidget(cameraElement, {
            x: x,
            y: y,
            w: itemWidth,
            h: 1,
            autoPosition: false,
            noResize: true,
            noMove: false
        });

        addedCameras.add(camera.id);
        initializeStream(camera.id);
    }

    // Initialize when document is ready
    document.addEventListener('DOMContentLoaded', async () => {
        try {
            // Load saved layout
            const response = await fetch('/get-layout');
            const data = await response.json();

            if (data.gridConfig) {
                const cols = 12 / data.gridConfig.itemWidth;
                currentLayout = {
                    rows: 2,
                    cols: cols,
                    totalCells: 2 * cols
                };
            }

            // Initialize empty grid first
            initializeGrid();

            // If we have saved layout data, restore cameras
            if (data.layout && Array.isArray(data.layout)) {
                // Filter only cameras item
                const cameraItems = data.layout.filter(isCameraItem);

                // Restore camrea
                for (const item of cameraItems) {
                    try {
                        const cameraId = item.id;
                        const camera = await getCameraById(cameraId);

                        if (camera) {
                            // Calculate camera position
                            const col = item.x / (12 / currentLayout.cols);
                            const row = item.y;
                            const cellIndex = row * currentLayout.cols + col;

                            // Find camera position
                            const targetCell = gridCells[cellIndex];

                            if (targetCell) {
                                // Remove existing content if any
                                if (targetCell.element) {
                                    grid.removeWidget(targetCell.element);
                                }

                                // Create and add camera widget
                                const cameraElement = createCameraCell(camera, cellIndex);
                                grid.makeWidget(cameraElement, {
                                    x: item.x,
                                    y: item.y,
                                    w: item.w,
                                    h: item.h,
                                    autoPosition: false,
                                    noResize: true,
                                    noMove: false
                                });

                                // Update cell state
                                targetCell.cameraId = camera.id;
                                targetCell.element = cameraElement;
                                addedCameras.add(camera.id);

                                // Initialize stream
                                initializeStream(camera.id);
                            }
                        }
                    } catch (error) {
                        console.error(`Error restoring camera ${item.id}:`, error);
                    }
                }
            }

            // Setup layout option events
            document.querySelectorAll('#layoutOptions .location-item').forEach(item => {
                item.addEventListener('click', () => {
                    const layout = item.getAttribute('data-layout');
                    changeLayout(layout);
                });
            });
        } catch (error) {
            console.error('Error initializing dashboard:', error);
            // Initialize with default grid if loading fails
            initializeGrid();
        }
    });
</script> --}}
