    OMIM has moved the entry for a phenotype associated with one or more of your curations.
    
    <ul>
        @foreach ($notifications as $notification)
            @php
                $oldName = $notification->data['oldName'];
                $oldMimNumber = $notification->data['oldMimNumber'];
                $phenotypes = $notification->data['phenotypes'];
            @endphp
            <li>
                @if (count($phenotypes) == 1)        
                    The phenotype {{$oldName}} with MIM Number {{$oldMimNumber}} has been moved to the MIM number {{$phenotypes[0]['mim_number']}} with preferred title of "{{$phenotypes[0]['name']}}" 
                    and one of your <a href="{{url('/#/curations/'.$curation['id'])}}">curation</a> has been automatically updated.
                     
                    <a href="https://www.omim.org/entry/{{$phenotypes[0]['mim_number']}}">
                        review the new OMIM record
                    </a>.
                @else
                    The phenotype {{$oldName}} with MIM Number {{$oldMimNumber}} has been moved multiple new MIM entries:
                    <ul>
                        @foreach ($phenotypes as $phenotype)
                            <li>
                                <a href="https://www.omim.org/entry/{{$phenotype['mim_number']}}">
                                    {{$phenotype['mim_number']}} - {{$phenotype['name']}}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </li>
        @endforeach
    </ul>

