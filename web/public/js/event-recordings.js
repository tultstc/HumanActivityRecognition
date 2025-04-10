let currentSessionPath = null;
let isPlaying = false;
let currentFrame = 0;
let totalFrames = 0;
let videoStream = document.getElementById("video-stream");
let placeholder = document.getElementById("recording-placeholder");
let playBtn = document.getElementById("play-btn");
let pauseBtn = document.getElementById("pause-btn");
let fpsControl = document.getElementById("fps-control");
let fpsValue = document.getElementById("fps-value");
let frameCounter = document.getElementById("frame-counter");
let playbackInterval = null;
let videoCompleted = false;

fetch("/api/recordings/list")
    .then((response) => {
        return response.json();
    })
    .then((data) => {
        const treeData = convertToJsTreeFormat(data);
        initializeJsTree(treeData);
    })
    .catch((error) => {
        console.error("Error loading recordings:", error);
        document.getElementById("recordings-tree").innerHTML =
            '<div class="p-3 text-center text-danger">Error loading recordings. Please try again.</div>';
    });

fpsControl.addEventListener("input", function () {
    fpsValue.textContent = this.value;

    if (isPlaying) {
        pausePlayback();
        startPlayback();
    }
});

playBtn.addEventListener("click", function () {
    if (!currentSessionPath) return;

    if (videoCompleted) {
        currentFrame = 0;
        videoCompleted = false;
        updateFrameDisplay();
        loadCurrentFrame();
    }

    startPlayback();
});

pauseBtn.addEventListener("click", function () {
    pausePlayback(true);
});

function startPlayback() {
    if (!currentSessionPath) return;

    if (videoCompleted || currentFrame >= totalFrames - 1) {
        currentFrame = 0;
        videoCompleted = false;
        updateFrameDisplay();
        loadCurrentFrame();
    }

    isPlaying = true;
    playBtn.disabled = true;
    pauseBtn.disabled = false;
    playBtn.innerHTML = '<i class="fas fa-play"></i> Play';
    const fps = parseInt(fpsControl.value);
    const frameDelay = 1000 / fps;

    if (playbackInterval) {
        clearInterval(playbackInterval);
    }

    playbackInterval = setInterval(() => {
        if (currentFrame >= totalFrames - 1) {
            pausePlayback();
            videoCompleted = true;
            playBtn.innerHTML = '<i class="fas fa-redo"></i> Replay';
            return;
        }

        currentFrame++;
        updateFrameDisplay();
        loadCurrentFrame();

        const frameSlider = document.getElementById("frame-slider");
        if (frameSlider) {
            frameSlider.value = currentFrame;
        }
    }, frameDelay);
}

function pausePlayback() {
    isPlaying = false;
    playBtn.disabled = false;
    pauseBtn.disabled = true;

    if (playbackInterval) {
        clearInterval(playbackInterval);
        playbackInterval = null;
    }

    loadCurrentFrame();
}

function loadCurrentFrame() {
    const frameUrl = `/api/recordings/frame?path=${currentSessionPath}&frame=${currentFrame}`;
    const img = new Image();
    img.onload = function () {
        if (isPlaying) {
            videoStream.src = frameUrl;
            videoStream.classList.remove("d-none");
            placeholder.classList.add("d-none");
        } else {
            placeholder.src = frameUrl;
            placeholder.classList.remove("d-none");
            videoStream.classList.add("d-none");
        }
    };
    img.onerror = function () {
        console.error("Error loading frame:", currentFrame);
    };
    img.src = frameUrl;
}

function updateFrameDisplay() {
    frameCounter.innerText = `${currentFrame + 1}/${totalFrames}`;
}

function addFrameControls() {
    const controls = document.createElement("div");
    controls.className = "d-flex align-items-center mt-2";
    controls.innerHTML = `
        <button class="btn btn-sm btn-outline-secondary me-2" id="prev-frame">
            <i class="fas fa-step-backward"></i>
        </button>
        <button class="btn btn-sm btn-outline-secondary me-2" id="next-frame">
            <i class="fas fa-step-forward"></i>
        </button>
        <input type="range" class="form-range flex-grow-1 mx-2" id="frame-slider" min="0" value="0">
    `;

    document.querySelector(".card-body").appendChild(controls);

    const prevBtn = document.getElementById("prev-frame");
    const nextBtn = document.getElementById("next-frame");
    const frameSlider = document.getElementById("frame-slider");

    prevBtn.addEventListener("click", () => {
        if (isPlaying) pausePlayback();
        currentFrame = Math.max(0, currentFrame - 1);
        updateFrameDisplay();
        loadCurrentFrame();
        frameSlider.value = currentFrame;
    });

    nextBtn.addEventListener("click", () => {
        if (isPlaying) pausePlayback();
        currentFrame = Math.min(totalFrames - 1, currentFrame + 1);
        updateFrameDisplay();
        loadCurrentFrame();
        frameSlider.value = currentFrame;
    });

    frameSlider.addEventListener("input", () => {
        if (isPlaying) pausePlayback();
        currentFrame = parseInt(frameSlider.value);
        updateFrameDisplay();
        loadCurrentFrame();
    });

    frameSlider.max = totalFrames - 1;
}

function selectRecording(sessionPath, title) {
    if (isPlaying) {
        pausePlayback();
    }

    currentSessionPath = sessionPath;
    currentFrame = 0;
    videoCompleted = false;
    document.getElementById("current-recording-title").innerText = title;

    loadRecordingMetadata(sessionPath);
    loadCurrentFrame();

    playBtn.disabled = false;
    playBtn.innerHTML = '<i class="fas fa-play"></i> Play';
    pauseBtn.disabled = true;

    const frameSlider = document.getElementById("frame-slider");
    if (frameSlider) {
        frameSlider.value = 0;
    }
}

