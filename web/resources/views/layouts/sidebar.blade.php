<div class="sidebar sidebar-narrow-unfoldable sidebar-dark sidebar-fixed border-end" id="sidebar">
    <div class="sidebar-header border-bottom">
        <div class="sidebar-brand">
            <div class="sidebar-brand-full">
                <p class="!mb-0" style="font-size: 24px">STC Video Analysis</p>
            </div>
            <img src="images/logo.png" alt="Logo" class="sidebar-brand-narrow w-8 h-8">
        </div>
        <button class="btn-close d-lg-none" type="button" data-coreui-dismiss="offcanvas" data-coreui-theme="dark"
            aria-label="Close"
            onclick="coreui.Sidebar.getInstance(document.querySelector(&quot;#sidebar&quot;)).toggle()"></button>
    </div>
    <ul class="sidebar-nav" data-coreui="navigation" data-simplebar>
        <li class="nav-item"><a class="nav-link" href="{{ route('dashboard') }}">
                <svg class="nav-icon">
                    <use xlink:href="node_modules/@coreui/icons/sprites/free.svg#cil-speedometer"></use>
                </svg>{{ __('messages.dashboard') }}</a></li>
        @can('view user')
            <li class="nav-item"><a class="nav-link" href="{{ route('users.index') }}">
                    <svg class="nav-icon">
                        <use xlink:href="node_modules/@coreui/icons/sprites/free.svg#cil-user"></use>
                    </svg>{{ __('messages.users') }}</a></li>
        @endcan
        @can('view role')
            <li class="nav-item"><a class="nav-link" href="{{ url('roles') }}">
                    <svg class="nav-icon">
                        <use xlink:href="node_modules/@coreui/icons/sprites/free.svg#cil-address-book"></use>
                    </svg>{{ __('messages.roles') }}</a></li>
        @endcan
        <li class="nav-item"><a class="nav-link" href="{{ route('groups') }}">
                <svg class="nav-icon">
                    <use xlink:href="node_modules/@coreui/icons/sprites/free.svg#cil-group"></use>
                </svg>Groups</a></li>
        @can('view camera')
            <li class="nav-item"><a class="nav-link" href="{{ route('cameras') }}">
                    <svg class="nav-icon">
                        <use xlink:href="node_modules/@coreui/icons/sprites/free.svg#cil-camera"></use>
                    </svg>Cameras</a></li>
        @endcan
        <li class="nav-item"><a class="nav-link" href="{{ route('recordings.index') }}">
                <svg class="nav-icon">
                    <use xlink:href="node_modules/@coreui/icons/sprites/free.svg#cil-airplay"></use>
                </svg>Event Recordings</a></li>
        <li class="nav-group"><a class="nav-link nav-group-toggle" href="#">
                <svg class="nav-icon">
                    <use xlink:href="node_modules/@coreui/icons/sprites/free.svg#cil-settings"></use>
                </svg> {{ __('messages.tools') }}</a>
            <ul class="nav-group-items compact">
                <li class="nav-item"><a class="nav-link" href="{{ route('rtsp.index') }}"><span class="nav-icon"><span
                                class="nav-icon-bullet"></span></span>{{ __('Record Stream') }}</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('label.video') }}"><span class="nav-icon"><span
                                class="nav-icon-bullet"></span></span> {{ __('Label Video') }}</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('train.index') }}"><span class="nav-icon"><span
                                class="nav-icon-bullet"></span></span> {{ __('messages.training') }}</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('facial-collection.index') }}"><span
                            class="nav-icon"><span class="nav-icon-bullet"></span></span>
                        {{ __('Facial Collection') }}</a></li>
            </ul>
        </li>
        @can('view event')
            <li class="nav-item"><a class="nav-link" href="{{ route('events') }}">
                    <svg class="nav-icon">
                        <use xlink:href="node_modules/@coreui/icons/sprites/free.svg#cil-bell-exclamation"></use>
                    </svg>{{ __('messages.events') }}</a></li>
        @endcan
    </ul>
    <div class="sidebar-footer border-top d-none d-md-flex">
        <button class="sidebar-toggler" type="button" data-coreui-toggle="unfoldable"></button>
    </div>
</div>
