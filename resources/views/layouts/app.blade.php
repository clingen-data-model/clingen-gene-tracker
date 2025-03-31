<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ site_title() }}</title>

    <!-- favicon -->
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="manifest" href="/site.webmanifest">
    <link rel="mask-icon" href="/safari-pinned-tab.svg" color="#284851">
    <meta name="msapplication-TileColor" content="#ffffff">
    <meta name="theme-color" content="#ffffff">    

    
    <!-- Styles -->
    <link 
        rel="preload stylesheet" 
        href="https://fonts.googleapis.com/icon?family=Material+Icons&display=swap" 
        as="style" 
        onload="this.rel = 'stylesheet'"
    >

    @vite(['resources/assets/styles/app.css', 'resources/assets/js/app.js'])

</head>
<body>
    <div>
        <main class="py-4">
            @yield('content')
        </main>

        @include('partials.impersonate');
        {{-- @include('partials.version_info'); --}}
    <!-- Scripts -->
    <script>
        window.user = {!! json_encode($user) !!}.user
        window.maxUploadSize = '{{getMaxUploadSizeForHumans()}}'
        window.supportedMimes = {!! json_encode(config('project.supported-mimes')) !!}
    </script>

    @stack('scripts')
</body>
</html>
