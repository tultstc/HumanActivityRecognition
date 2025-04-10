let trackedPersons = {};
const REMOVAL_TIMEOUT = 10;
let removedPersons = {};
let lastImageUpdateTime = {};
let imageCache = {};
let streamConnections = {};

function formatTime(timestamp) {
    const date = new Date(timestamp * 1000);
    return date.toLocaleString();
}

function getTimeAgo(timestamp) {
    const now = Math.floor(Date.now() / 1000);
    const seconds = now - timestamp;

    if (seconds < 60) return `${seconds} second${seconds !== 1 ? "s" : ""} ago`;
    if (seconds < 3600)
        return `${Math.floor(seconds / 60)} minute${Math.floor(seconds / 60) !== 1 ? "s" : ""} ago`;
    return `${Math.floor(seconds / 3600)} hour${Math.floor(seconds / 3600) !== 1 ? "s" : ""} ago`;
}

async function fetchActivePersons() {
    try {
        const response = await fetch(
            "http://localhost:8899/api/active_persons",
        );
        const data = await response.json();

        if (data.status === "success") {
            updatePersonsDisplay(data.data);
        } else {
            console.error("Error fetching active persons:", data.message);
        }
    } catch (error) {
        console.error("Error fetching active persons:", error);
    }
}

function updatePersonsDisplay(personsData) {
    const now = Math.floor(Date.now() / 1000);
    const activePersonSet = new Set(personsData.map((person) => person.name));

    personsData.forEach((person) => {
        const name = person.name;
        trackedPersons[name] = {
            ...person,
            lastActiveTime: now,
        };

        if (removedPersons[name]) {
            delete removedPersons[name];
        }
    });

    Object.keys(trackedPersons).forEach((name) => {
        if (!activePersonSet.has(name)) {
            const timeSinceLastSeen = now - trackedPersons[name].last_seen;

            if (timeSinceLastSeen >= REMOVAL_TIMEOUT) {
                removedPersons[name] = {
                    ...trackedPersons[name],
                    removedAt: now,
                };
                delete trackedPersons[name];

                if (streamConnections[name]) {
                    streamConnections[name].close();
                    delete streamConnections[name];
                }
            }
        }
    });

    renderFaceCards();
}

function renderFaceCards() {
    const faceDetectionContainer = document.getElementById(
        "faceDetectionContainer",
    );
    const existingCards = {};

    Array.from(faceDetectionContainer.children).forEach((card) => {
        const name = card.id.replace("face-card-", "");
        existingCards[name] = card;
    });

    const now = Math.floor(Date.now() / 1000);
    const personNames = Object.keys(trackedPersons).sort((a, b) => {
        return (
            trackedPersons[b].lastActiveTime - trackedPersons[a].lastActiveTime
        );
    });

    const orderedCards = [];

    personNames.forEach((name) => {
        const personData = trackedPersons[name];
        const isActive = now - personData.last_seen < REMOVAL_TIMEOUT;
        let card;

        if (existingCards[name]) {
            card = existingCards[name];

            const statusIndicator = card.querySelector(".rounded-full");
            if (statusIndicator) {
                statusIndicator.className = `absolute top-1 right-1 w-3 h-3 rounded-full ${isActive ? "bg-green-500" : "bg-red-500"}`;
            }

            const lastSeenElement = card.querySelector(`#last-seen-${name}`);
            if (lastSeenElement) {
                lastSeenElement.textContent = getTimeAgo(personData.last_seen);
            }

            const similarityElement = card.querySelector(".similarity-value");
            if (similarityElement) {
                similarityElement.textContent = `${(personData.similarity * 100).toFixed(1)}%`;
            }

            delete existingCards[name];
        } else {
            card = createPersonCard(name, personData, isActive);
        }

        orderedCards.push({
            name,
            card,
        });
    });

    Object.values(existingCards).forEach((card) => {
        const name = card.id.replace("face-card-", "");
        if (streamConnections[name]) {
            streamConnections[name].close();
            delete streamConnections[name];
        }
        card.remove();
    });

    faceDetectionContainer.innerHTML = "";
    orderedCards.forEach(({ card }) => {
        faceDetectionContainer.appendChild(card);
    });
}

