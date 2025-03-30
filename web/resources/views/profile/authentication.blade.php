@extends('layouts.app')

@section('content')
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Authentication') }}
        </h2>
    </x-slot>

    <div class="container">
        <div class="mt-10 sm:mt-0">
            @livewire('profile.logout-other-browser-sessions-form')
        </div>
        @if (Laravel\Fortify\Features::canManageTwoFactorAuthentication())
            <div class="mt-10 sm:mt-0">
                @livewire('profile.two-factor-authentication-form')
            </div>
        @endif
    </div>
@endsection

@section('subHeader')
    <div class="flex items-center gap-4">
        <a class="no-underline text-[#6A6E76]" href="{{ route('profile.show') }}">Profile Information</a>
        <a class="no-underline text-[#6A6E76]" href="{{ route('profile.authentication') }}">Authentication</a>
    </div>
@endsection
