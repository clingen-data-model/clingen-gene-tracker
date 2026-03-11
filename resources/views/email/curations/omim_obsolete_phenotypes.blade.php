@php
  $toDate = now();
  $baseUrl = rtrim(config('app.url'), '/');
@endphp

{{-- Show the intro only once per digest section --}}
@if(isset($user))
<p>Hi {{ $user->name }},</p>
@endif

<p>
  This is a weekly GeneTracker digest. Our daily OMIM data import detected that
  <strong>some OMIM phenotype labels currently used in your curations are no longer present</strong>.
</p>

<p style="color:#666; font-size:12px;">
  Note: A missing label usually means it was renamed/replaced in OMIM, or in some cases removed.
  In GeneTracker we flag these as "Not in latest OMIM" so curators can review them.
</p>

<p>
  If you are concerned about the changes to the OMIM label change, you may want to consider reviewing the precuration records (linked below) and updating as necessary with input from the expert panel.
  <strong>Action requested:</strong> For each curation listed below, open the curation in GeneTracker, review the phenotype(s) marked <strong>Not in latest OMIM</strong>, and replace or remove them if needed. Use the links provided.
</p>

@foreach ($group as $notification)
  @php
    $data = $notification->data ?? [];
    $expertPanel = $data['expert_panel'] ?? null;

    $since = $data['since'] ?? null;
    $sinceDate = $since ? \Carbon\Carbon::parse($since) : $toDate->copy()->subDays(7);

    $curations = $data['curations'] ?? [];
  @endphp

  <div style="background:#fff3cd; border:1px solid #ffeeba; padding:10px 12px; border-radius:4px; margin: 12px 0;">
    <div><strong>Expert Panel:</strong> {{ $expertPanel['name'] ?? '' }}</div>
    <div><strong>Time window:</strong> {{ $sinceDate->toDateString() }} – {{ $toDate->toDateString() }}</div>
  </div>

  <h3 style="margin-top: 18px;">Curations affected</h3>

  @foreach ($curations as $c)
    @php
      $curationUrl = $c['link'] ?? ($baseUrl.'/home#/curations/'.($c['id'] ?? ''));
      $phenos = $c['phenotypes'] ?? [];
    @endphp

    <div style="margin-top: 12px;">
      <div>
        <strong>Curation:</strong> {{ $c['gene_symbol'] ?? '' }}
        (Precuration ID: {{ $c['id'] ?? '' }})
      </div>

      <div style="font-size:12px;">
        <a href="{{ $curationUrl }}">{{ $curationUrl }}</a>
      </div>

      @if (!empty($phenos))
        <div style="margin-top: 6px;">
          <strong>Newly flagged phenotypes:</strong>
          <ul style="margin: 6px 0 0 18px;">
            @foreach ($phenos as $p)
              <li>{{ $p['mim_number'] ?? '' }} — {{ $p['name'] ?? '' }}</li>
            @endforeach
          </ul>
        </div>
      @endif
    </div>

    <hr style="border:0; border-top:1px solid #eee; margin: 16px 0;">
  @endforeach
@endforeach

<p>
    Thanks,<br>
    GeneTracker Team
</p>