let currentProcessId = null;
let statusCheckInterval = null;
let startTime = null;
let apiUrl = "http://localhost:8888";
let currentFrameIndex = 0;
let totalFrames = 0;
let currentVideoName = "";
let processingNextVideo = false;

function loadDirectory(path, targetElement, inputField) {
    $.ajax({
        url: `${apiUrl}/api/get-directory-contents`,
        method: "GET",
        data: {
            path: path,
        },
        success: function (response) {
            if (response.success) {
                const contents = response.contents;
                const html = `
                            <ul class="list-group max-h-[200px]">
                                ${path ? `<li class="list-group-item directory-item" data-path="${path.split("/").slice(0, -1).join("/")}"><i class="fas fa-arrow-up"></i> Up</li>` : ""}
                                ${contents.directories.map((dir) => `<li class="list-group-item directory-item" data-path="${path ? path + "/" + dir : dir}"><i class="fas fa-folder"></i> ${dir}</li>`).join("")}
                            </ul>
                        `;
                $(targetElement).html(html).show();

                if (path) {
                    $(inputField).val(path);
                } else {
                    $(inputField).val("");
                }

                $(".directory-item").click(function () {
                    const newPath = $(this).data("path");
                    loadDirectory(newPath, targetElement, inputField);
                });
            } else {
                console.error("Error loading directory:", response.message);
                alert("Error loading directory: " + response.message);
            }
        },
        error: function (xhr, status, error) {
            console.error("AJAX error:", error);
            alert("Failed to load directory structure. Please try again.");
        },
    });
}

$("#browseInputBtn").click(function () {
    loadDirectory("", "#inputDirectoryTree", "#input_folder");
});

$("#browseOutputBtn").click(function () {
    loadDirectory("", "#outputDirectoryTree", "#output_folder");
});

$("#browseTrainBtn").click(function () {
    loadDirectory("", "#trainDirectoryTree", "#train_folder");
});

$("#browseValBtn").click(function () {
    loadDirectory("", "#valDirectoryTree", "#val_folder");
});

$("#browseTestBtn").click(function () {
    loadDirectory("", "#testDirectoryTree", "#test_folder");
});

$("#extractionForm").submit(function (e) {
    e.preventDefault();

    const inputFolder = $("#input_folder").val();
    const outputFolder = $("#output_folder").val();

    if (!inputFolder) {
        alert("Please select an input folder");
        return;
    }

    if (!outputFolder) {
        alert("Please select an output folder");
        return;
    }

    const requestData = {
        input_folder: inputFolder,
        output_folder: outputFolder,
        device: $("#device").val(),
        skip_postproc: $("#skip_postproc").is(":checked"),
    };

    $.ajax({
        url: `${apiUrl}/api/extract-pose`,
        method: "POST",
        contentType: "application/json",
        data: JSON.stringify(requestData),
        success: function (response) {
            if (response.success) {
                $("#extractionForm").hide();
                $("#progressSection").show();
                $("#previewSection").hide();

                currentProcessId = response.process_id;
                startTime = new Date();

                $("#inputFolderDisplay").text(inputFolder);
                $("#outputFolderDisplay").text(outputFolder);

                addLogEntry("Extraction process started");
                addLogEntry(`Input folder: ${inputFolder}`);
                addLogEntry(`Output folder: ${outputFolder}`);

                checkExtractionStatus();
                statusCheckInterval = setInterval(checkExtractionStatus, 5000);
            } else {
                alert("Failed to start extraction: " + response.message);
                addLogEntry("ERROR: " + response.message);
            }
        },
        error: function (xhr, status, error) {
            console.error("AJAX error:", error);
            alert("Failed to start extraction process. Please try again.");
            addLogEntry("ERROR: Failed to start extraction process");
        },
    });
});

