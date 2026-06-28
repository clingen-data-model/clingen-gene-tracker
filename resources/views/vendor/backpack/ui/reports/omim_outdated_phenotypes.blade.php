@extends(backpack_view('blank'))

@section('content')
<div class="container-fluid">

  <div class="row mb-3">
    <div class="col-md-4">
      <div class="card">
        <div class="card-body">
          <div class="h2 mb-0">{{ $outdatedPhenotypesCount }}</div>
          <div class="text-muted">Outdated Phenotype Labels</div>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card">
        <div class="card-body">
          <div class="h2 mb-0">{{ $affectedCurationsCount }}</div>
          <div class="text-muted">Affected Curations</div>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card">
        <div class="card-body">
          <div class="h2 mb-0">{{ $outdatedUsedCount }}</div>
          <div class="text-muted">Outdated Phenotype Labels used on Curations</div>
        </div>
      </div>
    </div>
  </div>

  <div class="card mb-3">
    <div class="card-body py-2 d-flex justify-content-between align-items-center">
      <div>
        <a class="btn btn-sm {{ $tab === 'phenotypes' ? 'btn-primary' : 'btn-outline-primary' }}"
          href="{{ backpack_url('reports/omim-outdated-phenotypes?tab=phenotypes') }}">
          Outdated Phenotype Labels
        </a>

        <a class="btn btn-sm {{ $tab === 'curations' ? 'btn-primary' : 'btn-outline-primary' }}"
          href="{{ backpack_url('reports/omim-outdated-phenotypes?tab=curations') }}">
          Affected Curations
        </a>
      </div>

      <div>
        <a class="btn btn-sm btn-outline-primary"
          href="{{ backpack_url('reports/omim-outdated-phenotypes?tab='.$tab.'&download=csv'.(request('phenotype_id') ? '&phenotype_id='.request('phenotype_id') : '')) }}">
          Download CSV
        </a>
      </div>
    </div>
  </div>

  @if ($tab === 'phenotypes')
    <div class="card mb-3">
      <div class="card-header">Outdated Phenotype Labels</div>
      <div class="card-body p-0">
        <table class="table table-sm mb-0">
          <thead>
            <tr>
              <th>MIM</th>
              <th>Name</th>
              <th class="text-right">Affected curations</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($outdatedPhenotypes as $p)
              <tr>
                <td>{{ $p->mim_number }}</td>
                <td>{{ $p->name }}</td>
                
                <td class="text-right" width="50%">
                  {{ $p->affected_curations > 0 ? $p->affected_curations . ' Curation(s): ' : 'N/A' }}
                  @php
                    $ids = $p->curation_ids_sample ? explode(',', $p->curation_ids_sample) : [];
                  @endphp

                  @if (count($ids))
                      @foreach ($ids as $cid)
                        <a href="{{ url('home#/curations/'.$cid) }}" target="_blank">{{ isset($curationGeneMap[$cid]) ? data_get($curationGeneMap, $cid, $cid) . ' #' . $cid : $cid }}</a>@if(!$loop->last), @endif
                      @endforeach
                  @endif
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>

    {{ $outdatedPhenotypes->appends(['tab' => $tab])->links('pagination::simple-tailwind') }}
  @endif

  @if ($tab === 'curations')
    <div class="card mb-3">
      <div class="card-header">Affected curations</div>
      <div class="card-body p-0">
        <table class="table table-sm mb-0">
          <thead>
            <tr>
              <th>Precuration ID</th>
              <th>Gene</th>
              <th>MONDO</th>
              <th>Expert Panel</th>
              <th>Outdated Phenotype Labels</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($affectedCurations as $c)
              <tr>
                <td>
                  <a href="{{ url('home#/curations/'.$c->id) }}" target="_blank">{{ $c->id }}</a>
                </td>
                <td>{{ $c->gene_symbol }}</td>
                <td>{{ $c->mondo_id }}</td>
                <td>{{ optional($c->expertPanel)->name }}</td>
                <td>
                  {{ $c->phenotypes->map(fn($ph) => $ph->name.' ('.$ph->mim_number.')')->join('; ') }}
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>

    {{ $affectedCurations->appends(['tab' => $tab])->links('pagination::simple-tailwind') }}
  @endif
</div>
@endsection