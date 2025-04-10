// Constants
const ZOOM_CONFIG = {
    STEP: 0.1,
    MAX: 3.0,
    MIN: 0.5,
    INITIAL: 1.0,
};

const KEYPOINT_NAMES = [
    "Nose",
    "Left Eye",
    "Right Eye",
    "Left Ear",
    "Right Ear",
    "Left Shoulder",
    "Right Shoulder",
    "Left Elbow",
    "Right Elbow",
    "Left Wrist",
    "Right Wrist",
    "Left Hip",
    "Right Hip",
    "Left Knee",
    "Right Knee",
    "Left Ankle",
    "Right Ankle",
];

// State Management
const state = {
    currentZoom: ZOOM_CONFIG.INITIAL,
    canLabel: true,
    currentCameraId: 1,
    currentFrameBlob: null,
    isDragging: false,
    dragStart: {
        x: 0,
        y: 0,
        scrollLeft: 0,
        scrollTop: 0,
    },
    currentSaveDir: ".",
    keypointsData: null,
    labelMappings: {},
    nextLabelIndex: 1,
};

// Keyboard Shortcuts Configuration
const SHORTCUTS = {
    c: () => openCameraModal(),
    d: () => openDirectoryModal(),
    "+": () => zoomIn(),
    "-": () => zoomOut(),
    n: () => handleNext(),
    l: () => detectPose(),
    s: () => handleSave(),
};

const TOOLBAR_ITEMS = {
    Cameras: "c",
    "Change Save Dir": "d",
    "Zoom In": "+",
    "Zoom Out": "-",
    Next: "n",
    Label: "l",
    Save: "s",
};

// Element Selectors
const getElements = () => ({
    imageContainer: document.querySelector(".col-span-8"),
    image: document.getElementById("currentFrame"),
    keypointsTable: document.getElementById("keypointsTable"),
    saveButton: document.querySelector('[onclick="handleSave()"]'),
    dirButton: document.querySelector('[onclick="handleChangeDir()"]'),
});

// Utility Functions
const utils = {
    getSnapshotUrl: (cameraId, includeTimestamp = false) => {
        const baseUrl = `http://${window.location.hostname}:15440/get_snapshot/${cameraId}`;
        return includeTimestamp
            ? `${baseUrl}?t=${new Date().getTime()}`
            : baseUrl;
    },

    updateImageSource: (src) => {
        const elements = getElements();
        if (elements.image) {
            elements.image.src = src;
        }
    },

    showNotification: (message, type = "success") => {
        const className =
            type === "success"
                ? "bg-green-100 text-green-800"
                : "bg-blue-100 text-blue-800";
        const notification = document.createElement("div");
        notification.className = `fixed top-20 left-[45%] ${className} px-4 py-2 rounded-md`;
        notification.textContent = message;
        document.body.appendChild(notification);
        setTimeout(() => notification.remove(), 3000);
    },

    validateKeypoints: (keypoints) => {
        if (!Array.isArray(keypoints) || keypoints.length !== 17) {
            throw new Error(
                "Invalid number of keypoints. Expected 17 keypoints.",
            );
        }
        keypoints.forEach((point, index) => {
            if (!point.hasOwnProperty("x") || !point.hasOwnProperty("y")) {
                throw new Error(`Invalid keypoint format at index ${index}`);
            }
        });
        return true;
    },

    formatKeypointsForDataset: (keypoints) => {
        return keypoints
            .map((point) => [point.x.toFixed(1), point.y.toFixed(1)])
            .flat();
    },
};

// UI Management
const UI = {
    clearKeypointsTable: () => {
        const elements = getElements();
        if (elements.keypointsTable) {
            elements.keypointsTable.innerHTML = "";
        }
    },

    updateKeypointsTable: (keypoints) => {
        const elements = getElements();
        if (!elements.keypointsTable) return;

        UI.clearKeypointsTable();
        keypoints.forEach((point, index) => {
            const row = elements.keypointsTable.insertRow();
            row.insertCell(0).textContent =
                KEYPOINT_NAMES[index] || `Point ${index + 1}`;
            row.insertCell(1).textContent = point.x.toFixed(2);
            row.insertCell(2).textContent = point.y.toFixed(2);
            row.insertCell(3).textContent = point.confidence.toFixed(3);
        });
    },

    updateLabelMappingsDisplay: () => {
        const mappingsDiv = document.getElementById("labelMappings");
        const entries = Object.entries(state.labelMappings);

        if (entries.length === 0) {
            mappingsDiv.innerHTML =
                '<em class="text-gray-500">No labels defined yet</em>';
            return;
        }

        entries.sort((a, b) => a[1] - b[1]);
        mappingsDiv.innerHTML = entries
            .map(
                ([label, index]) =>
                    `<div class="text-sm">${label}: ${index}</div>`,
            )
            .join("");
    },
};

