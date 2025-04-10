let player = null;
let isRecording = false;
let directoryHandle = null;
let timerInterval = null;
let recordingStartTime = 0;
let labels = [];
let currentPath = "";
let videoApiUrl = `http://${window.location.hostname}:8888/api`;

document.addEventListener("DOMContentLoaded", function () {
    loadLabels();
    loadDirectoryContents();
});

function navigateDirectory(path) {
    currentPath = path;
    loadDirectoryContents();
}

function loadDirectoryContents() {
    fetch(
        `${videoApiUrl}/get-directory-contents?path=${encodeURIComponent(currentPath)}`,
    )
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                updateDirectoryBreadcrumb(currentPath);
                updateDirectoryListing(data.contents);
                updateVideoTable(data.contents.videos);
            } else {
                Swal.fire({
                    icon: "error",
                    title: "Error",
                    text: data.message,
                });
            }
        })
        .catch((error) => {
            console.error("Error loading directory contents:", error);
            Swal.fire({
                icon: "error",
                title: "Error",
                text: "Failed to load directory contents",
            });
        });
}

function updateDirectoryBreadcrumb(path) {
    const breadcrumbContainer = document.getElementById("directory_breadcrumb");
    breadcrumbContainer.innerHTML = "";
    const rootLink = document.createElement("li");
    rootLink.className = "breadcrumb-item";
    const rootAnchor = document.createElement("a");
    rootAnchor.href = "#";
    rootAnchor.textContent = "Root";
    rootAnchor.onclick = () => {
        navigateDirectory("");
        return false;
    };
    rootLink.appendChild(rootAnchor);
    breadcrumbContainer.appendChild(rootLink);
    if (path) {
        const segments = path.split("/");
        let currentSegmentPath = "";

        segments.forEach((segment, index) => {
            currentSegmentPath += (index > 0 ? "/" : "") + segment;
            const item = document.createElement("li");
            if (index === segments.length - 1) {
                item.className = "breadcrumb-item active";
                item.textContent = segment;
            } else {
                item.className = "breadcrumb-item";
                const anchor = document.createElement("a");
                anchor.href = "#";
                anchor.textContent = segment;
                const path = currentSegmentPath;
                anchor.onclick = () => {
                    navigateDirectory(path);
                    return false;
                };
                item.appendChild(anchor);
            }
            breadcrumbContainer.appendChild(item);
        });
    }
}

function updateDirectoryListing(contents) {
    const directoryList = document.getElementById("directory_list");
    directoryList.innerHTML = "";
    if (contents.directories.length === 0) {
        const emptyItem = document.createElement("li");
        emptyItem.className = "list-group-item text-muted";
        emptyItem.textContent = "No subdirectories";
        directoryList.appendChild(emptyItem);
    } else {
        contents.directories.forEach((dir) => {
            const item = document.createElement("li");
            item.className =
                "list-group-item directory-item d-flex justify-content-between align-items-center";
            const folderIcon = document.createElement("i");
            folderIcon.className = "cil-folder me-2";
            const nameSpan = document.createElement("span");
            nameSpan.className = "directory-name flex-grow-1";
            nameSpan.textContent = dir;
            item.appendChild(folderIcon);
            item.appendChild(nameSpan);
            item.style.cursor = "pointer";
            item.onclick = () => {
                const newPath = currentPath ? `${currentPath}/${dir}` : dir;
                navigateDirectory(newPath);
            };
            directoryList.appendChild(item);
        });
    }
}

