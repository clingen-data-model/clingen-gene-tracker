@php
$operationUrl = \Request::root().'/admin/api-client/'.$entry->getKey().'/create-token'
@endphp
<a
    class="btn btn-sm btn-link" 
    title="Deactivate this user." 
    href="{{$operationUrl}}"
>
    Create access token
</a>
