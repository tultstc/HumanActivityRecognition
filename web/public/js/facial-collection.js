let capturedImages = [];
let hasFaceData = false;

function updateCamera() {
    var cameraId = document.getElementById("camera-select").value;
    var imgElement = document.getElementById("camera-stream");
    imgElement.src = "http://localhost:15440/get_stream/" + cameraId;
}

function captureImage() {
    const personName = document.getElementById("person-name").value.trim();
    if (!personName) {
        Swal.fire({
            icon: "warning",
            title: "Missing Information",
            text: "Please enter person's name before taking photo",
        });
        return;
    }

    const cameraId = document.getElementById("camera-select").value;

    fetch(`api/tools/facial-collection/capture`, {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": document
                .querySelector('meta[name="csrf-token"]')
                .getAttribute("content"),
        },
        body: JSON.stringify({
            camera_id: cameraId,
            person_name: personName,
        }),
    })
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                capturedImages.push({
                    path: data.image_path,
                    person: personName,
                    preview: data.preview_url,
                });

                updateCapturedImagesDisplay();
            } else {
                Swal.fire({
                    icon: "error",
                    title: "Error",
                    text: data.message,
                });
            }
        })
        .catch((error) => {
            console.error("Error:", error);
            Swal.fire({
                icon: "error",
                title: "Error",
                text: "An error occurred while taking a photo.",
            });
        });
}

function updateCapturedImagesDisplay() {
    const container = document.getElementById("captured-images");
    container.innerHTML = "";

    capturedImages.forEach((img, index) => {
        const imgDiv = document.createElement("div");
        imgDiv.className = "captured-image-container m-1";
        imgDiv.style.position = "relative";

        const imgEl = document.createElement("img");
        imgEl.src = img.preview;
        imgEl.className = "img-thumbnail";
        imgEl.style.width = "80px";
        imgEl.style.height = "80px";
        imgEl.style.objectFit = "cover";
        imgEl.style.cursor = "pointer";
        imgEl.onclick = () => previewImage(img.preview);

        const removeBtn = document.createElement("button");
        removeBtn.className = "btn btn-danger btn-sm";
        removeBtn.innerHTML = "&times;";
        removeBtn.style.position = "absolute";
        removeBtn.style.top = "0";
        removeBtn.style.right = "0";
        removeBtn.style.padding = "0 5px";
        removeBtn.onclick = (e) => {
            e.stopPropagation();
            removeImage(index);
        };

        imgDiv.appendChild(imgEl);
        imgDiv.appendChild(removeBtn);
        container.appendChild(imgDiv);
    });
}

function previewImage(imageUrl) {
    const previewImg = document.getElementById("preview-image");
    previewImg.src = imageUrl;

    const previewModal = new bootstrap.Modal(
        document.getElementById("previewModal"),
    );
    previewModal.show();
}

function removeImage(index) {
    const removedImage = capturedImages.splice(index, 1)[0];

    fetch(`api/tools/facial-collection/remove-image`, {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": document
                .querySelector('meta[name="csrf-token"]')
                .getAttribute("content"),
        },
        body: JSON.stringify({
            image_path: removedImage.path,
        }),
    });

    updateCapturedImagesDisplay();
}

function saveFaces() {
    const personName = document.getElementById("person-name").value.trim();
    if (!personName || capturedImages.length === 0) {
        Swal.fire({
            icon: "warning",
            title: "Missing Information",
            text: "Please enter a person's name and take at least one photo",
        });
        return;
    }

    fetch(`api/tools/facial-collection/save`, {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": document
                .querySelector('meta[name="csrf-token"]')
                .getAttribute("content"),
        },
        body: JSON.stringify({
            person_name: personName,
            images: capturedImages.map((img) => img.path),
        }),
    })
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                capturedImages = [];
                updateCapturedImagesDisplay();
                refreshDatabaseInfo();

                Swal.fire({
                    icon: "success",
                    title: "Success",
                    text: `Saved ${data.count} photos for user "${personName}"`,
                });
            } else {
                Swal.fire({
                    icon: "error",
                    title: "Error",
                    text: data.message,
                });
            }
        })
        .catch((error) => {
            console.error("Error:", error);
            Swal.fire({
                icon: "error",
                title: "Error",
                text: "An error occurred while saving the images.",
            });
        });
}

