@extends('layouts.app')

@section('content')
    <div id="app" class="container mt-2">
        <div class="card">
            <div class="card-header"><h3>{{$page->title}}</h3></div>
            <div class="card-body">
                <div class="float-end" style="width: 300px"><criteria-table></criteria-table></div>
                {{$page->content}}
            </div>
        </div>
    </div>
@endsection