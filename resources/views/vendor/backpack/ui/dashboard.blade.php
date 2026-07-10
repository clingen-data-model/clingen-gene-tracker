{{-- resources/views/vendor/backpack/ui/dashboard.blade.php --}}
@extends(backpack_view('blank'))

@section('content')
@php
    use App\Phenotype;

    $outdatedPhenotypesCount = Phenotype::outdatedCount();
    $affectedCurationsCount = Phenotype::affectedCurationsCount();
    $outdatedUsedCount = Phenotype::outdatedInUseCount();
@endphp

<div class="container-fluid">
    <div class="row">
        <div class="col-md-4 mb-3">
            <div class="card">
                <div class="card-body">
                    <div class="h2 mb-0">{{ $outdatedPhenotypesCount }}</div>
                    <div class="text-muted">Outdated Phenotype Labels</div>
                    <a class="btn btn-sm btn-link px-0" href="{{ backpack_url('reports/omim-outdated-phenotypes?tab=phenotypes') }}">
                        View report &raquo;
                    </a>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card">
                <div class="card-body">
                    <div class="h2 mb-0">{{ $affectedCurationsCount }}</div>
                    <div class="text-muted">Affected Curations</div>
                    <a class="btn btn-sm btn-link px-0" href="{{ backpack_url('reports/omim-outdated-phenotypes?tab=curations') }}">
                        View report &raquo;
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-3">
            <div class="card">
                <div class="card-body">
                    <div class="h2 mb-0">{{ $outdatedUsedCount }}</div>
                    <div class="text-muted">Outdated Phenotype Labels used on Curations</div>
                    <a class="btn btn-sm btn-link px-0" href="{{ backpack_url('reports/omim-outdated-phenotypes?tab=phenotypes') }}">
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