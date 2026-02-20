{{-- resources/views/vendor/backpack/ui/dashboard.blade.php --}}
@extends(backpack_view('blank'))

@section('content')
@php
    use App\Phenotype;
    use App\Curation;

    $obsoletePhenotypesCount = Phenotype::where('obsolete', true)->count();

    $affectedCurationsCount = Curation::whereHas('phenotypes', function ($q) {
        $q->where('phenotypes.obsolete', true);
    })->count();

    $obsoleteUsedCount = Phenotype::query()
        ->join('curation_phenotype', 'curation_phenotype.phenotype_id', '=', 'phenotypes.id')
        ->where('phenotypes.obsolete', true)
        ->distinct('phenotypes.id')
        ->count('phenotypes.id');
@endphp

<div class="container-fluid">
    <div class="row">
        <div class="col-md-4 mb-3">
            <div class="card">
                <div class="card-body">
                    <div class="h2 mb-0">{{ $obsoletePhenotypesCount }}</div>
                    <div class="text-muted">Obsoleted phenotypes</div>
                    <a class="btn btn-sm btn-link px-0" href="{{ backpack_url('reports/omim-obsolete-phenotypes?tab=phenotypes') }}">
                        View report &raquo;
                    </a>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card">
                <div class="card-body">
                    <div class="h2 mb-0">{{ $affectedCurationsCount }}</div>
                    <div class="text-muted">Affected curations</div>
                    <a class="btn btn-sm btn-link px-0" href="{{ backpack_url('reports/omim-obsolete-phenotypes?tab=curations') }}">
                        View report &raquo;
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-3">
            <div class="card">
                <div class="card-body">
                    <div class="h2 mb-0">{{ $obsoleteUsedCount }}</div>
                    <div class="text-muted">Obsoleted phenotypes used on curations</div>
                    <a class="btn btn-sm btn-link px-0" href="{{ backpack_url('reports/omim-obsolete-phenotypes?tab=phenotypes') }}">
                        View report &raquo;
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Optional: include Backpack’s default dashboard content below, if you had any --}}
    {{-- @include(backpack_view('dashboard_content')) --}}
</div>
@endsection