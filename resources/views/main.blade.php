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