const selectCameras = document.getElementById("selectCameras");
const cameraListPopup = document.getElementById("cameraListPopup");
const closeCameraList = document.getElementById("closeCameraList");
const applySelection = document.getElementById("applySelection");
const cameraFrame = document.getElementById("cameraFrame");
const gridRows = document.getElementById("gridRows");
const gridColumns = document.getElementById("gridColumns");
const gridWarning = document.getElementById("gridWarning");
let selectedGroupName = "Analysis_Autoliv";
const selectedCameras = new Set();
const initialPreferences = window.userPreferences || {
    grid_rows: 3,
    grid_columns: 4,
    selected_camera_ids: [],
};

const images = document.querySelectorAll(".dynamic-image");
images.forEach((img) => {
    const url = img.dataset.url;
    img.src = `http://${window.location.hostname}:15440/image/${url}`;
});

document
    .querySelector("#cameraListPopup select")
    .addEventListener("change", function () {
        const selectedGroupId = this.value;
        selectedGroupName = this.options[this.selectedIndex].text;

        document.querySelectorAll(".camera-item").forEach((item) => {
            const groupIds = JSON.parse(item.dataset.groupId || "[]");
            if (
                selectedGroupId === "" ||
                groupIds.includes(Number(selectedGroupId))
            ) {
                item.style.display = "flex";
            } else {
                item.style.display = "none";
            }
        });

        document.querySelectorAll(".camera-checkbox").forEach((checkbox) => {
            checkbox.checked = false;
        });
        validateGridSize();
    });

function savePreferencesToServer() {
    const checkboxes = document.querySelectorAll(".camera-checkbox:checked");
    const selectedCameraIds = Array.from(checkboxes).map(
        (checkbox) => checkbox.dataset.cameraId,
    );
    const groupSelect = document.querySelector("#cameraListPopup select");
    const selectedGroupId = groupSelect.value;

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
            selected_group_id: selectedGroupId || null,
        }),
    });
}

function applyPreferences() {
    gridRows.value = initialPreferences.grid_rows || 3;
    gridColumns.value = initialPreferences.grid_columns || 4;
    selectedGroupName =
        initialPreferences.selected_group_name || "Analysis_Autoliv";

    const groupSelect = document.querySelector("#cameraListPopup select");
    if (initialPreferences.selected_group_id) {
        groupSelect.value = initialPreferences.selected_group_id;
        selectedGroupName = groupSelect.options[groupSelect.selectedIndex].text;
    }

    document.querySelectorAll(".camera-item").forEach((item) => {
        const groupIds = JSON.parse(item.dataset.groupId || "[]");
        if (
            !initialPreferences.selected_group_id ||
            groupIds.includes(Number(initialPreferences.selected_group_id))
        ) {
            item.style.display = "flex";
        } else {
            item.style.display = "none";
        }
    });

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
        const streamUrl = `http://${window.location.hostname}:15440/recognized_stream?camera_ids=${cameraIds}&camera_group=${encodeURIComponent(selectedGroupName)}&row=${gridRows.value}&column=${gridColumns.value}`;
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

selectCameras.addEventListener("click", function () {
    cameraListPopup.classList.remove("hidden");
    cameraListPopup.classList.add("flex");
});

closeCameraList.addEventListener("click", function () {
    cameraListPopup.classList.remove("flex");
    cameraListPopup.classList.add("hidden");
});

applySelection.addEventListener("click", function () {
    if (!validateGridSize()) return;

    const checkboxes = document.querySelectorAll(".camera-checkbox:checked");
    selectedCameras.clear();

    checkboxes.forEach((checkbox) => {
        selectedCameras.add(checkbox.dataset.cameraId);
    });

    if (selectedCameras.size > 0) {
        const cameraIds = Array.from(selectedCameras).join(",");
        const streamUrl = `http://${window.location.hostname}:15440/recognized_stream?camera_ids=${cameraIds}&camera_group=${encodeURIComponent(selectedGroupName)}&row=${gridRows.value}&column=${gridColumns.value}`;
        cameraFrame.src = streamUrl;
    } else {
        cameraFrame.src = "/images/blank.png";
    }

    savePreferencesToServer();

    cameraListPopup.classList.remove("flex");
    cameraListPopup.classList.add("hidden");
});

selectAllCameras.addEventListener("click", function () {
    document.querySelectorAll(".camera-item").forEach((item) => {
        if (item.style.display === "flex") {
            const checkbox = item.querySelector(".camera-checkbox");
            if (checkbox) {
                checkbox.checked = true;
            }
        }
    });
    validateGridSize();
});

deselectAllCameras.addEventListener("click", function () {
    document.querySelectorAll(".camera-checkbox").forEach((checkbox) => {
        checkbox.checked = false;
    });
    validateGridSize();
});

applyPreferences();