function processDatabase() {
    Swal.fire({
        title: "Processing",
        text: "Processing face database...",
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        },
    });

    fetch(`api/tools/facial-collection/process-database`, {
        method: "POST",
        headers: {
            "X-CSRF-TOKEN": document
                .querySelector('meta[name="csrf-token"]')
                .getAttribute("content"),
        },
    })
        .then((response) => response.json())
        .then((data) => {
            Swal.close();

            if (data.success) {
                refreshDatabaseInfo();

                Swal.fire({
                    icon: "success",
                    title: "Database processing successful",
                    text: `Processed ${data.face_count} faces into database`,
                });
            } else {
                Swal.fire({
                    icon: "error",
                    title: "Error",
                    text: data.message,
                });
            }
        })
        .catch((error) => {
            Swal.close();
            console.error("Error:", error);
            Swal.fire({
                icon: "error",
                title: "Error",
                text: "An error occurred while processing the database.",
            });
        });
}

function updateButtonVisibility(isUpdateMode = false) {
    const saveBtn = document.getElementById("save-btn");
    const processBtn = document.getElementById("process-btn");

    if (isUpdateMode) {
        saveBtn.style.display = "none";
        processBtn.style.display = "none";
    } else {
        if (hasFaceData) {
            saveBtn.style.display = "inline-block";
            processBtn.style.display = "inline-block";
        } else {
            saveBtn.style.display = "inline-block";
            processBtn.style.display = "inline-block";
        }
    }
}

