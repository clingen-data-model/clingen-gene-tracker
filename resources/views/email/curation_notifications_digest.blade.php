<h2>Updates from the past week.</h2>

@foreach ($groups as $type=>$group)
    @if (array_key_exists($type, $templateMap))
        @include($templateMap[$type], ['notifications'=>$group])
        <hr>
    @endif
@endforeach