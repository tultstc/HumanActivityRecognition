@props(['sort', 'search'])

{{-- Add Cam --}}
<div class="mb-6">
    <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('messages.create') }}</label>
    <a href="{{ route('cameras.create') }}" class="btn btn-primary w-full">{{ __('messages.add_camera') }}</a>
</div>

<!-- Search -->
<div class="mb-6">
    <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('messages.search') }}</label>
    <input type="text" wire:model.live="search" placeholder="{{ __('messages.search') }} cameras..."
        class="form-control">
</div>

<!-- Group Sort -->
<div class="mb-6">
    <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('Group Sort') }}</label>
    <select wire:model.live="group" class="form-control">
        <option value="" selected disabled>Select a group</option>
        @foreach ($this->groups as $group)
            <option value="{{ $group->id }}">{{ $group->name }}</option>
        @endforeach
    </select>
</div>

<!-- Sort Option -->
<div class="mb-6">
    <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('messages.sort_by') }}</label>
    <div class="grid grid-cols-2 gap-2">
        <button
            class="{{ $sort === 'name-asc' ? 'bg-indigo-100 text-indigo-700' : 'text-gray-500' }} px-3 py-2 rounded-md text-sm font-medium"
            wire:click="setSort('name-asc')">{{ __('messages.name') }} (A-Z)</button>
        <button
            class="{{ $sort === 'name-desc' ? 'bg-indigo-100 text-indigo-700' : 'text-gray-500' }} px-3 py-2 rounded-md text-sm font-medium"
            wire:click="setSort('name-desc')">{{ __('messages.name') }} (Z-A)</button>
        <button
            class="{{ $sort === 'desc' ? 'bg-indigo-100 text-indigo-700' : 'text-gray-500' }} px-3 py-2 rounded-md text-sm font-medium"
            wire:click="setSort('desc')">{{ __('messages.newest') }}</button>
        <button
            class="{{ $sort === 'asc' ? 'bg-indigo-100 text-indigo-700' : 'text-gray-500' }} px-3 py-2 rounded-md text-sm font-medium"
            wire:click="setSort('asc')">{{ __('messages.oldest') }}</button>
    </div>
</div>


<!-- Clear Filters -->
@if ($search || $sort)
    <button wire:click="clearFilters()" class="btn btn-primary w-full">
        {{ __('messages.clear_filters') }}
    </button>
@endif
