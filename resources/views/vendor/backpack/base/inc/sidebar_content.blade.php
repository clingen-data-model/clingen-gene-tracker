<li class="nav-item">
    <a class="nav-link" href="{{ backpack_url('dashboard') }}">
        <i class="nav-icon la la-dashboard"></i> <span>{{ trans('backpack::base.dashboard') }}</span>
    </a>
</li>

@if(Auth::user()->hasPermissionTo('list users'))
    <li class="nav-item">
        <a class="nav-link" href="{{ backpack_url('user') }}">
            <i class="nav-icon la la-user"></i> <span>Users</span>
        </a>
    </li>
@endif

@if(Auth::user()->hasPermissionTo('list expert-panels'))
    <li class="nav-item">
        <a class="nav-link" href="{{ backpack_url('expert-panel') }}">
            <i class="nav-icon la la-users"></i> <span>Expert Panels</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="{{ backpack_url('aff') }}">
            <i class="nav-icon la la-users"></i> <span>Affiliations</span>
        </a>
    </li>
@endif

@if(Auth::user()->hasPermissionTo('list working-groups'))
    <li class="nav-item">
        <a class="nav-link" href="{{ backpack_url('working-group') }}">
            <i class="nav-icon la la-tasks"></i> <span>Working Groups</span>
        </a>
    </li>
@endif

@if(Auth::user()->hasPermissionTo('list curation-types'))
    <li class="nav-item">
        <a class="nav-link" href="{{ backpack_url('curation-type') }}">
            <i class="nav-icon la la-star"></i> <span>Curation Types</span>
        </a>
    </li>
@endif

@if(Auth::user()->hasPermissionTo('list rationales'))
    <li class="nav-item">
        <a class="nav-link" href="{{ url(config('backpack.base.route_prefix').'/rationale') }}">
            <i class="nav-icon la la-file-o"></i> <span>Rationales</span>
        </a>
    </li>
@endif

@if(Auth::user()->hasPermissionTo('list mois'))
    <li class="nav-item">
        <a class="nav-link" href="{{ url(config('backpack.base.route_prefix').'/moi') }}">
            <i class="nav-icon la la-file-o"></i> <span>MOIs</span>
        </a>
    </li>
@endif

@if(Auth::user()->hasAnyRole(['programmer', 'admin'])) 
    <li class="nav-item">
        <a class="nav-link" href="{{ url(config('backpack.base.route_prefix').'/upload-category') }}">
            <i class="nav-icon la la-file-o"></i> <span>Upload Categories</span>
        </a>
    </li>
@endif

{{-- @if(Auth::user()->hasPermissionTo('view email')) --}}
    <li class="nav-item">
        <a class="nav-link" href="{{ url(config('backpack.base.route_prefix').'/email') }}">
            <i class="nav-icon la la-file-o"></i> <span>Emails</span>
        </a>
    </li>
{{-- @endif --}}
{{-- @if(Auth::user()->hasPermissionTo('view notification')) --}}
    <li class="nav-item">
        <a class="nav-link" href="{{ url(config('backpack.base.route_prefix').'/notification') }}">
            <i class="nav-icon la la-file-o"></i> <span>Notifications</span>
        </a>
    </li>
{{-- @endif --}}
{{-- @if(Auth::user()->hasPermissionTo('view logs')) --}}
<li class="nav-item">
    <a class="nav-link" href="/logs" target="logs">
        <i class="nav-icon la la-file-o"></i> <span>Logs</span>
    </a>
</li>
{{-- @endif --}}
