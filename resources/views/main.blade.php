@extends('layouts.app')
@section('content')
        <div id="app">
            <div class="mt-2">
                <alerts></alerts>
                <clingen-app></clingen-app>
                <b-progress 
                    :value="100" 
                    :max="100"  
                    animated 
                    v-show="loading"
                    style="position:fixed; top:0; left:0; right:0; border-radius: 0"
                    height="5px"
                >
                </b-progress>
            </div>
        </div>
@endsection