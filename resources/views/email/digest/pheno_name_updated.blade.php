<h4>OMIM phenotypes updated:</h4>
<ul>
    @foreach ($notifications as $notification)        
        @php 
            $oldName = $notification->data['oldName'];
            $curation = $notification->data['curation'];
            $phenotype = $notification->data['phenotype'];
        @endphp
        <li>
            <strong>{{$oldName}}</strong> to <strong>{{$phenotype['name']}}</strong> - <a href="{{url('/#/curations/'.$curation['id'])}}">curation {{$curation['id']}}</a>  - <a href="https://www.omim.org/entry/{{$phenotype['mim_number']}}">OMIM record</a>
        </li>
    @endforeach
</ul>
The curations listed have been automatically updated.