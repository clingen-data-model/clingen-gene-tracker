@extends(backpack_view('blank'))

@section('content')
<div class="container-fluid">

  <div class="row mb-3">
    <div class="col-md-4">
      <div class="card">
        <div class="card-body">
          <div class="h2 mb-0">{{ $obsoletePhenotypesCount }}</div>
          <div class="text-muted">Obsoleted phenotypes</div>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card">
        <div class="card-body">
          <div class="h2 mb-0">{{ $affectedCurationsCount }}</div>
          <div class="text-muted">Affected curations</div>
        </div>
      </div>
    </div>
  </div>

  <div class="card mb-3">
    <div class="card-body py-2">
      <a class="btn btn-sm {{ $tab === 'phenotypes' ? 'btn-primary' : 'btn-outline-primary' }}"
        href="{{ backpack_url('reports/omim-obsolete-phenotypes?tab=phenotypes') }}">
        Obsoleted phenotypes
      </a>

      <a class="btn btn-sm {{ $tab === 'curations' ? 'btn-primary' : 'btn-outline-primary' }}"
        href="{{ backpack_url('reports/omim-obsolete-phenotypes?tab=curations') }}">
        Affected curations
      </a>
    </div>
  </div>

  @if ($tab === 'phenotypes')
    <div class="card mb-3">
      <div class="card-header">Obsoleted phenotypes</div>
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
            @foreach ($obsoletePhenotypes as $p)
              <tr>
                <td>{{ $p->mim_number }}</td>
                <td>{{ $p->name }}</td>
                
                <td class="text-right">
                  {{ $p->affected_curations > 0 ? $p->affected_curations . ' Curation(s): ' : 'N/A' }}
                  @php
                    $ids = $p->curation_ids_sample ? explode(',', $p->curation_ids_sample) : [];
                  @endphp

                  @if (count($ids))
                      @foreach ($ids as $cid)
                        <a href="{{ url('home#/curations/'.$cid) }}" target="_blank">{{ $cid }}</a>@if(!$loop->last), @endif
                      @endforeach
                  @endif
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>

    {{ $obsoletePhenotypes->appends(['tab' => $tab])->links('pagination::simple-tailwind') }}
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
              <th>Obsolete phenotypes</th>
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