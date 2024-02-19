<!-- expert panel -->
@include('crud::fields.inc.wrapper_start')
    <label>{!! $field['label'] !!}</label>
    @include('crud::fields.inc.translatable_icon')
    <div id="expert-panel-field">
        @php 
            $prepopValue = (old($field['name'])) 
                                ? old($field['value']) 
                                : ($field['value']) ?? '[]';
        @endphp
        <expert-panel-field 
            :connected-panels="{{$prepopValue}}" 
            :panel-options="{{$field['model']::all()}}"
        ></expert-panel-field>
    </div>
    @if(isset($field['select_all']) && $field['select_all'])
        <a class="btn btn-xs btn-default select_all" style="margin-top: 5px;">
            <i class="la la-check-square-o"></i> {{ trans('backpack::crud.select_all') }}</a>
        <a class="btn btn-xs btn-default clear" style="margin-top: 5px;"><i class="la la-times">x</i> {{ trans('backpack::crud.clear') }}X</a>
    @endif

    {{-- HINT --}}
    @if (isset($field['hint']))
        <p class="help-block">{!! $field['hint'] !!}</p>
    @endif
    
@include('crud::fields.inc.wrapper_end')

@if ($crud->checkIfFieldIsFirstOfItsType($field, $fields))
    {{-- FIELD JS - will be loaded in the after_scripts section --}}
    @push('crud_fields_scripts')
        <script>
            let user = null;
        </script>
        @vite(['resources/assets/js/app.js'])
    @endpush

@endif