$("#combineForm").submit(function (e) {
    e.preventDefault();

    const trainFolder = $("#train_folder").val();
    const valFolder = $("#val_folder").val();
    const testFolder = $("#test_folder").val();
    const outputFile = $("#output_file").val();

    if (!trainFolder && !valFolder && !testFolder) {
        alert(
            "Please select at least one data directory (train, validation, or test)",
        );
        return;
    }

    const requestData = {
        train_folder: trainFolder,
        val_folder: valFolder,
        test_folder: testFolder,
        output_file: outputFile,
    };

    addCombineLogEntry("Starting PKL combining process...");
    addCombineLogEntry(`Train folder: ${trainFolder || "None"}`);
    addCombineLogEntry(`Validation folder: ${valFolder || "None"}`);
    addCombineLogEntry(`Test folder: ${testFolder || "None"}`);
    addCombineLogEntry(`Output file: ${outputFile}`);

    $.ajax({
        url: `${apiUrl}/api/combine-pkl`,
        method: "POST",
        contentType: "application/json",
        data: JSON.stringify(requestData),
        success: function (response) {
            if (response.success) {
                $("#combineForm").hide();
                $("#combineResultSection").show();
                $("#combineResultMessage").text(response.message);

                const statsList = $("#combineStatsList");
                statsList.empty();
                statsList.append(
                    `<li class="list-group-item"><strong>Total samples:</strong> ${response.stats.total}</li>`,
                );

                if (response.stats.train !== undefined) {
                    statsList.append(
                        `<li class="list-group-item"><strong>Train samples:</strong> ${response.stats.train}</li>`,
                    );
                }
                if (response.stats.val !== undefined) {
                    statsList.append(
                        `<li class="list-group-item"><strong>Validation samples:</strong> ${response.stats.val}</li>`,
                    );
                }
                if (response.stats.test !== undefined) {
                    statsList.append(
                        `<li class="list-group-item"><strong>Test samples:</strong> ${response.stats.test}</li>`,
                    );
                }

                statsList.append(
                    `<li class="list-group-item"><strong>Output file:</strong> ${response.output_file}</li>`,
                );

                addCombineLogEntry(
                    "PKL combining process completed successfully",
                );
            } else {
                alert("Failed to combine PKL files: " + response.message);
                addCombineLogEntry("ERROR: " + response.message);
            }
        },
        error: function (xhr, status, error) {
            console.error("AJAX error:", error);
            alert("Failed to combine PKL files. Please try again.");
            addCombineLogEntry("ERROR: Failed to combine PKL files");
        },
    });
});

$("#newCombineBtn").click(function () {
    $("#combineForm").show();
    $("#combineResultSection").hide();
    addCombineLogEntry("UI reset for new dataset creation");
});

