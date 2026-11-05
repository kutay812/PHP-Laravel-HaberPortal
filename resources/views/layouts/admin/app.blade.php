<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Yönetim Paneli') - Gece Haber Admin</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">

    <!-- Custom fonts -->
    <link href="{{ asset('storage/adminlte/plugins/fontawesome-free/css/all.min.css') }}" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:400,600,700,800&display=swap" rel="stylesheet">

    <!-- Custom styles -->
    <link href="{{ asset('storage/adminlte/css/adminlte.min.css') }}" rel="stylesheet">
    <link href="{{ asset('css/admin-autocomplete.css') }}" rel="stylesheet">
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2c3e50;
            --success-color: #2ecc71;
            --danger-color: #e74c3c;
            --warning-color: #f1c40f;
            --info-color: #3498db;
            --light-color: #ecf0f1;
            --dark-color: #2c3e50;
        }

        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            background-color: #f5f6fa;
            font-family: 'Nunito', sans-serif;
            color: var(--dark-color);
        }

        #wrapper {
            display: flex;
            flex: 1;
            background-color: #f5f6fa;
        }

        #content-wrapper {
            flex: 1;
            display: flex;
            flex-direction: column;
            background-color: #f5f6fa;
            overflow-x: hidden;
        }

        #content {
            flex: 1;
            padding: 1.5rem;
        }

        .card {
            border: none;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0,0,0,0.05);
            margin-bottom: 1.5rem;
            background: #fff;
        }

        .card-header {
            background: #fff;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            padding: 1.2rem 1.5rem;
            border-radius: 8px 8px 0 0 !important;
        }

        .card-header h6 {
            margin: 0;
            color: var(--dark-color);
            font-weight: 600;
        }

        .card-body {
            padding: 1.5rem;
        }

        .form-control, .form-select {
            border-radius: 6px;
            border: 1px solid rgba(0,0,0,0.12);
            padding: 0.55rem 0.9rem;
            transition: all 0.2s ease;
            width: 100%;
            background-color: #fff;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(52,152,219,0.15);
            outline: none;
        }

        .form-label {
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 0.5rem;
        }

        .btn {
            border-radius: 6px;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .btn-xs {
            padding: 0.25rem 0.55rem;
            font-size: 0.78rem;
        }

        .gap-2 { gap: 0.5rem; }
        .me-1 { margin-right: 0.25rem; }
        .me-2 { margin-right: 0.5rem; }

        .btn-primary {
            background: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background: #2980b9;
            border-color: #2980b9;
        }

        .table {
            margin-bottom: 0;
        }

        .table th {
            border-top: none;
            font-weight: 600;
            padding: 0.9rem 1rem;
            background: rgba(0,0,0,0.02);
            color: var(--dark-color);
        }

        .table td {
            padding: 0.9rem 1rem;
            vertical-align: middle;
            border-color: rgba(0,0,0,0.05);
        }

        .alert {
            border: none;
            border-radius: 8px;
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
        }

        .alert-success {
            background: rgba(46,204,113,0.14);
            color: #1e8449;
        }

        .alert-danger {
            background: rgba(231,76,60,0.14);
            color: #922b21;
        }

        .badge {
            padding: 0.45em 0.85em;
            border-radius: 30px;
            font-weight: 600;
            font-size: 0.75rem;
        }

        .badge-primary {
            background: rgba(52,152,219,0.15);
            color: var(--primary-color);
        }

        .badge-success {
            background: rgba(46,204,113,0.15);
            color: #1e8449;
        }

        .badge-danger {
            background: rgba(231,76,60,0.15);
            color: #c0392b;
        }

        .scroll-to-top {
            position: fixed;
            right: 1rem;
            bottom: 1rem;
            display: none;
            width: 2.75rem;
            height: 2.75rem;
            text-align: center;
            color: #fff;
            background: var(--primary-color);
            border-radius: 8px;
            line-height: 46px;
            transition: all 0.3s ease;
            opacity: 0.85;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            z-index: 1030;
        }

        .scroll-to-top:hover {
            opacity: 1;
            color: #fff;
        }

        @media (max-width: 768px) {
            #content {
                padding: 1rem;
            }
        }
    </style>
    @stack('styles')
</head>

<body id="page-top">
    @auth
    <div id="wrapper">
        @include('layouts.admin.sidebar')
        <div id="content-wrapper">
            <div id="content">
                @yield('content')
            </div>
        </div>
    </div>

    <a class="scroll-to-top" href="#page-top" aria-label="Yukarı Çık">
        <i class="fas fa-chevron-up"></i>
    </a>
    @else
        @yield('content')
    @endauth

    <!-- Core JavaScript -->
    <script src="{{ asset('storage/adminlte/plugins/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('storage/adminlte/plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('storage/adminlte/js/adminlte.min.js') }}"></script>

    <script>
        $(window).on('scroll', function() {
            if ($(this).scrollTop() > 100) {
                $('.scroll-to-top').fadeIn();
            } else {
                $('.scroll-to-top').fadeOut();
            }
        });

        $('.scroll-to-top').on('click', function(e) {
            e.preventDefault();
            $('html, body').animate({scrollTop: 0}, 400, 'swing');
        });

        // Sadece başarı bildirimlerini 5 saniye sonra otomatik kapat (hata ve bilgi kutularını silme)
        window.setTimeout(function() {
            $('.alert-success.alert-dismissible').fadeTo(500, 0).slideUp(500, function() {
                $(this).remove();
            });
        }, 5000);
    </script>

    <script src="{{ asset('js/admin-autocomplete.js') }}"></script>
    @stack('scripts')
</body>
</html>
