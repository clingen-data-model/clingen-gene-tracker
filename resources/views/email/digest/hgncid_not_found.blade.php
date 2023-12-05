There does not appear to be an HGNC record for one or more of your curations.  This probably means that the gene symbol is not a valid HGNC symbol.  Please consult <a href="https://https://www.genenames.org/">HGNC</a> to find a valid gene symbol:
<ul>
    @foreach ($notifications as $item)
        @php
            $curation = $item->data['curation'];
        @endphp
        <li>
            {{$curation['gene_symbol']}} - <a href="{{url('/#/curations/'.$curation['id'])}}"> curation {{$curation['id']}}</a>.  
        </li>        
    @endforeach
</ul>
