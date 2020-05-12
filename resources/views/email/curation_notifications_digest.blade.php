<h2>Updates from the past week.</h2>

<ul>
@foreach ($notifications as $notification)
    @if (isset($notification->data['template']))
        <li>
            @include($notification->data['template'], $notification['data'])
            <br>({{$notification->created_at->format('Y-m-d h:i a')}})
        </li>
    @else
        <li>Notification: {{ $notifications }}</li>
    @endif
@endforeach
</ul>