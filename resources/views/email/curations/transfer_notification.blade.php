<h2>A curation has been transferred to one of your expert panels.</h2>

<p>
    {{$previousEp->name}} has transferred the GeneTracker record for 
    <a href="{{url('/#/curations/'.$curation->id)}}">{{$curation->gene_symbol}}</a>
    to
    {{$curation->expertPanel->name}}.
</p>

<p>If you have questions please contact {{$previousEp->name}} coordinator(s), who have been CCed on this email</p>

<p>Thanks,</p>
<p>ClinGen Gene Tracker</p>