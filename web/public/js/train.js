const form = document.getElementById("training-form");
const startBtn = document.getElementById("start-training-btn");
const stopBtn = document.getElementById("stop-training-btn");
const progressBar = document.getElementById("training-progress-bar");
const progressElement = progressBar.parentElement;
const trainingLogs = document.getElementById("training-logs");

let trainingJobId = null;
let logInterval = null;

form.addEventListener("submit", function (e) {
    e.preventDefault();

    const formData = new FormData(form);
    const payload = {};

    for (const [key, value] of formData.entries()) {
        if (key === "use_amp") {
            payload[key] = value === "on";
        } else {
            payload[key] = value;
        }
    }

    startBtn.disabled = true;
    startBtn.innerHTML =
        '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Starting...';

    fetch("/api/train/start", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": document
                .querySelector('meta[name="csrf-token"]')
                .getAttribute("content"),
        },
        body: JSON.stringify(payload),
    })
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                trainingJobId = data.job_id;
                startBtn.classList.add("d-none");
                stopBtn.classList.remove("d-none");
                trainingLogs.textContent = `Training started with job ID: ${trainingJobId}\n`;

                progressBar.style.width = "0%";
                progressBar.textContent = "0%";
                progressBar.setAttribute("aria-valuenow", 0);
                progressElement.classList.add("active");
                progressElement.classList.remove("progress-complete");

                logInterval = setInterval(fetchTrainingStatus, 3000);
            } else {
                startBtn.disabled = false;
                startBtn.innerHTML = "Start Training";
                alert("Failed to start training: " + data.message);
            }
        })
        .catch((error) => {
            startBtn.disabled = false;
            startBtn.innerHTML = "Start Training";
            console.error("Error:", error);
            alert("Failed to start training: " + error.message);
        });
});

stopBtn.addEventListener("click", function () {
    if (!trainingJobId) return;

    if (confirm("Are you sure you want to stop the training?")) {
        stopBtn.disabled = true;
        stopBtn.innerHTML =
            '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Stopping...';

        fetch(`/api/train/stop/${trainingJobId}`, {
            method: "POST",
            headers: {
                "X-CSRF-TOKEN": document
                    .querySelector('meta[name="csrf-token"]')
                    .getAttribute("content"),
            },
        })
            .then((response) => response.json())
            .then((data) => {
                clearInterval(logInterval);
                stopBtn.classList.add("d-none");
                startBtn.classList.remove("d-none");
                startBtn.disabled = false;
                startBtn.innerHTML = "Start Training";

                trainingLogs.textContent += `\nTraining stopped.\n`;
                progressElement.classList.remove("active");
            })
            .catch((error) => {
                stopBtn.disabled = false;
                stopBtn.innerHTML = "Stop Training";
                console.error("Error:", error);
                alert("Failed to stop training: " + error.message);
            });
    }
});

function fetchTrainingStatus() {
    if (!trainingJobId) return;

    fetch(`/api/train/status/${trainingJobId}`)
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                if (data.progress !== undefined) {
                    const progress = Math.round(data.progress);
                    updateProgressBar(progress);
                }

                if (data.logs && data.logs.length > 0) {
                    trainingLogs.textContent += data.logs.join("\n") + "\n";
                    trainingLogs.scrollTop = trainingLogs.scrollHeight;
                }

                if (data.status === "completed") {
                    clearInterval(logInterval);
                    stopBtn.classList.add("d-none");
                    startBtn.classList.remove("d-none");
                    startBtn.disabled = false;
                    startBtn.innerHTML = "Start Training";
                    trainingLogs.textContent +=
                        "\nTraining completed successfully!\n";

                    progressElement.classList.remove("active");
                    progressElement.classList.add("progress-complete");
                    progressBar.style.width = "100%";
                    progressBar.textContent = "Completed!";
                } else if (data.status === "failed") {
                    clearInterval(logInterval);
                    stopBtn.classList.add("d-none");
                    startBtn.classList.remove("d-none");
                    startBtn.disabled = false;
                    startBtn.innerHTML = "Start Training";
                    trainingLogs.textContent += `\nTraining failed: ${data.error}\n`;

                    progressElement.classList.remove("active");
                    progressBar.classList.remove(
                        "bg-success",
                        "bg-info",
                        "bg-primary",
                    );
                    progressBar.classList.add("bg-danger");
                    progressBar.textContent = "Failed";
                }
            }
        })
        .catch((error) => {
            console.error("Error fetching training status:", error);
        });
}

function updateProgressBar(progress) {
    progressBar.style.width = `${progress}%`;
    progressBar.textContent = `${progress}%`;
    progressBar.setAttribute("aria-valuenow", progress);
    progressBar.classList.remove(
        "bg-success",
        "bg-info",
        "bg-primary",
        "bg-warning",
    );

    if (progress < 25) {
        progressBar.classList.add("bg-info");
    } else if (progress < 50) {
        progressBar.classList.add("bg-primary");
    } else if (progress < 75) {
        progressBar.classList.add("bg-info");
    } else {
        progressBar.classList.add("bg-success");
    }
}

function padZero(num) {
    return num.toString().padStart(2, "0");
}

fetch("/api/train/annotation-files")
    .then((response) => response.json())
    .then((data) => {
        const selectElement = document.getElementById("ann_file");
        selectElement.innerHTML = "";

        if (data.success && data.files.length > 0) {
            data.files.forEach((file) => {
                const option = document.createElement("option");
                option.value = file;
                option.textContent = file;
                selectElement.appendChild(option);
            });
            if (selectElement.options.length > 0) {
                selectElement.value = "ntu_custom_dataset.pkl";
            }
        } else {
            const option = document.createElement("option");
            option.value = "";
            option.textContent = "No annotation files found";
            selectElement.appendChild(option);
        }
    })
    .catch((error) => {
        console.error("Error loading annotation files:", error);
        const selectElement = document.getElementById("ann_file");
        selectElement.innerHTML =
            '<option value="">Error loading files</option>';
    });