function checkExtractionStatus() {
    if (!currentProcessId) return;

    $.ajax({
        url: `${apiUrl}/api/extraction-status/${currentProcessId}`,
        method: "GET",
        success: function (response) {
            if (response.success) {
                const processInfo = response.process_info;

                const progress = processInfo.progress || 0;
                $("#extractionProgress").css("width", `${progress}%`);
                $("#progressPercentage").text(`${progress}%`);

                let statusText = "Running";
                let statusClass = "bg-primary";

                if (processInfo.status === "completed") {
                    statusText = "Completed";
                    statusClass = "bg-success";
                    clearInterval(statusCheckInterval);
                    addLogEntry("Extraction process completed successfully");
                    $("#reopenPreviewContainer").hide();
                } else if (processInfo.status === "error") {
                    statusText = "Error";
                    statusClass = "bg-danger";
                    clearInterval(statusCheckInterval);
                    addLogEntry("ERROR: " + processInfo.message);
                    $("#reopenPreviewContainer").hide();
                } else if (processInfo.status === "cancelled") {
                    statusText = "Cancelled";
                    statusClass = "bg-warning";
                    clearInterval(statusCheckInterval);
                    addLogEntry("Extraction process cancelled by user");
                    $("#reopenPreviewContainer").hide();
                } else if (processInfo.status === "awaiting_confirmation") {
                    statusText = "Awaiting Confirmation";
                    statusClass = "bg-info";
                    clearInterval(statusCheckInterval);
                    addLogEntry("Extraction completed, awaiting confirmation");

                    if (
                        !processingNextVideo &&
                        !$("#previewModal").is(":visible")
                    ) {
                        addReopenModalButton();

                        if (processInfo.current_video_name) {
                            currentVideoName = processInfo.current_video_name;
                            $("#currentVideoName").text(currentVideoName);
                        }

                        if (processInfo.is_new_video !== false) {
                            currentFrameIndex = 0;
                            loadFramePreview(currentFrameIndex);
                            $("#previewModal").modal("show");
                        }
                    }
                } else if (processInfo.status === "processing_next") {
                    statusText = `Processing Next Video: ${processInfo.current_video_name || ""}`;
                    statusClass = "bg-info";
                    processingNextVideo = true;
                    addLogEntry(
                        `Processing next video: ${processInfo.current_video_name || ""}`,
                    );

                    $("#progressText").text(
                        `Processing next video: ${processInfo.current_video_name || ""}`,
                    );

                    $("#reopenPreviewContainer").hide();

                    if (
                        processInfo.processed_count &&
                        processInfo.total_videos
                    ) {
                        $("#processingProgress").text(
                            `Processing video ${processInfo.processed_count} of ${processInfo.total_videos}`,
                        );
                    }
                }

                $("#statusDisplay")
                    .text(statusText)
                    .removeClass()
                    .addClass(`badge ${statusClass}`);

                if (startTime) {
                    const now = new Date();
                    const elapsedMs = now - startTime;
                    const elapsedSec = Math.floor(elapsedMs / 1000);
                    const hours = Math.floor(elapsedSec / 3600);
                    const minutes = Math.floor((elapsedSec % 3600) / 60);
                    const seconds = elapsedSec % 60;
                    $("#timeElapsed").text(
                        `${hours.toString().padStart(2, "0")}:${minutes.toString().padStart(2, "0")}:${seconds.toString().padStart(2, "0")}`,
                    );
                }

                if (
                    ["completed", "error", "cancelled"].includes(
                        processInfo.status,
                    )
                ) {
                    $("#cancelExtractionBtn")
                        .text("Start New Extraction")
                        .removeClass("btn-danger")
                        .addClass("btn-primary")
                        .off("click")
                        .click(function () {
                            resetExtractionUI();
                        });
                }
            } else {
                console.error("Error checking status:", response.message);
                addLogEntry(
                    "ERROR: Failed to check extraction status: " +
                        response.message,
                );
            }
        },
        error: function (xhr, status, error) {
            console.error("AJAX error:", error);
            addLogEntry("ERROR: Failed to communicate with server");
        },
    });
}

function loadFramePreview(frameIndex) {
    if (!currentProcessId) return;

    $.ajax({
        url: `${apiUrl}/api/preview-annotations/${currentProcessId}/${frameIndex}`,
        method: "GET",
        success: function (response) {
            if (response.success) {
                $("#framePreview").attr(
                    "src",
                    `data:image/jpeg;base64,${response.image}`,
                );

                totalFrames = response.total_frames;
                $("#frameCounter").text(
                    `Frame ${frameIndex + 1} of ${totalFrames}`,
                );

                $("#frameSlider").val(frameIndex);
                $("#frameSlider").attr("max", totalFrames - 1);

                $("#prevFrameBtn").prop("disabled", frameIndex === 0);
                $("#nextFrameBtn").prop(
                    "disabled",
                    frameIndex === totalFrames - 1,
                );
            } else {
                console.error("Error loading frame preview:", response.message);
                alert("Error loading frame preview: " + response.message);
            }
        },
        error: function (xhr, status, error) {
            console.error("AJAX error:", error);
            alert("Failed to load frame preview. Please try again.");
        },
    });
}

$("#prevFrameBtn").click(function () {
    if (currentFrameIndex > 0) {
        currentFrameIndex--;
        loadFramePreview(currentFrameIndex);
    }
});

$("#nextFrameBtn").click(function () {
    if (currentFrameIndex < totalFrames - 1) {
        currentFrameIndex++;
        loadFramePreview(currentFrameIndex);
    }
});

