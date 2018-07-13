@extends('layouts.app')

@section('content')
<div class="container">
    <div class="card w-75">
        <div class="card-header">
            <h2>Curations: Bulk Upload</h2>
        </div>
        <div class="card-body">
            @include('bulk_uploads.errors')
            @include('bulk_uploads.new_curations')
    
            <div class="d-flex justify-content-between">

                <form action="{{route('bulk-uploads.upload')}}" method="POST" enctype="multipart/form-data" style="width: 70%">
                    {{csrf_field()}}
                    <div class="form-group form-inline">
                        <label for="expert_panel_id">Expert Panel:</label>
                        &nbsp;
                        <select name="expert_panel_id" id="expert_panel_id" class="form-control">
                            @foreach (\Auth::user()->getPanelsCoordinating() as $panel)
                                <option value="{{$panel->id}}">{{$panel->name}}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group form-inline">
                        <label for="upload-field">Upload File:</label>
                        &nbsp;
                        <input type="file" name="bulk_curations" id="upload-field" class="form-control" />
                    </div>
                    <div class="alert alert-warning pt-1 pb-1 pl-2 pr-2">
                        <small>Please note that uploads are for curations in a single expert Panel</small>
                    </div>
                    <button class="btn btn-primary" type="submit">Upload</button>
                </form>

                <div class="w-25">
                    <div class="alert alert-info">
                        <p>Please download the fill out the excel template for best results.</p>
                        <a href="/files/bulk_curation_template.xlsx" class="btn btn-info form-control">
                            Download Template
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection