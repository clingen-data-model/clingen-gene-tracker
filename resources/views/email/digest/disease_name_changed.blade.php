<h4>MonDO Nomenclature Updated:</h4>
<ul>
    @foreach ($notifications as $item)
        @php
            $curation = $item->data['curation'];
            $oldName = $item->data['oldName'];
        @endphp
        <li>
            <a href="https://monarchinitiative.org/disease/{{$curation['mondo_id']}}">{{$curation['mondo_id']}}</a>:            
            <strong>{{$oldName}}</strong>
            to 
            <strong>{{$curation['disease']['name']}}</strong>
            - 
            <a href="{{url('/#/curations/'.$curation['id'])}}">curation {{$curation['id']}}</a> 
        </li>
    @endforeach
</ul>