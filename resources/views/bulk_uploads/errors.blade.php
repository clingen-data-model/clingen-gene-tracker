@if (isset($errors) && count($errors) > 0)
    <div class="alert alert-danger">
        There are problems with your data:
        <ul>
        @if ($missing_panel_id = $errors->get('expert_panel_id'))
            <li>
                <strong>Need to select a valid Expert Panel</strong>
            </li>
        @endif
        @if ($fileErrors = $errors->get('file'))
            <li>
                @foreach ($fileErrors as $message)
                    <strong>File:</strong> {{$message}}
                @endforeach
            </li>
        @endif
        @foreach ($errors as $idx => $row)
            @if ($idx != 'file')
                <li>
                    <strong> Row {{($idx)}} - </strong>
                    @foreach ($row as $field => $message)
                        <strong>{{$field}}:</strong> {{$message}}
                    @endforeach
                </li>
            @endif
        @endforeach
        </ul>
    </div>
@endif