function updateVideoTable(videos) {
    const tableBody = document.getElementById("video_table");
    tableBody.innerHTML = "";
    if (videos.length === 0) {
        const row = document.createElement("tr");
        row.innerHTML = `<td colspan="3" class="text-center">No videos found</td>`;
        tableBody.appendChild(row);
        return;
    }
    videos.forEach((filename) => {
        const parts = filename.match(
            /S(\d{3})C(\d{3})P(\d{3})R(\d{3})A(\d{3})\.mp4/i,
        );

        if (parts) {
            const [_, subjectId, cameraId, personId, repeatId, actionId] =
                parts;
            let labelName = "Unknown";
            if (actionId && labels[parseInt(actionId) - 1]) {
                labelName = labels[parseInt(actionId) - 1].name;
            }
            const row = document.createElement("tr");
            row.innerHTML = `
                <td>${filename}</td>
                <td>${parseInt(actionId)} - ${labelName}</td>
                <td>
                    <button class="btn btn-sm btn-primary me-1" onclick="downloadVideo('${filename}')">
                        <i class="cil-cloud-download"></i>
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="deleteVideo('${filename}')">
                        <i class="cil-trash"></i>
                    </button>
                </td>
            `;
            tableBody.appendChild(row);
        }
    });
}

function downloadFile(url, filename) {
    const a = document.createElement("a");
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
}

function connectStream() {
    const rtspUrl = document.getElementById("rtsp_url").value;
    if (!rtspUrl) {
        Swal.fire({
            icon: "error",
            title: "Error",
            text: "Please enter RTSP URL first",
        });
        return;
    }
    if (player) {
        player.destroy();
    }

    const wsUrl = `ws://${window.location.hostname}:9999/stream?url=${encodeURIComponent(rtspUrl)}`;
    const canvas = document.getElementById("videoCanvas");

    try {
        player = new JSMpeg.Player(wsUrl, {
            canvas: canvas,
            autoplay: true,
            audio: false,
            loop: true,
            onError: function (error) {
                console.error("Stream error:", error);
                Swal.fire({
                    icon: "error",
                    title: "Stream Error",
                    text: "Error connecting to stream. Please check URL and try again.",
                });
            },
        });
    } catch (error) {
        console.error("Error initializing player:", error);
        Swal.fire({
            icon: "error",
            title: "Player Error",
            text: "Error initializing player. Please try again.",
        });
    }
}

function updateTimer() {
    const currentTime = new Date().getTime();
    const elapsedTime = currentTime - recordingStartTime;
    const hours = Math.floor(elapsedTime / (1000 * 60 * 60));
    const minutes = Math.floor((elapsedTime % (1000 * 60 * 60)) / (1000 * 60));
    const seconds = Math.floor((elapsedTime % (1000 * 60)) / 1000);
    const formattedTime =
        (hours < 10 ? "0" + hours : hours) +
        ":" +
        (minutes < 10 ? "0" + minutes : minutes) +
        ":" +
        (seconds < 10 ? "0" + seconds : seconds);

    document.getElementById("recordBtnText").textContent = formattedTime;
}

function startRecording() {
    const rtspUrl = document.getElementById("rtsp_url").value;
    if (!rtspUrl) {
        Swal.fire({
            icon: "error",
            title: "Error",
            text: "Please enter RTSP URL first",
        });
        return;
    }

    $.ajax({
        url: "http://localhost:8080/tools/rtsp/start-record",
        method: "POST",
        headers: {
            "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
        },
        data: {
            rtsp_url: rtspUrl,
        },
        success: function (response) {
            if (response.success) {
                isRecording = true;
                document.getElementById("recordBtn").disabled = true;
                document.getElementById("stopBtn").disabled = false;
                recordingStartTime = new Date().getTime();
                document.getElementById("recordBtnText").textContent =
                    "00:00:00";
                timerInterval = setInterval(updateTimer, 1000);

                Swal.fire({
                    icon: "success",
                    title: "Success",
                    text: "Being recorded!",
                });
            } else {
                Swal.fire({
                    icon: "error",
                    title: "Error",
                    text: response.message,
                });
            }
        },
        error: function (xhr) {
            const response = xhr.responseJSON;
            console.error(
                "Error starting recording:",
                response?.message || "Unknown error",
            );
            Swal.fire({
                icon: "error",
                title: "Error",
                text:
                    response?.message ||
                    "Error starting recording. Please try again.",
            });
        },
    });
}

