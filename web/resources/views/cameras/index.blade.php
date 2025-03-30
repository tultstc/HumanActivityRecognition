@extends('layouts.app')
@section('title', 'Cameras')

@section('content')
    @if (session()->has('status'))
        <div class="alert alert-success mb-1">
            {{ session()->get('status') }}
        </div>
    @endif
    <livewire:camera-list />
    <script>
        setTimeout(function() {
            $('.alert').fadeOut('slow');
        }, 3000);
    </script>
@endsection
