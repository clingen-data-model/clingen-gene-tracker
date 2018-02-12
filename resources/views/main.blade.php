{{-- <html lang="en">
    <head>
        <!-- Required meta tags -->
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

        <link rel="stylesheet" type="text/css" href="{{mix('/css/app.css')}}">
        <meta name="csrf-token" content="{{ csrf_token() }}">
    </head>
    <body>
            <clingen-nav></clingen-nav>
 --}}
@extends('layouts.app')
@section('content')
        <div id="app">
             <div class="mt-2">
                {{-- <clingen-nav></clingen-nav> --}}
                <alerts></alerts>
                <clingen-app></clingen-app>
            </div>
        </div>
@endsection
{{--         <script type="text/javascript" src="{{mix('/js/app.js')}}"></script>
    </body>
</html> --}}