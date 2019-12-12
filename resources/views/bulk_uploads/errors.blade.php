@if (isset($errors) && count($errors) > 0)
    <div class="alert alert-danger">
        There are problems with your data:
        <ul>
        @if ($fileErrors = $errors->pop())
            <li>
                @foreach ($fileErrors as $message)
                    <strong>File:</strong> {{$message}}
                @endforeach
            </li>
        @endif
        @foreach ($errors as $idx => $row)
            <li>
                <strong> Row {{($idx)}} - </strong>
                @foreach ($row as $field => $message)
                    <strong>{{$field}}:</strong> {{$message}}
                @endforeach
            </li>
        @endforeach
        </ul>
    </div>
@endif
