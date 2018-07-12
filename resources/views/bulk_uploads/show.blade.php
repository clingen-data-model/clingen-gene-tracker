@extends('layouts.app')

@section('content')
<div class="container">
    <div class="card w-75">
        <div class="card-header">
            <h2>Curations: Bulk Upload</h2>
        </div>
        <div class="card-body">            
            @if (isset($errors) && count($errors) > 0)
                <div class="alert alert-danger">
                    There are problems with your data:
                    <ul>
                    @foreach ($errors as $idx => $row)
                        <li>
                            <strong> Row {{($idx+1)}} - </strong>
                            @foreach ($row as $field => $message)
                                <strong>{{$field}}:</strong> {{$message}}
                            @endforeach
                        </li>
                    @endforeach
                    </ul>
                </div>
            @endif

            @if (isset($newCurations) && $newCurations->count() > 0)
                <div class="alert alert-info">
                    Created {{$newCurations->count()}} new curations for {{$newCurations->first()->expertPanel->name}}
                    <ul>
                    @foreach ($newCurations as $curation)
                        <li>
                            <a href="/#/curations/{{$curation->id}}">{{$curation->gene_symbol}}</a>
                        </li>
                    @endforeach
                    </ul>
                </div>
            @endif
    
            <div class="d-flex justify-content-between">

                <form action="{{route('bulk-uploads.upload')}}" method="POST" enctype="multipart/form-data" style="width: 70%">
                    {{csrf_field()}}
                    <div class="form-group form-inline">
                        <label for="expert_panel_id">Expert Panel:</label>
                        &nbsp;
                        <select name="expert_panel_id" id="expert_panel_id" class="form-control">
                            @foreach (\Auth::user()->expertPanels->where('pivot.is_coordinator', 1) as $panel)
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