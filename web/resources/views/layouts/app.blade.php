@props(['add_class' => 'mt-4 container-fluid'])

<!DOCTYPE html>
<html lang="en">

<head>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <base href="/">
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <meta name="description" content="CoreUI - Open Source Bootstrap Admin Template">
    <meta name="author" content="STC">
    <meta name="keyword" content="Bootstrap,Admin,Template,Open,Source,jQuery,CSS,HTML,RWD,Dashboard">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'STC Video Analysis')</title>

    <!-- ... (các link favicon khác) ... -->
    <meta name="msapplication-TileColor" content="#ffffff">
    <meta name="theme-color" content="#ffffff">

    <!-- Vendors styles-->
    <link rel="stylesheet" href="node_modules/simplebar/dist/simplebar.css">
    <link rel="stylesheet" href="css/vendors/simplebar.css">
    <!-- Main styles for this application-->
    <link href="css/style.css" rel="stylesheet">

    <link href="css/examples.css" rel="stylesheet">
    <link href="node_modules/@coreui/chartjs/dist/css/coreui-chartjs.css" rel="stylesheet">
    <script src="js/config.js"></script>
    <script src="js/color-modes.js"></script>

    <link rel="stylesheet" href="css/all.min.css">
    <link href="node_modules/gridstack/dist/gridstack.min.css" rel="stylesheet" />

    {{-- Datatable --}}
    <link href="css/datatables.min.css" rel="stylesheet">
    <link href="css/select2.min.css" rel="stylesheet" />
    <script src="js/jquery.min.js"></script>
    {{-- <script src="https://code.jquery.com/jquery-3.7.1.min.js"
        integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script> --}}
    <link rel="stylesheet" href="css/flatpickr.min.css">
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
    @yield('links')
    @livewireStyles

</head>

<body>

    <!-- ======= Sidebar ======= -->
    @include('layouts.sidebar')

    <div class="wrapper d-flex flex-column h-screen min-vh-100">
        <!-- ======= Header ======= -->
        @include('layouts.header')

        <!-- ======= Content ======= -->
        <main id="main" class="body flex-grow-1 {{ $add_class }}">
            @yield('content')
        </main>

        <!-- ======= Footer ======= -->
        @include('layouts.footer')
    </div>
    @livewireScripts

    <!-- CoreUI and necessary plugins-->
    <script src="node_modules/@coreui/coreui/dist/js/coreui.bundle.min.js"></script>
    <script src="node_modules/simplebar/dist/simplebar.min.js"></script>
    <script>
        const header = document.querySelector('header.header');
        document.addEventListener('scroll', () => {
            if (header) {
                header.classList.toggle('shadow-sm', document.documentElement.scrollTop > 0);
            }
        });
    </script>
    <!-- Plugins and scripts required by this view-->
    <script src="node_modules/chart.js/dist/chart.umd.js"></script>
    <script src="node_modules/@coreui/chartjs/dist/js/coreui-chartjs.js"></script>
    <script src="node_modules/@coreui/utils/dist/umd/index.js"></script>
    <script src="js/main.js"></script>

    <script defer src="js/alpinejs.min.js"></script>

    {{-- Datatable --}}
    <script src="js/datatable.js"></script>
    <script src="js/pdfmake.min.js"></script>
    <script src="js/vfs_fonts.js"></script>
    <script src="js/datatables.min.js"></script>
    <script src="js/flatpickr.min.js"></script>

    <script src="js/sweetalert.min.js"></script>

    {{-- Require input --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const requiredFields = document.querySelectorAll(
                'input[required], textarea[required], select[required]');

            requiredFields.forEach(function(field) {
                const label = field.previousElementSibling;
                if (label && label.tagName === 'LABEL') {
                    label.innerHTML += '<span style="color: red;"> *</span>';
                }
            });
        });
    </script>
    @yield('script')
</body>

</html>
