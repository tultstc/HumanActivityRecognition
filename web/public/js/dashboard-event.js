window.Echo.channel('events').listen('.create', (event) => {
    const eventsContainer = document.querySelector('.col-span-2 > div');
    if (!eventsContainer) return;

    const newEventElement = createEventElement(event);

    const firstEvent = eventsContainer.querySelector('a');
    if (firstEvent) {
        eventsContainer.insertBefore(newEventElement, firstEvent);
    } else {
        eventsContainer.appendChild(newEventElement);
    }

    const maxEvents = 3;
    const events = eventsContainer.querySelectorAll('a');
    if (events.length > maxEvents) {
        events[events.length - 1].remove();
    }
});

function createEventElement(event) {
    const wrapper = document.createElement('a');
    wrapper.href = `/events?page=1&selected=${event.event.id}`;
    wrapper.className = 'no-underline';

    const eventHtml = `
        <div class="card card-new mb-2">
            <img src="http://${window.location.hostname}:15440/image/${event.event.url}" class="card-img-top" alt="Events">
            <div class="card-body p-2">
                <div class="flex justify-between items-center mb-2">
                    <span class="text-sm font-semibold">${event.event.camera?.name || 'Deleted camera'}</span>
                    <p class="text-xs mb-0">${event.event.start_error_time}</p>
                </div>
                <div class="text-xs flex justify-between">
                    <span><b>Description: </b>Detect an object in restricted zone</span>
                </div>
            </div>
        </div>
    `;

    wrapper.innerHTML = eventHtml;
    
    requestAnimationFrame(() => {
        const card = wrapper.querySelector('.card');
        if (card) {
            card.classList.add('card-new-active');
        }
    });

    return wrapper;
}