<h4>HGCN Gene Symbols Updated:</h4>
<ul>
    @foreach ($notifications as $item)
        @php
            $curation = $item->data['curation'];
            $oldGeneSymbol = $item->data['oldGeneSymbol'];
        @endphp
        <li>
            <strong>{{$oldGeneSymbol}}</strong> to <strong>{{$curation['gene_symbol']}}</strong>
            - <a href="url('/#/curations/{{$curation['id']}}')">curation {{$curation['id']}}</a> 
            - <a href="https://www.genenames.org/data/gene-symbol-report/#!/hgnc_id/HGNC:{{$curation['hgnc_id']}}">HGNC record</a>
        </li>
    @endforeach
</ul>