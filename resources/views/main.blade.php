<html lang="en">
    <head>
        <!-- Required meta tags -->
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

        <link rel="stylesheet" type="text/css" href="{{mix('/css/app.css')}}">
        <meta name="csrf-token" content="{{ csrf_token() }}">
    </head>
    <body>
        <div id="app">
            <clingen-nav></clingen-nav>
            <div class="mt-2">
                <clingen-app></clingen-app>
            </div>
        </div>
        <script type="text/javascript" src="{{mix('/js/app.js')}}"></script>
    </body>
</html>