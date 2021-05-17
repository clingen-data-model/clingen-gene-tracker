    OMIM has moved the entry for a phenotype associated with one of your <a href="{{url('/#/curations/'.$curation['id'])}}">curations</a>.
    
    
    
    @if ($phenotypes->count() == 1)        
        The phenotype {{$oldName}} with MIM Number {{$oldMimNumber}} has been moved to the MIM number {{$phenotypes->first()->mim_number}} with preferred title of "{{$phenotypes->first()->name}}" 
        and one of your <a href="{{url('/#/curations/'.$curation['id'])}}">curation</a> has been automatically updated.
        
        You may want to 
        <a href="https://www.omim.org/entry/{{$phenotypes->first()->mim_number}}">
            review the new OMIM record
        </a>.
    @else
        The phenotype {{$oldName}} with MIM Number {{$oldMimNumber}} has been moved multiple new MIM entries:
        <ul>
            @foreach ($phenotypes as $phenotype)
                <li>
                    <a href="https://www.omim.org/entry/{{$phenotype->mim_number}}">
                        {{$phenotype->mim_number}} - {{$phenotype->name}}
                    </a>
                </li>
            @endforeach
        </ul>
        You may want to review the OMIM records.
    @endif

