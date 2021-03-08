<h4>HGNC Gene Symbols Updated:</h4>
<ul>
    @foreach ($notifications as $item)
        @php
            $curation = $item->data['curation'];
            $oldGeneSymbol = $item->data['oldGeneSymbol'];
        @endphp
        <li>
            <strong>{{$oldGeneSymbol}}</strong> to <strong>{{$curation['gene_symbol']}}</strong>
            - <a href="{{url('/#/curations/'.$curation['id'])}}">curation {{$curation['id']}}</a> 
            @if(isset($curation['hgnc_id']))
                - <a href="https://www.genenames.org/data/gene-symbol-report/#!/hgnc_id/HGNC:{{$curation['hgnc_id']}}">HGNC record</a>
            @endif
        </li>
    @endforeach
</ul>