$("#frameSlider").on("input", function () {
    currentFrameIndex = parseInt($(this).val());
    loadFramePreview(currentFrameIndex);
});

$("#confirmSaveBtn").click(function () {
    if (!currentProcessId || !currentVideoName) return;

    $(this).prop("disabled", true);
    $("#skipSaveBtn").prop("disabled", true);

    $.ajax({
        url: `${apiUrl}/api/confirm-save/${currentProcessId}/${currentVideoName}`,
        method: "POST",
        contentType: "application/json",
        data: JSON.stringify({ confirm: true }),
        success: function (response) {
            if (response.success) {
                addLogEntry(response.message);
                processNextVideo();
            } else {
                alert("Failed to save: " + response.message);
                addLogEntry("ERROR: " + response.message);
                $("#confirmSaveBtn").prop("disabled", false);
            }
        },
        error: function (xhr, status, error) {
            console.error("AJAX error:", error);
            alert("Failed to save annotations. Please try again.");
            addLogEntry("ERROR: Failed to save annotations");
            $("#confirmSaveBtn").prop("disabled", false);
        },
    });
});

$("#skipSaveBtn").click(function () {
    if (!currentProcessId || !currentVideoName) return;

    $(this).prop("disabled", true);
    $("#confirmSaveBtn").prop("disabled", true);

    $.ajax({
        url: `${apiUrl}/api/confirm-save/${currentProcessId}/${currentVideoName}`,
        method: "POST",
        contentType: "application/json",
        data: JSON.stringify({ confirm: false }),
        success: function (response) {
            if (response.success) {
                addLogEntry(response.message);
                processNextVideo();
            } else {
                alert("Failed to skip: " + response.message);
                addLogEntry("ERROR: " + response.message);
                $("#skipSaveBtn").prop("disabled", false);
            }
        },
        error: function (xhr, status, error) {
            console.error("AJAX error:", error);
            alert("Failed to skip. Please try again.");
            addLogEntry("ERROR: Failed to skip current video");
            $("#skipSaveBtn").prop("disabled", false);
        },
    });
});

function processNextVideo() {
    processingNextVideo = true;
    $("#progressText").text("Processing next video...");
    $("#previewModal").modal("hide");

    $.ajax({
        url: `${apiUrl}/api/next-video/${currentProcessId}`,
        method: "POST",
        success: function (response) {
            if (response.success) {
                if (response.status === "completed") {
                    addLogEntry("All videos processed successfully");
                    processingNextVideo = false;
                    $("#statusDisplay")
                        .text("Completed")
                        .removeClass()
                        .addClass("badge bg-success");

                    $("#progressText").text(
                        "All videos processed successfully",
                    );

                    $("#cancelExtractionBtn")
                        .text("Start New Extraction")
                        .removeClass("btn-danger")
                        .addClass("btn-primary")
                        .off("click")
                        .click(function () {
                            resetExtractionUI();
                        });
                } else {
                    currentVideoName = response.video_name;
                    $("#currentVideoName").text(currentVideoName);
                    currentFrameIndex = 0;
                    loadFramePreview(currentFrameIndex);
                    addLogEntry(`Processing video: ${currentVideoName}`);

                    $("#processingProgress").text(
                        `Processing video ${response.processed_count} of ${response.total_videos}`,
                    );

                    const progress = Math.round(
                        (response.processed_count / response.total_videos) *
                            100,
                    );
                    $("#extractionProgress").css("width", `${progress}%`);
                    $("#progressPercentage").text(`${progress}%`);

                    $("#confirmSaveBtn").prop("disabled", false);
                    $("#skipSaveBtn").prop("disabled", false);

                    processingNextVideo = false;
                    $("#previewModal").modal("show");
                }
            } else {
                alert("Failed to process next video: " + response.message);
                addLogEntry("ERROR: " + response.message);
                processingNextVideo = false;
            }
        },
        error: function (xhr, status, error) {
            console.error("AJAX error:", error);
            alert("Failed to process next video. Please try again.");
            addLogEntry("ERROR: Failed to process next video");
            processingNextVideo = false;
        },
    });
}

