@php
    $toDate = now();
    $baseUrl = rtrim(config('app.url'), '/');
@endphp

<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; font-size: 14px; color: #222; }
        .muted { color: #666; }
        .box { background: #fff3cd; border: 1px solid #ffeeba; padding: 10px 12px; border-radius: 4px; }
        .curation { margin-top: 16px; }
        .pheno-list { margin: 6px 0 0 18px; }
        .pheno-list li { margin: 2px 0; }
        .small { font-size: 12px; }
        a { color: #0b5ed7; }
        hr { border: 0; border-top: 1px solid #eee; margin: 16px 0; }
    </style>
</head>
<body>

<p>
    Hi {{ $user->name }},
</p>

<p>
    This is a weekly GeneTracker digest. Our daily OMIM data import detected that <strong>some OMIM phenotype labels currently used in your curations are no longer present</strong>.
</p>

<div class="box">
    <div><strong>Expert Panel:</strong> {{ $expertPanel->name }}</div>
    <div><strong>Time window:</strong> {{ $since->toDateString() }} – {{ $toDate->toDateString() }}</div>
</div>

<p class="muted small" style="margin-top: 10px;">
    Note: A missing label usually means it was renamed/replaced in OMIM, or in some cases removed. In GeneTracker we flag these as "Not in latest OMIM" so curators can review them.
</p>
<p>
    If you are concerned about the changes to the OMIM label change, you may want to consider reviewing the precuration records (linked below) and updating as necessary with input from the expert panel.
</p>
<h3 style="margin-top: 18px;">Curations affected</h3>

@foreach ($curations as $curation)
    @php
        $curationUrl = $baseUrl . '/home#/curations/' . $curation->id;
        $phenos = $curation->phenotypes ?? collect();
    @endphp

    <div class="curation">
        <div>
            <strong>Curation:</strong>
            {{ $curation->gene_symbol }}
            @if(!empty($curation->mondo_id))
                / {{ $curation->mondo_id }}
            @endif
            @if(!empty($curation->mondo_name))
                ({{ $curation->mondo_name }})
            @endif
        </div>

        <div class="small">
            <a href="{{ $curationUrl }}">{{ $curationUrl }}</a>
        </div>

        @if($phenos->count() > 0)
            <div style="margin-top: 6px;">
                <strong>Newly flagged phenotypes:</strong>
                <ul class="pheno-list">
                    @foreach ($phenos as $p)
                        <li>
                            {{ $p->mim_number }} — {{ $p->name }}
                        </li>
                    @endforeach
                </ul>
            </div>
        @else
            <div class="muted small" style="margin-top: 6px;">
                (No phenotype details found for this curation in the selected window.)
            </div>
        @endif
    </div>

    <hr>
@endforeach

<p>
    Thanks,<br>
    GeneTracker Team
</p>

</body>
</html>