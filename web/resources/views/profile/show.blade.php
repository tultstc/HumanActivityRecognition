@extends('layouts.app')

@section('content')
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Profile') }}
        </h2>
    </x-slot>

    <div>
        <div class="max-w-7xl mx-auto">
            @if (Laravel\Fortify\Features::canUpdateProfileInformation())
                @livewire('profile.update-profile-information-form')
            @endif
        </div>
    </div>
@endsection

@section('subHeader')
    <div class="flex items-center gap-4">
        <a class="no-underline text-[#6A6E76]" href="{{ route('profile.show') }}">Profile Information</a>
        <a class="no-underline text-[#6A6E76]" href="{{ route('profile.authentication') }}">Authentication</a>
    </div>
@endsection
