We received info from the GCI about gene-disease records we cannot match against the GeneTracker:
<ul>
    @foreach ($streamErrors as $streamError)
        <li>
            <a href="https://curation.clinicalgenome.org/curation-central/?gdm={{$streamError->message_payload->report_id}}">
                {{$streamError->message_payload->gene_validity_evidence_level->genetic_condition->gene}}
                /
                {{$streamError->message_payload->gene_validity_evidence_level->genetic_condition->condition}}
                /
                {{$streamError->message_payload->gene_validity_evidence_level->genetic_condition->mode_of_inheritance}}
            </a>
        </li>
    @endforeach
</ul>