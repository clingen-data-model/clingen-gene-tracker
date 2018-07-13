@if (isset($errors) && count($errors) > 0)
    <div class="alert alert-danger">
        There are problems with your data:
        <ul>
        @foreach ($errors as $idx => $row)
            <li>
                <strong> Row {{($idx+1)}} - </strong>
                @foreach ($row as $field => $message)
                    <strong>{{$field}}:</strong> {{$message}}
                @endforeach
            </li>
        @endforeach
        </ul>
    </div>
@endif
