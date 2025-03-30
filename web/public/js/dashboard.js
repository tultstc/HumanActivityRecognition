const selectCameras = document.getElementById("selectCameras");
const cameraListPopup = document.getElementById("cameraListPopup");
const closeCameraList = document.getElementById("closeCameraList");
const applySelection = document.getElementById("applySelection");
const cameraFrame = document.getElementById("cameraFrame");
const gridRows = document.getElementById("gridRows");
const gridColumns = document.getElementById("gridColumns");
const gridWarning = document.getElementById("gridWarning");
const selectedCameras = new Set();
const initialPreferences = window.userPreferences || {
    grid_rows: 3,
    grid_columns: 4,
    selected_camera_ids: [],
};
const images = document.querySelectorAll('.dynamic-image');
images.forEach(img => {
    const url = img.dataset.url;
    img.src = `http://${window.location.hostname}:15440/image/${url}`;
});

function savePreferencesToServer() {
    const checkboxes = document.querySelectorAll(".camera-checkbox:checked");
    const selectedCameraIds = Array.from(checkboxes).map(
        (checkbox) => checkbox.dataset.cameraId,
    );

    fetch("/dashboard/save-preferences", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')
                .content,
        },
        body: JSON.stringify({
            grid_rows: gridRows.value,
            grid_columns: gridColumns.value,
            selected_camera_ids: selectedCameraIds,
        }),
    });
}

function applyPreferences() {
    gridRows.value = initialPreferences.grid_rows || 3;
    gridColumns.value = initialPreferences.grid_columns || 4;

    document.querySelectorAll(".camera-checkbox").forEach((checkbox) => {
        checkbox.checked = initialPreferences.selected_camera_ids.includes(
            checkbox.dataset.cameraId,
        );
    });

    const checkboxes = document.querySelectorAll(".camera-checkbox:checked");
    selectedCameras.clear();

    checkboxes.forEach((checkbox) => {
        selectedCameras.add(checkbox.dataset.cameraId);
    });

    if (selectedCameras.size > 0) {
        const cameraIds = Array.from(selectedCameras).join(",");
        const streamUrl =
            `http://${window.location.hostname}:15440/recognized_stream?camera_ids=${cameraIds}&row=${gridRows.value}&column=${gridColumns.value}`;
        cameraFrame.src = streamUrl;
    }

    validateGridSize();
}

function validateGridSize() {
    const totalGridSize = gridRows.value * gridColumns.value;
    const selectedCount = document.querySelectorAll(
        ".camera-checkbox:checked",
    ).length;
    const isValid = totalGridSize >= selectedCount;

    gridWarning.classList.toggle("hidden", isValid);
    applySelection.disabled = !isValid;
    return isValid;
}

[gridRows, gridColumns].forEach((input) => {
    input.addEventListener("input", validateGridSize);
});

document.querySelectorAll(".camera-checkbox").forEach((checkbox) => {
    checkbox.addEventListener("change", validateGridSize);
});

selectCameras.addEventListener("click", function() {
    cameraListPopup.classList.remove("hidden");
    cameraListPopup.classList.add("flex");
});

closeCameraList.addEventListener("click", function() {
    cameraListPopup.classList.remove("flex");
    cameraListPopup.classList.add("hidden");
});

applySelection.addEventListener("click", function() {
    if (!validateGridSize()) return;

    const checkboxes = document.querySelectorAll(".camera-checkbox:checked");
    selectedCameras.clear();

    checkboxes.forEach((checkbox) => {
        selectedCameras.add(checkbox.dataset.cameraId);
    });

    if (selectedCameras.size > 0) {
        const cameraIds = Array.from(selectedCameras).join(",");
        const streamUrl =
            `http://${window.location.hostname}:15440/recognized_stream?camera_ids=${cameraIds}&row=${gridRows.value}&column=${gridColumns.value}`;
        cameraFrame.src = streamUrl;
    } else {
        cameraFrame.src = "/images/blank.png";
    }

    savePreferencesToServer();

    cameraListPopup.classList.remove("flex");
    cameraListPopup.classList.add("hidden");
});

selectAllCameras.addEventListener("click", function() {
    document.querySelectorAll(".camera-checkbox").forEach(checkbox => {
        checkbox.checked = true;
    });
    validateGridSize();
});

deselectAllCameras.addEventListener("click", function() {
    document.querySelectorAll(".camera-checkbox").forEach(checkbox => {
        checkbox.checked = false;
    });
    validateGridSize();
});

applyPreferences();