function createPersonCard(name, personData, isActive) {
    const card = document.createElement("div");
    card.className =
        "bg-white rounded-lg shadow-lg w-[400px] flex items-center";
    card.id = `face-card-${name}`;
    const imageContainer = document.createElement("div");
    imageContainer.className = "relative w-40 h-40 flex-shrink-0";
    const image = document.createElement("img");
    image.alt = name;
    image.className = "w-40 h-40 object-cover rounded-l-lg";
    const streamUrl = `http://localhost:8899/api/person_stream/${name}`;
    image.src = streamUrl;

    if (!streamConnections[name]) {
        streamConnections[name] = {
            close: () => {
                image.src = "";
            },
        };
    }

    const statusIndicator = document.createElement("div");
    statusIndicator.className = `absolute top-1 right-1 w-3 h-3 rounded-full ${isActive ? "bg-green-500" : "bg-red-500"}`;

    imageContainer.appendChild(image);
    imageContainer.appendChild(statusIndicator);

    const infoContainer = document.createElement("div");
    infoContainer.className = "p-2 flex-grow";
    infoContainer.innerHTML = `
                    <h3 class="font-bold text-black text-sm truncate">${name}</h3>
                    <p class="text-xs text-gray-600">Camera: ${personData.camera_id}</p>
                    <p class="text-xs text-gray-600">Similarity: <span class="similarity-value">${(personData.similarity * 100).toFixed(1)}%</span></p>
                    <p class="text-xs text-gray-600" id="last-seen-${name}">
                        ${getTimeAgo(personData.last_seen)}
                    </p>
                `;

    card.appendChild(imageContainer);
    card.appendChild(infoContainer);

    return card;
}

function updateActiveStatus() {
    fetch("http://localhost:8899/api/active_persons")
        .then((response) => response.json())
        .then((data) => {
            if (data.status === "success") {
                const now = Math.floor(Date.now() / 1000);
                const activePersons = data.data;
                const activePersonSet = new Set(
                    activePersons.map((person) => person.name),
                );

                activePersons.forEach((person) => {
                    const name = person.name;

                    if (trackedPersons[name]) {
                        trackedPersons[name] = {
                            ...trackedPersons[name],
                            ...person,
                            lastActiveTime: now,
                        };
                    } else {
                        trackedPersons[name] = {
                            ...person,
                            lastActiveTime: now,
                        };
                    }

                    if (removedPersons[name]) {
                        delete removedPersons[name];
                    }
                });

                Object.keys(trackedPersons).forEach((name) => {
                    if (!activePersonSet.has(name)) {
                        const timeSinceLastSeen =
                            now - trackedPersons[name].last_seen;
                        if (timeSinceLastSeen >= REMOVAL_TIMEOUT) {
                            removedPersons[name] = {
                                ...trackedPersons[name],
                                removedAt: now,
                            };
                            delete trackedPersons[name];

                            if (streamConnections[name]) {
                                streamConnections[name].close();
                                delete streamConnections[name];
                            }
                        }
                    }
                });

                renderFaceCards();
            }
        })
        .catch((error) => console.error("Error updating status:", error));
}

function cleanRemovedPersons() {
    const now = Math.floor(Date.now() / 1000);

    Object.keys(removedPersons).forEach((name) => {
        if (now - removedPersons[name].removedAt > REMOVAL_TIMEOUT * 2) {
            delete removedPersons[name];
        }
    });
}

function updateLastSeenTimes() {
    const now = Math.floor(Date.now() / 1000);

    Object.keys(trackedPersons).forEach((name) => {
        const lastSeenElement = document.getElementById(`last-seen-${name}`);
        if (lastSeenElement) {
            lastSeenElement.textContent = getTimeAgo(
                trackedPersons[name].last_seen,
            );
        }

        const timeSinceLastSeen = now - trackedPersons[name].last_seen;
        const statusIndicator = document.querySelector(
            `#face-card-${name} .rounded-full`,
        );
        if (statusIndicator) {
            const isActive = timeSinceLastSeen < REMOVAL_TIMEOUT;
            statusIndicator.className = `absolute top-1 right-1 w-3 h-3 rounded-full ${isActive ? "bg-green-500" : "bg-red-500"}`;
        }
    });
}

fetchActivePersons();
setInterval(updateActiveStatus, 1000);
setInterval(updateLastSeenTimes, 5000);
setInterval(cleanRemovedPersons, 30000);
