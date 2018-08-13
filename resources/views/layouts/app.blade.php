<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ site_title() }}</title>

    <!-- Styles -->
    <link href="{{ mix('css/app.css') }}" rel="stylesheet">

</head>
<body>
    <div>
        <nav class="navbar navbar-default navbar-expand-md navbar-light navbar-laravel {{ config('app.env') }}">
            <div class="container">
                <a class="navbar-brand" href="/#/">
                    {{-- {{ site_title() }} --}}
                    <img src="/images/clingen_logo_75w.png"></img>
                </a>
                <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <!-- Left Side Of Navbar -->
                    @if (!Auth::guest())
                    <ul class="navbar-nav mr-auto">
                        <li>
                            <a class="nav-link" href="/#/">Dashboard</a>
                        </li>
                        <li>
                            <a class="nav-link" href="/#/curations">Curations</a>
                        </li>
                        <li>
                            <a class="nav-link" href="/#/working-groups">Working Groups</a>
                        </li>
                    </ul>
                    @endif

                    <!-- Right Side Of Navbar -->
                    <ul class="navbar-nav ml-auto">
                        <!-- Authentication Links -->
                        @guest
                            <li><a class="nav-link" href="{{ route('login') }}">Login</a></li>
                        @else
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    {{ Auth::user()->name }} <span class="caret"></span>
                                </a>
                                <div class="dropdown-menu" aria-labelledby="navbarDropdown">
                                    @if (\Auth::user()->hasAnyRole('programmer|admin') || \Auth::user()->isCoordinator())
                                        <a class="dropdown-item" href="/bulk-uploads">Bulk Upload</a>
                                        <div class="dropdown-divider"></div>
                                    @endif 
                                    @role('programmer|admin')
                                        <a href="{{ route('backpack') }}" class="dropdown-item">Admin</a>
                                        @role('programmer')
                                            <a class="dropdown-item" href="{{ route('logs') }}" target="logs">Logs</a>
                                        @endrole
                                        <div class="dropdown-divider"></div>
                                    @endrole

                                    <a class="dropdown-item" href="{{ route('logout') }}"
                                       onclick="event.preventDefault();
                                                     document.getElementById('logout-form').submit();">
                                        Logout
                                    </a>

                                    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                                        @csrf
                                    </form>
                                </div>
                            </li>
                            @include('partials.help')
                         @endguest
                    </ul>
                </div>
            </div>
        </nav>

        <main class="py-4">
            @yield('content')
        </main>

        @include('partials.impersonate');
    <!-- Scripts -->
    <script>
        let user = {!! json_encode($user) !!}.user
    </script>
    <script src="{{ mix('js/app.js') }}"></script>
</body>
</html>
