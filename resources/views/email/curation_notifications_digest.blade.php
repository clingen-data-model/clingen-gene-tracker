<h2>Updates from the past week.</h2>

@foreach($groups as $class => $group)
  @include($class::getDigestTemplate(), ['group' => $group, 'user' => $user ?? null])
@endforeach