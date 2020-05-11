<h2>Updates from the past week.</h2>

<ul>
@foreach ($notifications as $notification)
    @if (isset($notification->data['template']))
        @include($notification->data['template'], $notification['data'])
    @else
        <li>Notification: {{ $notifications }}</li>
    @endif
@endforeach
</ul>