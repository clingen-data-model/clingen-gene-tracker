<div>
    @foreach ($entry->{$column['name']} as $email => $name)
        @if ($name)
            {{$name}}<br><em>{{$email}}</em>@if (!$loop->last),<br><br>@endif
        @else
            <em>{{$email}}</em>@if (!$loop->last),<br><br>@endif
        @endif
    @endforeach
</div>