function loadRecordingMetadata(sessionPath) {
    const metadataUrl = `/api/recordings/metadata?path=${sessionPath}`;

    fetch(metadataUrl)
        .then((response) => {
            return response.json();
        })
        .then((data) => {
            let tableHtml = "";

            if (data.error) {
                console.error("Error in metadata:", data.error);
                tableHtml = `<tr><td colspan="2" class="text-danger">${data.error}</td></tr>`;
            } else {
                tableHtml += `<tr><td>Camera</td><td>${data.camera_name} (ID: ${data.camera_id})</td></tr>`;
                tableHtml += `<tr><td>Group</td><td>${data.group_name}</td></tr>`;
                tableHtml += `<tr><td>Start Time</td><td>${data.start_time}</td></tr>`;
                tableHtml += `<tr><td>End Time</td><td>${data.end_time}</td></tr>`;
            }

            totalFrames = data.total_frames || data.frame_count || 0;
            currentFrame = 0;
            updateFrameDisplay();

            const frameSlider = document.getElementById("frame-slider");
            if (frameSlider) {
                frameSlider.max = totalFrames - 1;
                frameSlider.value = 0;
            }

            document.getElementById("recording-metadata").innerHTML = tableHtml;
            totalFrames = data.total_frames || data.frame_count || 0;
            document.getElementById("frame-counter").innerText =
                `0/${totalFrames}`;
        })
        .catch((error) => {
            console.error("Error loading metadata:", error);
            document.getElementById("recording-metadata").innerHTML =
                '<tr><td colspan="2" class="text-danger">Error loading metadata</td></tr>';
        });
}

function convertToJsTreeFormat(data) {
    let treeData = [];

    for (const groupName in data) {
        const groupNode = {
            text: groupName,
            icon: "fas fa-users",
            state: {
                opened: false,
            },
            children: [],
        };

        const cameras = data[groupName];
        for (const cameraInfo in cameras) {
            const cameraNode = {
                text: cameraInfo,
                icon: "fas fa-video",
                state: {
                    opened: false,
                },
                children: [],
            };

            const models = cameras[cameraInfo];
            for (const modelInfo in models) {
                const modelNode = {
                    text: modelInfo,
                    icon: "fas fa-brain",
                    state: {
                        opened: false,
                    },
                    children: [],
                };

                const dates = models[modelInfo];
                for (const date in dates) {
                    const dateNode = {
                        text: date,
                        icon: "fas fa-calendar-day",
                        state: {
                            opened: false,
                        },
                        children: [],
                    };

                    const sessions = dates[date];
                    for (const session of sessions) {
                        const sessionTime = session.session_id.replace(
                            "session_",
                            "",
                        );
                        const formattedTime = `${sessionTime.substr(9, 2)}:${sessionTime.substr(11, 2)}:${sessionTime.substr(13, 2)}`;

                        let duration = "";
                        if (
                            session.metadata &&
                            session.metadata.duration_seconds
                        ) {
                            const durationSec =
                                session.metadata.duration_seconds;
                            const min = Math.floor(durationSec / 60);
                            const sec = Math.floor(durationSec % 60);
                            duration = `(${min}m ${sec}s)`;
                        }

                        const sessionNode = {
                            text: `${formattedTime} ${duration} <span class="badge bg-primary rounded-pill">${session.frame_count}</span>`,
                            icon: "fas fa-film",
                            a_attr: {
                                "data-path": session.path,
                                "data-title": `${date} ${formattedTime}`,
                            },
                            data: {
                                path: session.path,
                                title: `${date} ${formattedTime}`,
                            },
                            type: "recording",
                        };
                        dateNode.children.push(sessionNode);
                    }
                    modelNode.children.push(dateNode);
                }
                cameraNode.children.push(modelNode);
            }
            groupNode.children.push(cameraNode);
        }
        treeData.push(groupNode);
    }

    return treeData;
}

function initializeJsTree(treeData) {
    $("#recordings-tree")
        .jstree({
            core: {
                data: treeData,
                themes: {
                    responsive: true,
                },
                check_callback: true,
            },
            plugins: ["types", "wholerow", "state"],
            types: {
                default: {
                    icon: "fas fa-folder",
                },
            },
            state: {
                key: "recordings-tree-state",
            },
        })
        .on("select_node.jstree", function (e, data) {
            if (data.node.data && data.node.data.path) {
                selectRecording(data.node.data.path, data.node.data.title);
            } else if (data.node.a_attr && data.node.a_attr["data-path"]) {
                selectRecording(
                    data.node.a_attr["data-path"],
                    data.node.a_attr["data-title"],
                );
            } else if (data.node.original && data.node.original.data) {
                selectRecording(
                    data.node.original.data.path,
                    data.node.original.data.title,
                );
            }
        });
}

function selectRecording(sessionPath, title) {
    if (isPlaying) {
        pausePlayback();
    }

    currentSessionPath = sessionPath;
    document.getElementById("current-recording-title").innerText = title;

    const previewUrl = `/api/recordings/preview${sessionPath}`;
    placeholder.src = previewUrl;
    placeholder.classList.remove("d-none");
    videoStream.classList.add("d-none");
    playBtn.disabled = false;
    playBtn.innerHTML = '<i class="fas fa-play"></i> Play';
    videoCompleted = false;

    loadRecordingMetadata(sessionPath);
}