// Zoom Handlers
const ZoomHandlers = {
    zoomIn: () => {
        if (state.currentZoom < ZOOM_CONFIG.MAX) {
            state.currentZoom += ZOOM_CONFIG.STEP;
            ZoomHandlers.applyZoom();
        }
    },

    zoomOut: () => {
        if (state.currentZoom > ZOOM_CONFIG.MIN) {
            state.currentZoom -= ZOOM_CONFIG.STEP;
            ZoomHandlers.applyZoom();
        }
    },

    applyZoom: () => {
        const elements = getElements();
        if (!elements.imageContainer || !elements.image) return;

        const containerRect = elements.imageContainer.getBoundingClientRect();
        const imageRect = elements.image.getBoundingClientRect();

        const relativeX = containerRect.width / 2;
        const relativeY = containerRect.height / 2;

        elements.image.style.transform = `scale(${state.currentZoom})`;

        requestAnimationFrame(() => {
            const newImageRect = elements.image.getBoundingClientRect();
            const scrollX =
                (newImageRect.width - imageRect.width) *
                (relativeX / containerRect.width);
            const scrollY =
                (newImageRect.height - imageRect.height) *
                (relativeY / containerRect.height);

            elements.imageContainer.scrollLeft += scrollX;
            elements.imageContainer.scrollTop += scrollY;
        });
    },
};

// Drag Handlers
const DragHandlers = {
    setupDragHandlers: () => {
        const elements = getElements();
        if (!elements.imageContainer) return;

        elements.imageContainer.addEventListener(
            "mousedown",
            DragHandlers.handleDragStart,
        );
        elements.imageContainer.addEventListener(
            "mouseleave",
            DragHandlers.handleDragEnd,
        );
        elements.imageContainer.addEventListener(
            "mouseup",
            DragHandlers.handleDragEnd,
        );
        elements.imageContainer.addEventListener(
            "mousemove",
            DragHandlers.handleDragMove,
        );
    },

    handleDragStart: (e) => {
        const elements = getElements();
        state.isDragging = true;
        elements.imageContainer.style.cursor = "grabbing";
        state.dragStart = {
            x: e.pageX - elements.imageContainer.offsetLeft,
            y: e.pageY - elements.imageContainer.offsetTop,
            scrollLeft: elements.imageContainer.scrollLeft,
            scrollTop: elements.imageContainer.scrollTop,
        };
    },

    handleDragEnd: () => {
        const elements = getElements();
        state.isDragging = false;
        elements.imageContainer.style.cursor = "default";
    },

    handleDragMove: (e) => {
        const elements = getElements();
        if (!state.isDragging) return;

        e.preventDefault();
        const x = e.pageX - elements.imageContainer.offsetLeft;
        const y = e.pageY - elements.imageContainer.offsetTop;
        const walkX = x - state.dragStart.x;
        const walkY = y - state.dragStart.y;

        elements.imageContainer.scrollLeft = state.dragStart.scrollLeft - walkX;
        elements.imageContainer.scrollTop = state.dragStart.scrollTop - walkY;
    },
};

