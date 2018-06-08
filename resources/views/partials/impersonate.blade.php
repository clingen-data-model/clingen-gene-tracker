@role('programmer|admin')
    @if (!\Auth::user()->isImpersonated())
    <div class="container form-inline">
        Impersonate a user:
        &nbsp;
        <select name="impersonate_id" class="form-control" onchange="location.href = '/impersonate/take/'+this.value">
            <option value="">Select user...</option>
            @foreach($impersonatable as $u)
                <option value="{{$u->id}}">{{$u->name}}</option>
            @endforeach
        </select>
    </div>
    @endif
@endrole
@impersonating
    <div class="container">
        <div class="alert alert-warning d-flex" style="align-items: center;">
            <div style="align-self: flex-start">
                You are impersonating {{ \Auth::user()->name }}
                &nbsp;
                <p class="m-0">
                   <strong>Roles:</strong> {{\Auth::user()->roles->pluck('name')->implode(', ')}}
                </p>
                <strong>ExpertPanels:</strong> 
                <ul class="m-0">
                    @foreach(\Auth::user()->expertPanels as $panel)
                    <li>
                        {{ $panel->name }}
                        {{ ($panel->pivot->is_coordinator == 1) ? '| coordinator' : '' }}
                        {{ ($panel->pivot->is_curator == 1) ? '| curator' : '' }}
                        {{ ($panel->pivot->can_edit_topics == 1) ? ', can edit all topics' : '' }}
                    </li>
                    @endforeach
                </ul>
            </div>
            <div class="ml-4 text-center" style="align-self: center; justify-self: center; flex-grow: 2;">
                <a href="/impersonate/leave" class="btn btn-secondary btn">Stop impersonating</a>
            </div>
        </div>
    </div>
@endImpersonating
</div>
