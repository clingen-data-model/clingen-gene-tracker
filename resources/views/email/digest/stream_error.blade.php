<h4>Unmatchable GCI Records</h4>
We have received data from the GCI about a gene-disease relationship that may be of interest or associated with one or more of your groups, however the data does not match our current records. Please review the following records and if they to belong to your group, please consider updating the information (e.g. Affiliation, MonDO ID, etc) to optimize automation of future updates. If you have any questions, please contact us at clingentrackerhelp@unc.edu

@php
    $groupedErrors = $notifications->map(function ($item) {
        return $item->data['stream_errors'];
    })
    ->flatten(1)
    ->groupBy(function ($streamError) {
        return $streamError['affiliation']['name'];
    });
@endphp

<ul>
    @foreach ($groupedErrors as $epName => $errors)
        {{-- @php dump($epName); dump($errors->count()) @endphp --}}
        <li>
            Expert Panel: {{$epName}}
            <ul>
                @foreach ($errors as $streamError)
                <li>
                    {{-- @php dump($streamError) @endphp --}}
                    <a href="https://curation.clinicalgenome.org/curation-central/{{$streamError['message_payload']['report_id']}}/">
                        {{$streamError['gene']}} / {{$streamError['condition']}} / {{$streamError['moi']}}
                    </a>
                    on {{\Carbon\Carbon::parse($streamError['created_at'])->format('Y-m-d')}}
                </li>
                @endforeach
            </ul>
        </li>
    @endforeach
</ul>