// API Handlers
const API = {
    selectCamera: async (cameraId) => {
        state.currentCameraId = cameraId;
        try {
            const snapshotUrl = utils.getSnapshotUrl(cameraId);
            const response = await fetch(snapshotUrl);
            state.currentFrameBlob = await response.blob();
            utils.updateImageSource(
                URL.createObjectURL(state.currentFrameBlob),
            );
            state.canLabel = true;
            UI.clearKeypointsTable();
            ModalHandlers.closeCameraModal();
            utils.showNotification(`Camera ${cameraId} selected`, "success");
        } catch (error) {
            console.error("Error selecting camera:", error);
            alert("Failed to switch camera");
        }
    },

    initializeFirstFrame: async () => {
        try {
            const snapshotUrl = utils.getSnapshotUrl(state.currentCameraId);
            const response = await fetch(snapshotUrl);
            state.currentFrameBlob = await response.blob();
            utils.updateImageSource(
                URL.createObjectURL(state.currentFrameBlob),
            );
        } catch (error) {
            console.error("Error initializing first frame:", error);
            alert("Failed to load initial frame");
        }
    },

    handleNext: async () => {
        const elements = getElements();
        if (!elements.image) return;

        try {
            const snapshotUrl = utils.getSnapshotUrl(
                state.currentCameraId,
                true,
            );
            const response = await fetch(snapshotUrl);
            state.currentFrameBlob = await response.blob();

            utils.updateImageSource(
                URL.createObjectURL(state.currentFrameBlob),
            );
            state.canLabel = true;
            UI.clearKeypointsTable();
        } catch (error) {
            console.error("Error fetching new frame:", error);
            alert("Failed to fetch new frame");
        }
    },

    detectPose: async () => {
        if (!state.canLabel) {
            alert("Please click Next to process a new image");
            return;
        }

        if (!state.currentFrameBlob) {
            alert("No frame available for processing");
            return;
        }

        try {
            const formData = new FormData();
            formData.append("image", state.currentFrameBlob, "frame.jpg");

            const detectResponse = await fetch(
                `http://${window.location.hostname}:15440/detect_pose`,
                {
                    method: "POST",
                    body: formData,
                },
            );

            const data = await detectResponse.json();

            if (detectResponse.ok) {
                utils.updateImageSource(data.image);
                state.canLabel = false;
                UI.updateKeypointsTable(data.keypoints);
                state.keypointsData = data.keypoints;
            } else {
                alert("Error: " + data.error);
            }
        } catch (error) {
            console.error("Error:", error);
            alert("Failed to detect pose");
        }
    },

    handleSave: async () => {
        if (!state.keypointsData || !state.currentLabel) {
            alert("Please ensure pose is detected, and label is entered");
            return;
        }

        if (state.canLabel) {
            alert("Please detect pose (Label) before saving");
            return;
        }

        try {
            utils.validateKeypoints(state.keypointsData);
            const formattedKeypoints = utils.formatKeypointsForDataset(
                state.keypointsData,
            );
            const timestamp = new Date().toISOString().replace(/[:.]/g, "-");

            const saveData = {
                keypoints: formattedKeypoints,
                label: state.currentLabel,
                labelIndex: state.labelMappings[state.currentLabel],
                dataset: document.getElementById("datasetType").value,
                timestamp: timestamp,
                base_dir: state.currentSaveDir,
                labelMappings: state.labelMappings,
            };

            const response = await fetch(
                `http://${window.location.hostname}:15440/save-labeled-data`,
                {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                    },
                    body: JSON.stringify(saveData),
                },
            );

            if (!response.ok) throw new Error("Failed to save data");

            const result = await response.json();

            if (result.frames_remaining > 0) {
                utils.showNotification(
                    `Frame saved - ${result.frames_remaining} more frames needed for sequence`,
                    "info",
                );
            } else {
                utils.showNotification(
                    `Sequence completed and saved to ${state.currentSaveDir}`,
                    "success",
                );
            }

            state.canLabel = true;
            API.handleNext();
        } catch (error) {
            console.error("Error saving:", error);
            alert(`Failed to save data: ${error.message}`);
        }
    },
};

