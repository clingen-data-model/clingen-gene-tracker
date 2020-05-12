<span>
    @foreach ($entry->{$column['name']} as $email => $name)
        @if ($name)
            "{{$name}}" &lt;{{$email}}&gt;@if (!$loop->last),<br>@endif
        @else
            {{$email}}@if (!$loop->last),<br>@endif
        @endif
    @endforeach
</span>