async function stopRecording() {
    try {
        clearInterval(timerInterval);
        document.getElementById("recordBtnText").textContent =
            "Start Recording";

        const response = await $.ajax({
            url: "http://localhost:8080/tools/rtsp/stop-record",
            method: "POST",
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
            },
        });

        if (response.success) {
            isRecording = false;
            document.getElementById("recordBtn").disabled = false;
            document.getElementById("stopBtn").disabled = true;
            const fileResponse = await fetch(response.output_url);
            const fileBlob = await fileResponse.blob();
            const result = await Swal.fire({
                title: "Save Video?",
                text: "Do you want to save this recording?",
                icon: "question",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Yes, save it!",
                cancelButtonText: "No, discard it",
            });

            if (result.isConfirmed) {
                const subject_id = parseInt(
                    document.getElementById("subject_id").value,
                );
                const camera_id = parseInt(
                    document.getElementById("camera_id").value,
                );
                const person_id = parseInt(
                    document.getElementById("person_id").value,
                );
                const repeat_id = parseInt(
                    document.getElementById("repeat_id").value,
                );
                const action_id = parseInt(
                    document.getElementById("action_id").value,
                );
                const formData = new FormData();
                formData.append(
                    "video",
                    new File([fileBlob], "recorded.mp4", { type: "video/mp4" }),
                );
                formData.append("subject_id", subject_id);
                formData.append("camera_id", camera_id);
                formData.append("person_id", person_id);
                formData.append("repeat_id", repeat_id);
                formData.append("action_id", action_id);
                formData.append("directory_path", currentPath);

                fetch(`${videoApiUrl}/save-video`, {
                    method: "POST",
                    body: formData,
                })
                    .then((response) => response.json())
                    .then((data) => {
                        if (data.success) {
                            Swal.fire({
                                icon: "success",
                                title: "Success",
                                text: `Video saved as ${data.filename}`,
                            });
                            document.getElementById("repeat_id").value =
                                repeat_id + 1;
                            updateNamingPreview();
                            loadDirectoryContents();
                        } else {
                            Swal.fire({
                                icon: "error",
                                title: "Error",
                                text: data.message,
                            });
                        }
                    })
                    .catch((error) => {
                        console.error("Error saving video:", error);
                        Swal.fire({
                            icon: "error",
                            title: "Error",
                            text: "Error saving video to PoseC3D container",
                        });
                        downloadFile(response.output_url, response.filename);
                    });
            } else {
                Swal.fire({
                    icon: "info",
                    title: "Discarded",
                    text: "Recording has been discarded",
                    timer: 1500,
                });
            }
        } else {
            Swal.fire({
                icon: "error",
                title: "Error",
                text: "Error stopping recording. Please try again.",
            });
        }
    } catch (error) {
        console.error("Error stopping recording:", error);
        Swal.fire({
            icon: "error",
            title: "Error",
            text: "Error stopping recording. Please try again.",
        });
    }
}

function loadDirectories() {
    fetch(`${videoApiUrl}/get-directories`)
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                const select = document.getElementById("directory_select");
                const currentValue = select.value;
                while (select.options.length > 1) {
                    select.remove(1);
                }
                data.directories.forEach((dir) => {
                    const option = document.createElement("option");
                    option.value = dir;
                    option.textContent = dir;
                    select.appendChild(option);
                });
                if (currentValue) {
                    select.value = currentValue;
                }
            }
        })
        .catch((error) => {
            console.error("Error loading directories:", error);
        });
}

