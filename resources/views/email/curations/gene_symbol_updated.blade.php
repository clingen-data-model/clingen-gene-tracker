<h2>The HGNC gene symbol {{$oldGeneSymbol}} has changed.</h2>

<p>
    HGNC has updated the gene symbol {{$oldGeneSymbol}} to {{$curation->gene_symbol}} 
    and one of your <a href="url('/#/curations/{{$curation->id}}')">curations</a> has been automatically updated.

    You may want to <a href="https://www.genenames.org/data/gene-symbol-report/#!/hgnc_id/HGNC:{{$curation->hgnc_id}}">review the HGNC record</a>.
</p>