function addReopenModalButton() {
    if ($("#reopenModalBtn").length === 0) {
        $("#progressSection").append(`
            <div class="mt-3 d-grid" id="reopenPreviewContainer">
                <button class="btn btn-info" id="reopenModalBtn">
                    <i class="fas fa-eye"></i> Open Preview Modal
                </button>
            </div>
        `);

        $("#reopenModalBtn").click(function () {
            if (currentProcessId && currentVideoName) {
                loadFramePreview(currentFrameIndex);
                $("#previewModal").modal("show");
                addLogEntry(
                    "Reopen the video preview modal: " + currentVideoName,
                );
            } else {
                addLogEntry("Cannot reopen preview modal - no pending videos");
            }
        });
    }
}

$("#previewModal").on("hidden.bs.modal", function () {
    if (!processingNextVideo && currentProcessId) {
        addLogEntry("Modal closes, continues to monitor extraction status");
        addReopenModalButton();
        $("#statusDisplay")
            .text("Awaiting Confirmation")
            .removeClass()
            .addClass("badge bg-info");
        $("#progressText").text(
            "Waiting for confirmation for video: " + currentVideoName,
        );
        statusCheckInterval = setInterval(checkExtractionStatus, 5000);
    }
});

$("#cancelExtractionBtn").click(function () {
    if (confirm("Are you sure you want to cancel the extraction process?")) {
        $.ajax({
            url: `${apiUrl}/api/cancel-extraction/${currentProcessId}`,
            method: "POST",
            success: function (response) {
                if (response.success) {
                    addLogEntry("Cancellation request sent");
                } else {
                    alert("Failed to cancel extraction: " + response.message);
                    addLogEntry(
                        "ERROR: Failed to cancel extraction: " +
                            response.message,
                    );
                }
            },
            error: function (xhr, status, error) {
                console.error("AJAX error:", error);
                alert("Failed to send cancellation request");
                addLogEntry("ERROR: Failed to send cancellation request");
            },
        });
    }
});

function addLogEntry(message) {
    const now = new Date();
    const timestamp = `${now.toLocaleDateString()} ${now.toLocaleTimeString()}`;
    $("#extractionLog").append(`<div>[${timestamp}] ${message}</div>`);
    const logElement = document.getElementById("extractionLog");
    logElement.scrollTop = logElement.scrollHeight;
}

function addCombineLogEntry(message) {
    const now = new Date();
    const timestamp = `${now.toLocaleDateString()} ${now.toLocaleTimeString()}`;
    $("#combineLog").append(`<div>[${timestamp}] ${message}</div>`);
    const logElement = document.getElementById("combineLog");
    logElement.scrollTop = logElement.scrollHeight;
}

function resetExtractionUI() {
    $("#extractionForm").show();
    $("#progressSection").hide();
    $("#previewModal").modal("hide");
    $("#reopenPreviewContainer").remove();

    $("#extractionProgress").css("width", "0%");
    $("#progressPercentage").text("0%");
    $("#progressText").text("Starting extraction...");

    $("#processingProgress").text("Processing video");

    $("#statusDisplay")
        .text("Running")
        .removeClass()
        .addClass("badge bg-primary");

    $("#timeElapsed").text("00:00:00");

    currentProcessId = null;
    startTime = null;
    currentFrameIndex = 0;
    totalFrames = 0;
    currentVideoName = "";
    processingNextVideo = false;

    if (statusCheckInterval) {
        clearInterval(statusCheckInterval);
        statusCheckInterval = null;
    }

    $("#cancelExtractionBtn")
        .text("Cancel Extraction")
        .removeClass("btn-primary")
        .addClass("btn-danger");

    addLogEntry("UI reset for new extraction");
}

addLogEntry("PoseC3D Data Extraction Tool initialized");
addCombineLogEntry("PKL Combine Tool initialized");
