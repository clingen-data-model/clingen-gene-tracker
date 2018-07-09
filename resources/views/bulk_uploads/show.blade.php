@extends('layouts.app')

@section('content')
<div class="container">
    <div class="card w-75">
        <div class="card-header">
            <h2>Bulk upload curations</h2>
        </div>
        <div class="card-body">
            <p>
                <a href="/files/bulk_curation_template.xlsx" class="btn btn-primary btn-sm">
                    Download Template
                </a>
            </p>
            
            <form action="{{route('bulk-uploads.upload')}}" method="POST" enctype="multipart/form-data">
                {{csrf_field()}}
                <div class="form-group form-inline">
                    <label for="upload-field">Upload File:</label>
                    &nbsp;
                    <input type="file" name="bulk_curations" id="upload-field" class="form-control" />
                    &nbsp;
                    <button class="btn btn-primary" type="submit">Upload</button>
                </div>
            </form>

        </div>
    </div>
</div>
@endsection