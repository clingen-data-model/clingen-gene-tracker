@if ($entry->deactivated_at)
    <a 
        class="btn btn-xs btn-default" 
        title="Deactivate this user." 
        href="{{\Request::root()}}/admin/user/{{$entry->getKey()}}/reactivate" 
        data-toggle="tooltip" 
        onClick="return confirm(\'Are you sure?\');"
    >
        <i class="fa fa-asterix"></i> 
        Reactviate
    </a>
@else
    <a 
        class="btn btn-xs btn-default" 
        title="Deactivate this user." 
        href="{{\Request::root()}}/admin/user/{{$entry->getKey()}}/deactivate" 
        data-toggle="tooltip" 
        onClick="return confirm(\'Are you sure?\');"
    >
        <i class="fa fa-ban"></i> 
        Deactviate
    </a>
@endif 