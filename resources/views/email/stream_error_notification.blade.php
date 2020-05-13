We received info from the GCI about gene-disease in <strong>{{$stream_errors[0]['affiliation']['name']}}</strong> records we cannot match against the GeneTracker for :

We have received data from the GCI about a gene-disease relationship that may be of interest or associated with your group, {{$stream_errors[0]['affiliation']['name']}}, however the data does not match our current records. Please review the following records and if they to belong to your group, please consider updating the information (e.g. Affiliation, MonDO ID, etc) to optimize automation of future updates. If you have any questions, please contact us at clingentrackerhelp@unc.edu
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