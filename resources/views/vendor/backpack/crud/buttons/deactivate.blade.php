@if ($entry->deactivated_at)
    <a 
        class="btn btn-sm btn-link" 
        title="Deactivate this user." 
        href="{{\Request::root()}}/admin/user/{{$entry->getKey()}}/reactivate" 
        data-toggle="tooltip" 
        onClick="return confirm(\'Are you sure?\');"
    >
        <i class="la la-asterix"></i> 
        Reactviate
    </a>
@else
    <a 
        class="btn btn-sm btn-link" 
        title="Deactivate this user." 
        href="{{\Request::root()}}/admin/user/{{$entry->getKey()}}/deactivate" 
        data-toggle="tooltip" 
        onClick="return confirm(\'Are you sure?\');"
    >
        <i class="la la-ban"></i> 
        Deactviate
    </a>
@endif 