// Directory Handlers
const DirectoryHandlers = {
    loadDirectories: async (path) => {
        try {
            const response = await fetch(
                `http://${window.location.hostname}:15440/list-directory?directory=${path}`,
            );
            const data = await response.json();

            document.getElementById("currentPath").textContent = data.directory;

            const directoryList = document.getElementById("directoryList");
            directoryList.innerHTML = "";

            if (data.directory !== ".") {
                const parentDir = document.createElement("div");
                parentDir.className =
                    "flex items-center gap-2 p-2 hover:bg-gray-300 hover:text-black cursor-pointer";
                parentDir.innerHTML = `
            <i class="fa-solid fa-arrow-up"></i>
            <span>../</span>
        `;
                parentDir.onclick = () =>
                    DirectoryHandlers.loadDirectories(
                        path.split("/").slice(0, -1).join("/") || ".",
                    );
                directoryList.appendChild(parentDir);
            }

            data.items
                .filter((item) => item.is_dir)
                .forEach((item) => {
                    const dirElement = document.createElement("div");
                    dirElement.className =
                        "flex items-center gap-2 p-2 hover:bg-gray-300 hover:text-black cursor-pointer";
                    dirElement.innerHTML = `
                <i class="fa-solid fa-folder"></i>
                <span>${item.name}</span>
            `;
                    dirElement.onclick = () =>
                        DirectoryHandlers.loadDirectories(
                            `${data.directory}/${item.name}`,
                        );
                    directoryList.appendChild(dirElement);
                });
        } catch (error) {
            console.error("Error loading directories:", error);
            alert("Failed to load directories");
        }
    },

    createNewDirectory: async () => {
        const input = document.getElementById("newDirName");
        const name = input.value.trim();

        if (!name) {
            alert("Please enter a directory name");
            return;
        }

        try {
            const currentPath =
                document.getElementById("currentPath").textContent;
            const response = await fetch(
                `http://${window.location.hostname}:15440/create-directory`,
                {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                    },
                    body: JSON.stringify({
                        parent_directory: currentPath,
                        new_directory: name,
                    }),
                },
            );

            if (!response.ok) {
                throw new Error("Failed to create directory");
            }

            input.value = "";
            await DirectoryHandlers.loadDirectories(currentPath);
        } catch (error) {
            console.error("Error creating directory:", error);
            alert("Failed to create directory");
        }
    },

    selectDirectory: async () => {
        const currentPath = document.getElementById("currentPath").textContent;
        try {
            const response = await fetch(
                `http://${window.location.hostname}:15440/select-directory`,
                {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                    },
                    body: JSON.stringify({
                        directory: currentPath,
                    }),
                },
            );

            if (!response.ok) {
                throw new Error("Failed to select directory");
            }

            // Load existing label mappings if they exist
            const mappingsResponse = await fetch(
                `http://${window.location.hostname}:15440/load-label-mappings?directory=${encodeURIComponent(currentPath)}`,
            );

            if (mappingsResponse.ok) {
                const mappingsData = await mappingsResponse.json();

                if (mappingsData.exists) {
                    state.labelMappings = mappingsData.mappings;
                    state.nextLabelIndex = mappingsData.nextIndex;
                    UI.updateLabelMappingsDisplay();
                }
            }

            state.currentSaveDir = currentPath;
            ModalHandlers.closeDirectoryModal();
            utils.showNotification(`Save Directory: ${currentPath}`, "success");
        } catch (error) {
            console.error("Error selecting directory:", error);
            alert("Failed to select directory");
        }
    },

    initializeDefaultDirectory: async () => {
        try {
            const response = await fetch(
                `http://${window.location.hostname}:15440/list-directory?directory=.`,
            );
            const data = await response.json();

            const dataDirectoryExists = data.items.some(
                (item) => item.is_dir && item.name === "data",
            );

            if (!dataDirectoryExists) {
                await fetch(
                    `http://${window.location.hostname}:15440/create-directory`,
                    {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                        },
                        body: JSON.stringify({
                            parent_directory: ".",
                            new_directory: "data",
                        }),
                    },
                );
            }

            const selectResponse = await fetch(
                `http://${window.location.hostname}:15440/select-directory`,
                {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                    },
                    body: JSON.stringify({
                        directory: "data",
                    }),
                },
            );

            if (!selectResponse.ok) {
                throw new Error("Failed to select data directory");
            }

            const mappingsResponse = await fetch(
                `http://${window.location.hostname}:15440/load-label-mappings?directory=${encodeURIComponent("data")}`,
            );

            if (mappingsResponse.ok) {
                const mappingsData = await mappingsResponse.json();
                if (mappingsData.exists) {
                    state.labelMappings = mappingsData.mappings;
                    state.nextLabelIndex = mappingsData.nextIndex;
                    UI.updateLabelMappingsDisplay();
                }
            }

            state.currentSaveDir = "data";
            utils.showNotification("Save Directory: data", "success");
        } catch (error) {
            console.error("Error initializing default directory:", error);
            utils.showNotification(
                "Failed to initialize default directory",
                "error",
            );
        }
    },
};

