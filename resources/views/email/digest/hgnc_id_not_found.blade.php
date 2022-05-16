<h4>Unknown HGNC IDs:</h4>

The following curations use gene symbols that do not appear to be in HGNC. Please consult <a href="https://https://www.genenames.org/">HGNC</a> to find a valid gene symbol.

<ul>
    @foreach ($notifications as $notification)
        <li>
            <a href="{{url('/#/curations/'.$notification->data['curation']['id'])}}">
                {{$notification->data['curation']['gene_symbol']}} 
                for {{$notification->data['curation']['expert_panel']['name']}}
            </a> 
        </li>
    @endforeach
</ul>