We received info from the GCI about gene-disease in <strong>{{$stream_errors[0]['affiliation']['name']}}</strong> records we cannot match against the GeneTracker for :

<p>
    Our records indicate that a curation you started in the GCI (Human readable Gene/Disease/MOI) has not been entered into the Gene Tracker.  Please document this information in the Gene Tracker so that we can accurately track GCEP activity and precuration/disease-naming decisions.
</p>

<p>
    If you have any questions, please contact us at 
    <a href="mailto:clingentrackerhelp@unc.edu">clingentrackerhelp@unc.edu</a>.
</p>

<ul>
    @foreach ($stream_errors as $streamError)
        <li>
            <a href="https://curation.clinicalgenome.org/curation-central/{{$streamError['message_payload']['report_id']}}">
                {{ isset($streamError['gene_model'])
                        ? $streamError['gene_model']['gene_symbol'] 
                        : $streamError['gene'] }} 
                / {{ isset($streamError['disease_model'])
                        ? $streamError['disease_model']['name'] 
                        : $streamError['condition'] }} 
                / {{ isset($streamError['moi_model']) 
                    ? $streamError['moi_model']['name']
                    : $streamError['moi'] }}
            </a>
            on {{$streamError['created_at']}}
        </li>
    @endforeach
</ul>