// Modal Handlers
const ModalHandlers = {
    openCameraModal: () => {
        const modal = document.getElementById("cameraModal");
        if (modal) modal.classList.remove("hidden");
    },

    closeCameraModal: () => {
        const modal = document.getElementById("cameraModal");
        if (modal) modal.classList.add("hidden");
    },

    openDirectoryModal: async () => {
        const modal = document.getElementById("directoryModal");
        modal.classList.remove("hidden");
        await DirectoryHandlers.loadDirectories(".");
    },

    closeDirectoryModal: () => {
        const modal = document.getElementById("directoryModal");
        modal.classList.add("hidden");
    },
};

// Label Handlers
const LabelHandlers = {
    updateLabel: () => {
        const label = document.getElementById("actionLabel").value.trim();
        if (!label) {
            alert("Please enter a label");
            return;
        }

        if (!(label in state.labelMappings)) {
            state.labelMappings[label] = state.nextLabelIndex++;
            UI.updateLabelMappingsDisplay();
        }

        state.currentLabel = label;
        state.currentDataset = document.getElementById("datasetType").value;

        document.getElementById("labelIndex").textContent =
            `Label Index: ${state.labelMappings[label]}`;
    },
};

// Event Listeners
const setupEventListeners = () => {
    document.addEventListener("DOMContentLoaded", async function () {
        // Initialize UI elements
        const elements = getElements();
        if (elements.imageContainer) {
            elements.imageContainer.style.overflow = "auto";
            elements.imageContainer.style.position = "relative";
        }

        if (elements.image) {
            elements.image.style.transformOrigin = "center center";
            elements.image.style.transition = "transform 0.2s ease-out";
            elements.image.addEventListener("dragstart", (e) =>
                e.preventDefault(),
            );
        }

        // Setup drag handlers
        DragHandlers.setupDragHandlers();

        // Setup keyboard shortcuts
        document.addEventListener("keydown", function (event) {
            if (
                event.target.tagName === "INPUT" ||
                event.target.tagName === "TEXTAREA"
            ) {
                return;
            }

            const key = event.key.toLowerCase();
            if (SHORTCUTS.hasOwnProperty(key)) {
                event.preventDefault();
                SHORTCUTS[key]();
            }
        });

        // Setup toolbar highlighting
        document.addEventListener("keydown", function (event) {
            const key = event.key.toLowerCase();
            if (SHORTCUTS.hasOwnProperty(key)) {
                const action = Object.entries(TOOLBAR_ITEMS).find(
                    ([_, shortcut]) => shortcut === key,
                )?.[0];
                if (action) {
                    const paragraphs = document.querySelectorAll("[onclick] p");
                    const element = Array.from(paragraphs)
                        .find((p) => p.textContent.includes(action))
                        ?.closest("div");

                    if (element) {
                        element.classList.add("bg-gray-300");
                        setTimeout(() => {
                            element.classList.remove("bg-gray-300");
                        }, 200);
                    }
                }
            }
        });

        // Initialize default directory
        await DirectoryHandlers.initializeDefaultDirectory();

        // Initialize first frame
        await API.initializeFirstFrame();
    });
};

// Export global functions for HTML onclick handlers
window.openCameraModal = ModalHandlers.openCameraModal;
window.closeCameraModal = ModalHandlers.closeCameraModal;
window.openDirectoryModal = ModalHandlers.openDirectoryModal;
window.closeDirectoryModal = ModalHandlers.closeDirectoryModal;
window.selectCamera = API.selectCamera;
window.zoomIn = ZoomHandlers.zoomIn;
window.zoomOut = ZoomHandlers.zoomOut;
window.handleNext = API.handleNext;
window.detectPose = API.detectPose;
window.handleSave = API.handleSave;
window.updateLabel = LabelHandlers.updateLabel;
window.createNewDirectory = DirectoryHandlers.createNewDirectory;
window.selectDirectory = DirectoryHandlers.selectDirectory;

// Initialize the application
setupEventListeners();
