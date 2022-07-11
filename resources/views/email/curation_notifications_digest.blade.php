<h2>Updates from the past week.</h2>

@foreach ($groups as $type=>$group)
    @include($type::getDigestTemplate(), ['notifications'=>$group])
    <hr>
@endforeach