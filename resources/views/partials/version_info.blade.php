@if (\Auth::user() && \Auth::user()->hasRole('programmer')) 
    <div class="container border-top mt-3 pt-1">
        <strong>Release info:</strong>
        <small>
            <strong>branch:</strong> {{$gitInfo['branch']}}
            <span class="text-muted">|</span>
            <strong>commit:</strong> {{$gitInfo['commit']}}
            <span class="text-muted">|</span>
            <strong>release:</strong> {{$releaseNumber}}
        </small>
    </div>
@endif