<div class="coordinates !hidden">
    <span class="cc-title">Coordinates</span>
    <span>
        <span class="cc-coord">x: <span class="cc-value" id="x">---</span> | </span>
        <span class="cc-coord">y: <span class="cc-value" id="y">---</span></span>
    </span>
</div>
<div class="image-container mb-4 mt-2">
    <canvas id="canvas" class="m-auto"></canvas>
</div>
<div class="grid grid-cols-6 gap-3 border rounded-md">
    <div class="mode-button mode-toggle flex flex-col items-center justify-center p-2 rounded-md transition-all duration-200 ease-in-out cursor-pointer hover:bg-gray-200 active:bg-gray-300  hover:text-gray-900"
        id="mode-polygon">
        <i class="fa-solid fa-draw-polygon text-xl mb-1"></i>
        <span class="flex flex-col items-center text-nd">
            <span>{{ __('messages.polygon') }}</span>
            <span class=" hover:text-gray-900 text-[14px] mt-1">(P)</span>
        </span>
    </div>
    <div class="mode-button mode-toggle flex flex-col items-center justify-center p-2 rounded-md transition-all duration-200 ease-in-out cursor-pointer hover:bg-gray-200 active:bg-gray-300  hover:text-gray-900"
        id="mode-line">
        <i class="fa-solid fa-lines-leaning text-xl mb-1"></i>
        <span class="flex flex-col items-center text-md">
            <span>{{ __('messages.line') }}</span>
            <span class=" hover:text-gray-900 text-[14px] mt-1">(L)</span>
        </span>
    </div>
    <div class="mode-button mode-toggle flex flex-col items-center justify-center p-2 rounded-md transition-all duration-200 ease-in-out cursor-pointer hover:bg-gray-200 active:bg-gray-300  hover:text-gray-900"
        id="mode-edit">
        <i class="fa-solid fa-up-down-left-right text-xl mb-1"></i>
        <span class="flex flex-col items-center text-md">
            <span>{{ __('messages.edit_mode') }}</span>
            <span class=" hover:text-gray-900 text-[14px] mt-1">(E)</span>
        </span>
    </div>
    <div class="mode-button flex flex-col items-center justify-center p-2 rounded-md transition-all duration-200 ease-in-out cursor-pointer hover:bg-gray-200 active:bg-gray-300  hover:text-gray-900"
        id="undo">
        <i class="fa-solid fa-rotate-left text-xl mb-1"></i>
        <span class="flex flex-col items-center text-md">
            <span>{{ __('messages.undo') }}</span>
            <span class=" hover:text-gray-900 text-[14px] mt-1">(Ctrl/⌘-Z)</span>
        </span>
    </div>
    <div class="mode-button flex flex-col items-center justify-center p-2 rounded-md transition-all duration-200 ease-in-out cursor-pointer hover:bg-gray-200 active:bg-gray-300  hover:text-gray-900"
        id="discard-current">
        <i class="fa-solid fa-xmark text-xl mb-1"></i>
        <span class="flex flex-col items-center text-md">
            <span>{{ __('messages.discard') }}</span>
            <span class=" hover:text-gray-900 text-[14px] mt-1">(Esc)</span>
        </span>
    </div>
    <div class="mode-button flex flex-col items-center justify-center p-2 rounded-md transition-all duration-200 ease-in-out cursor-pointer hover:bg-gray-200 active:bg-gray-300  hover:text-gray-900"
        id="clear">
        <i class="fa-solid fa-trash text-xl mb-1"></i>
        <span class="flex flex-col items-center text-md">
            <span>{{ __('messages.clear') }}</span>
            <span class=" hover:text-gray-900 text-[14px] mt-1">(Ctrl/⌘-E)</span>
        </span>
    </div>
</div>
<script>
    document.querySelectorAll('.mode-toggle').forEach(button => {
        button.addEventListener('click', function() {
            document.querySelectorAll('.mode-toggle').forEach(btn => {
                btn.classList.remove('bg-gray-400', 'text-black');
            });
            this.classList.remove('text-gray-700');
            this.classList.add('bg-gray-400', 'text-black');
        });
    });

    document.querySelectorAll('.mode-button:not(.mode-toggle)').forEach(button => {
        button.addEventListener('click', function() {
            this.classList.add('bg-gray-400', 'text-black');
            setTimeout(() => {
                this.classList.remove('bg-gray-400', 'text-black');
            }, 50);
        });
    });

    document.addEventListener('DOMContentLoaded', function() {
        const polygonButton = document.getElementById('mode-polygon');
        if (polygonButton) {
            polygonButton.click();
        }
    });
</script>
