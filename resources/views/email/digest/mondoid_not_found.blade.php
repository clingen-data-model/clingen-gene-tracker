<h4>The following mondo ids could not be found:</h4>
<ul>
@foreach ($notifications as $notification)
    @php $curation = $notification->data['curation'] @endphp
    <li>{{$curation['mondo_id']}} for <a href="{{url('/#/curations/'.$curation['id'])}}"> curation {{$curation['id']}}</a> </li>    
@endforeach
</ul>

<p>Please check the following:</p>
<ol>
    <li>The MonDO ID is not correctly formatted.  Valid format: <strong>MONDO:123453</strong> (no spaces or other puntation)</li>
    <li>Leading zeros are required.</li>
</ol>
Please consult MonDO and update the curation with an existing MonDO ID.