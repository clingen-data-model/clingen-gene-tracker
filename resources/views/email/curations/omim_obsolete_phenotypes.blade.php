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
  Please review the changes to the OMIM label with your Expert Panel and make any necessary updates to the precuration record.
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

  <h3 style="margin-top: 18px;">Curations with obsolete OMIM phenotype labels</h3>

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
          <strong>Newly flagged obsolete phenotype labels:</strong>
          <ul style="margin: 6px 0 0 18px;">
            @foreach ($phenos as $p)
              <li><a href="https://omim.org/entry/{{ $p['mim_number'] ?? '' }}" target="_blank">{{ $p['mim_number'] ?? '' }}</a> — {{ $p['name'] ?? '' }}</li>

              @if (!empty($p['current_phenotypes']))
                <div style="margin-top: 4px; color:#555;">
                  Current label(s) with the same MIM:
                  <ul style="margin: 4px 0 8px 18px;">
                    @foreach ($p['current_phenotypes'] as $current)
                      <li>{{ $current['mim_number'] ?? '' }} — {{ $current['name'] ?? '' }}</li>
                    @endforeach
                  </ul>
                </div>
              @else
                <div style="margin-top: 4px; color:#777;">
                  No current label with the same MIM was found in GeneTracker.
                </div>
              @endif
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