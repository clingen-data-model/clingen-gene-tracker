@extends('layouts.app')

@section('content')
    <div class="container mt-2">
        <div class="card">
            <div class="card-header"><h3>{{$page->title}}</h3></div>
            <div class="card-body">
                <div id="criteria-app" class="float-right" style="width: 300px"></div>
                {{$page->content}}
            </div>
        </div>
    </div>
@endsection