<!-- expert panel -->
<div @include('crud::inc.field_wrapper_attributes') >
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
        <a class="btn btn-xs btn-default select_all" style="margin-top: 5px;"><i class="la la-check-square-o"></i> {{ trans('backpack::crud.select_all') }}</a>
        <a class="btn btn-xs btn-default clear" style="margin-top: 5px;"><i class="la la-times"></i> {{ trans('backpack::crud.clear') }}</a>
    @endif

    {{-- HINT --}}
    @if (isset($field['hint']))
        <p class="help-block">{!! $field['hint'] !!}</p>
    @endif
</div>


{{-- ########################################## --}}
{{-- Extra CSS and JS for this particular field --}}
{{-- If a field type is shown multiple times on a form, the CSS and JS will only be loaded once --}}
@if ($crud->checkIfFieldIsFirstOfItsType($field, $fields))

    {{-- FIELD CSS - will be loaded in the after_styles section --}}
    @push('crud_fields_styles')
    @endpush

    {{-- FIELD JS - will be loaded in the after_scripts section --}}
    @push('crud_fields_scripts')
        <script>
            let user = null;
        </script>
        <script src="{{ mix('js/app.js') }}"></script>
    @endpush

@endif
{{-- End of Extra CSS and JS --}}
{{-- ########################################## --}}
