We received info from the GCI about gene-disease in <strong>{{$stream_errors[0]['affiliation']['name']}}</strong> records we cannot match against the GeneTracker for :
<ul>
    @foreach ($stream_errors as $streamError)
        <li>
            <a href="https://curation.clinicalgenome.org/curation-central/?gdm={{$streamError['message_payload']['report_id']}}">
                {{$streamError['gene']}}
                /
                {{$streamError['condition']}}
                /
                {{$streamError['moi']}}
            </a>
            on {{$streamError['created_at']}}
        </li>
    @endforeach
</ul>