function createDirectory() {
    const directoryName = document.getElementById("directory_name").value;
    if (!directoryName) {
        Swal.fire({
            icon: "error",
            title: "Error",
            text: "Please enter directory name",
        });
        return;
    }

    const newDirectoryPath = currentPath
        ? `${currentPath}/${directoryName}`
        : directoryName;

    fetch(`${videoApiUrl}/create-directory`, {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
        },
        body: JSON.stringify({
            directory_name: directoryName,
            parent_path: currentPath,
        }),
    })
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                Swal.fire({
                    icon: "success",
                    title: "Success",
                    text: data.message,
                });
                document.getElementById("directory_name").value = "";
                loadDirectoryContents();
            } else {
                Swal.fire({
                    icon: "error",
                    title: "Error",
                    text: data.message,
                });
            }
        })
        .catch((error) => {
            console.error("Error creating directory:", error);
            Swal.fire({
                icon: "error",
                title: "Error",
                text: "Error communicating with API",
            });
        });
}

function loadLabels() {
    fetch(`${videoApiUrl}/get-labels`)
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                labels = data.labels;
                const tableBody = document.getElementById("label_table");
                tableBody.innerHTML = "";
                const actionSelect = document.getElementById("action_id");
                actionSelect.innerHTML = "";
                labels.forEach((label, index) => {
                    const row = document.createElement("tr");
                    row.innerHTML = `
                                    <td>${label.name}</td>
                                    <td><span class="badge ${label.status === "OK" ? "bg-success" : "bg-secondary"}">${label.status}</span></td>
                                    <td>${index + 1}</td>
                                    <td>
                                        <button class="btn btn-sm btn-danger" onclick="removeLabel(${index})">
                                            <i class="cil-trash"></i>
                                        </button>
                                    </td>
                                `;
                    tableBody.appendChild(row);
                    const option = document.createElement("option");
                    option.value = index + 1;
                    option.textContent = `${index + 1} - ${label.name}`;
                    actionSelect.appendChild(option);
                });

                updateNamingPreview();
            }
        })
        .catch((error) => {
            console.error("Error loading labels:", error);
        });
}

function addLabel() {
    const labelName = document.getElementById("label_name").value;
    const labelStatus = document.getElementById("label_status").value;

    if (!labelName) {
        Swal.fire({
            icon: "error",
            title: "Error",
            text: "Please enter label name",
        });
        return;
    }

    labels.push({
        name: labelName,
        status: labelStatus,
    });

    fetch(`${videoApiUrl}/save-labels`, {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
        },
        body: JSON.stringify({
            labels: labels,
        }),
    })
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                document.getElementById("label_name").value = "";
                loadLabels();
            } else {
                Swal.fire({
                    icon: "error",
                    title: "Error",
                    text: data.message,
                });
            }
        })
        .catch((error) => {
            console.error("Error saving labels:", error);
            Swal.fire({
                icon: "error",
                title: "Error",
                text: "Error communicating with API",
            });
        });
}

function removeLabel(index) {
    Swal.fire({
        title: "Are you sure?",
        text: "This will remove the label from the list",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        cancelButtonColor: "#3085d6",
        confirmButtonText: "Yes, remove it!",
    }).then((result) => {
        if (result.isConfirmed) {
            labels.splice(index, 1);

            fetch(`${videoApiUrl}/save-labels`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({
                    labels: labels,
                }),
            })
                .then((response) => response.json())
                .then((data) => {
                    if (data.success) {
                        loadLabels();
                    } else {
                        Swal.fire({
                            icon: "error",
                            title: "Error",
                            text: data.message,
                        });
                    }
                })
                .catch((error) => {
                    console.error("Error saving labels:", error);
                });
        }
    });
}

function updateNamingPreview() {
    const subject_id =
        parseInt(document.getElementById("subject_id").value) || 1;
    const camera_id = parseInt(document.getElementById("camera_id").value) || 1;
    const person_id = parseInt(document.getElementById("person_id").value) || 1;
    const repeat_id = parseInt(document.getElementById("repeat_id").value) || 1;
    const action_id = parseInt(document.getElementById("action_id").value) || 1;

    document.getElementById("subject_id_display").textContent = subject_id
        .toString()
        .padStart(3, "0");
    document.getElementById("camera_id_display").textContent = camera_id
        .toString()
        .padStart(3, "0");
    document.getElementById("person_id_display").textContent = person_id
        .toString()
        .padStart(3, "0");
    document.getElementById("repeat_id_display").textContent = repeat_id
        .toString()
        .padStart(3, "0");
    document.getElementById("action_id_display").textContent = action_id
        .toString()
        .padStart(3, "0");
}

