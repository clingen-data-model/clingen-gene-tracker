<h2>An HGNC ID couldn't be found for the gene symbol {{$curation->gene_symbol}}</h2>

<p>
    There does not appear to be an HGNC record for <a href="{{url('/')}}#curations/{{$curation->id}}">your curation {{$curation->id}}</a> with gene symbol {{$curation->gene_symbol}}.  
</p>    
<p>
    This probably means that the gene symbol is not a valid HGNC symbol.  Please consult <a href="https://https://www.genenames.org/">HGNC</a> to find a valid gene symbol.
</p>
