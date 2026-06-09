@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 text-center mt-5">
                <h1>403</h1>
                <h2>{{ $name ?? 'Forbidden' }}</h2>
                <p>You are not authorized to access this page.</p>
                <a href="{{ url('/') }}" class="btn btn-primary">Return home</a>
            </div>
        </div>
    </div>
@endsection