function suggestNextIds() {
    const action_id = parseInt(document.getElementById("action_id").value) || 1;

    fetch(
        `${videoApiUrl}/get-next-ids?directory_path=${encodeURIComponent(currentPath)}&action_id=${action_id}`,
    )
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                document.getElementById("subject_id").value =
                    data.suggestion.subject_id;
                document.getElementById("camera_id").value =
                    data.suggestion.camera_id;
                document.getElementById("person_id").value =
                    data.suggestion.person_id;
                document.getElementById("repeat_id").value =
                    data.suggestion.repeat_id;
                updateNamingPreview();
            }
        })
        .catch((error) => {
            console.error("Error getting next IDs:", error);
        });
}

function loadVideos() {
    const directory = document.getElementById("directory_select").value;

    fetch(
        `${videoApiUrl}/get-videos?directory=${encodeURIComponent(directory)}`,
    )
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                const tableBody = document.getElementById("video_table");
                tableBody.innerHTML = "";

                if (data.videos.length === 0) {
                    const row = document.createElement("tr");
                    row.innerHTML = `<td colspan="7" class="text-center">No videos found</td>`;
                    tableBody.appendChild(row);
                    return;
                }

                data.videos.forEach((filename) => {
                    const parts = filename.match(
                        /S(\d{3})C(\d{3})P(\d{3})R(\d{3})A(\d{3})\.mp4/i,
                    );

                    if (parts) {
                        const [
                            _,
                            subjectId,
                            cameraId,
                            personId,
                            repeatId,
                            actionId,
                        ] = parts;

                        let labelName = "Unknown";
                        if (actionId && labels[parseInt(actionId) - 1]) {
                            labelName = labels[parseInt(actionId) - 1].name;
                        }

                        const row = document.createElement("tr");
                        row.innerHTML = `
                                        <td>${filename}</td>
                                        <td>${parseInt(actionId)} - ${labelName}</td>
                                        <td>
                                            <button class="btn btn-sm btn-primary me-1" onclick="downloadVideo('${filename}')">
                                                <i class="cil-cloud-download"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger" onclick="deleteVideo('${filename}')">
                                                <i class="cil-trash"></i>
                                            </button>
                                        </td>
                                    `;
                        tableBody.appendChild(row);
                    }
                });
            }
        })
        .catch((error) => {
            console.error("Error loading videos:", error);
        });
}

function downloadVideo(filename) {
    const downloadUrl = `${videoApiUrl}/download-video/${filename}?directory_path=${encodeURIComponent(currentPath)}`;
    window.open(downloadUrl, "_blank");
}

function deleteVideo(filename) {
    Swal.fire({
        title: "Are you sure?",
        text: `This will permanently remove the video`,
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        cancelButtonColor: "#3085d6",
        confirmButtonText: "Yes, delete!",
        cancelButtonText: "Cancel",
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(`${videoApiUrl}/delete-video`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({
                    filename: filename,
                    directory_path: currentPath,
                }),
            })
                .then((response) => response.json())
                .then((data) => {
                    if (data.success) {
                        Swal.fire({
                            icon: "success",
                            title: "Deleted!",
                            text: data.message,
                            timer: 1500,
                        });
                        loadDirectoryContents();
                    } else {
                        Swal.fire({
                            icon: "error",
                            title: "Error",
                            text: data.message,
                        });
                    }
                })
                .catch((error) => {
                    console.error("Error deleting video:", error);
                    Swal.fire({
                        icon: "error",
                        title: "Error",
                        text: "Unable to connect to API",
                    });
                });
        }
    });
}
