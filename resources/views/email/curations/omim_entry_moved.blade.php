<h2>The OMIM entry for the phenotype {{$oldName}} has moved.</h2>

<p>
    OMIM has moved the entry for a phenotype associated with one of your <a href="{{url('/#/curations/'.$curation->id)}}">curations</a>.
    The phenotype {{$oldName}} with MIM Number {{$oldMimNumber}} has been moved to the MIM number {{$phenotype->mim_number}} with preferred title of "{{$phenotype->name}}" 
    and one of your <a href="{{url('/#/curations/'.$curation->id)}}">curation</a> has been automatically updated.

    You may want to <a href="https://www.omim.org/entry/{{$phenotype->mim_number}}">review the new OMIM record</a>.
</p>