function refreshDatabaseInfo() {
    const databaseInfoContent = document.getElementById(
        "database-info-content",
    );
    databaseInfoContent.innerHTML =
        '<p class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</p>';

    fetch(`api/tools/facial-collection/extract-database`, {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": document
                .querySelector('meta[name="csrf-token"]')
                .getAttribute("content"),
        },
    })
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                const personData = data.data.persons;
                const totalFaces = data.data.total_faces;
                const uniquePersons = data.data.unique_persons;

                hasFaceData = totalFaces > 0;
                updateButtonVisibility(false);

                let content = `
                            <div class="mb-3">
                                <p><strong>Total number of faces:</strong> ${totalFaces}</p>
                                <p><strong>Number of people:</strong> ${uniquePersons}</p>
                            </div>
                        `;

                if (uniquePersons > 0) {
                    content += `
                                <div class="table-responsive">
                                    <table class="table table-sm table-striped">
                                        <thead>
                                            <tr>
                                                <th>Name</th>
                                                <th>Number of faces</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                            `;

                    for (const [name, info] of Object.entries(personData)) {
                        content += `
                            <tr>
                                <td>${name}</td>
                                <td>${info.count}</td>
                                <td class="d-flex gap-1">
                                    <button class="btn btn-sm btn-warning" onclick="prepareUpdatePerson('${name}')">
                                        Update
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="confirmDeletePerson('${name}')">
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        `;
                    }

                    content += `
                                        </tbody>
                                    </table>
                                </div>
                            `;
                } else {
                    content += `<p class="text-center">No face data yet.</p>`;
                }

                databaseInfoContent.innerHTML = content;
            } else {
                databaseInfoContent.innerHTML = `
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle"></i> ${data.message}
                            </div>
                        `;

                hasFaceData = false;
                updateButtonVisibility();
            }
        })
        .catch((error) => {
            console.error("Error:", error);
            databaseInfoContent.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle"></i> Unable to load database information.
                        </div>
                    `;

            hasFaceData = false;
            updateButtonVisibility();
        });
}

function confirmDeletePerson(personName) {
    Swal.fire({
        title: "Delete Confirmation",
        text: `Are you sure you want to delete all facial data for "${personName}"?`,
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        cancelButtonColor: "#3085d6",
        confirmButtonText: "Yes, delete it!",
        cancelButtonText: "Cancel"
    }).then((result) => {
        if (result.isConfirmed) {
            deletePerson(personName);
        }
    });
}

function deletePerson(personName) {
    fetch(`api/tools/facial-collection/delete-person`, {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": document
                .querySelector('meta[name="csrf-token"]')
                .getAttribute("content"),
        },
        body: JSON.stringify({
            person_name: personName,
        }),
    })
    .then((response) => response.json())
    .then((data) => {
        if (data.success) {
            Swal.fire({
                title: "Processing",
                text: "Updating face database after deletion...",
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                },
            });

            fetch(`api/tools/facial-collection/process-database`, {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": document
                        .querySelector('meta[name="csrf-token"]')
                        .getAttribute("content"),
                },
            })
            .then((response) => response.json())
            .then((processData) => {
                Swal.close();
                
                if (processData.success) {
                    refreshDatabaseInfo();
                    
                    Swal.fire({
                        icon: "success",
                        title: "Deleted and Updated!",
                        text: `${data.message} and database has been processed successfully.`,
                    });
                } else {
                    Swal.fire({
                        icon: "warning", 
                        title: "Deletion Successful, Processing Failed",
                        text: `${data.message}, but database processing failed: ${processData.message}`,
                    });
                    refreshDatabaseInfo();
                }
            })
            .catch((error) => {
                Swal.close();
                console.error("Error:", error);
                Swal.fire({
                    icon: "warning",
                    title: "Partial Success",
                    text: `${data.message}, but an error occurred while processing the database.`,
                });
                refreshDatabaseInfo();
            });
        } else {
            Swal.fire({
                icon: "error",
                title: "Error",
                text: data.message,
            });
        }
    })
    .catch((error) => {
        console.error("Error:", error);
        Swal.fire({
            icon: "error",
            title: "Error",
            text: "An error occurred while deleting the person.",
        });
    });
}

function prepareUpdatePerson(personName) {
    document.getElementById("person-name").value = personName;

    Swal.fire({
        icon: "info",
        title: "Face update",
        text: `You are about to update the facial data for "${personName}". Take new photos and then press "Save & Update" to update.`,
        confirmButtonText: "Continue",
    });

    addUpdateButton();
    updateButtonVisibility(true);
}

function addUpdateButton() {
    if (!document.getElementById("update-btn")) {
        const captureBtn = document.getElementById("capture-btn");
        const updateBtn = document.createElement("button");
        updateBtn.id = "update-btn";
        updateBtn.className = "btn btn-warning btn-block";
        updateBtn.textContent = "Save & Update";
        updateBtn.onclick = function () {
            saveFacesAndUpdate();
        };

        captureBtn.insertAdjacentElement("afterend", updateBtn);
    }
}

function saveFacesAndUpdate() {
    const personName = document.getElementById("person-name").value.trim();
    if (!personName || capturedImages.length === 0) {
        Swal.fire({
            icon: "warning",
            title: "Missing Information",
            text: "Please enter a person's name and take at least one photo",
        });
        return;
    }

    fetch(`api/tools/facial-collection/save`, {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": document
                .querySelector('meta[name="csrf-token"]')
                .getAttribute("content"),
        },
        body: JSON.stringify({
            person_name: personName,
            images: capturedImages.map((img) => img.path),
        }),
    })
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                updateFaceDatabase(personName, true);
                capturedImages = [];
                updateCapturedImagesDisplay();

                if (document.getElementById("update-btn")) {
                    document.getElementById("update-btn").remove();
                }
                updateButtonVisibility(false);
            } else {
                Swal.fire({
                    icon: "error",
                    title: "Error",
                    text: data.message,
                });
            }
        })
        .catch((error) => {
            console.error("Error:", error);
            Swal.fire({
                icon: "error",
                title: "Error",
                text: "An error occurred while saving the images.",
            });
        });
}

function updateFaceDatabase(personName, updateExisting) {
    Swal.fire({
        title: "Processing",
        text: "Updating face database...",
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        },
    });

    fetch(`api/tools/facial-collection/update-database`, {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": document
                .querySelector('meta[name="csrf-token"]')
                .getAttribute("content"),
        },
        body: JSON.stringify({
            mode: "merge",
            update_existing: updateExisting,
        }),
    })
        .then((response) => response.json())
        .then((data) => {
            Swal.close();

            if (data.success) {
                refreshDatabaseInfo();

                Swal.fire({
                    icon: "success",
                    title: "Success",
                    text: `${data.message}`,
                    confirmButtonText: "OK",
                });
            } else {
                Swal.fire({
                    icon: "error",
                    title: "Error",
                    text: data.message,
                });
            }
        })
        .catch((error) => {
            Swal.close();
            console.error("Error:", error);
            Swal.fire({
                icon: "error",
                title: "Error",
                text: "An error occurred while updating the database.",
            });
        });
}

document.addEventListener("DOMContentLoaded", function () {
    var selectElement = document.getElementById("camera-select");
    if (selectElement.options.length > 0) {
        selectElement.selectedIndex = 0;
        updateCamera();
    }

    refreshDatabaseInfo();

    document.querySelectorAll('[data-bs-dismiss="modal"]').forEach((button) => {
        button.addEventListener("click", function () {
            const modalId = this.closest(".modal").id;
            const modalElement = document.getElementById(modalId);
            const modalInstance = bootstrap.Modal.getInstance(modalElement);
            if (modalInstance) {
                modalInstance.hide();
            }
        });
    });
});
