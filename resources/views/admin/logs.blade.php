<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>GeneTracker Logs</title>
    @vite(['resources/assets/sass/app.scss'])
</head>
<body>
<main class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">Laravel Log Viewer</h1>
        <a class="btn btn-outline-secondary" href="/home#/admin">Back to Administration</a>
    </div>
    <div class="row">
        <aside class="col-md-3 col-lg-2 mb-3">
            <h2 class="h5">Log files</h2>
            <div class="list-group">
                @forelse($files as $file)
                    <a
                        class="list-group-item list-group-item-action {{ $current_file === $file ? 'active' : '' }}"
                        href="?l={{ urlencode(\Illuminate\Support\Facades\Crypt::encryptString($file)) }}"
                    >{{ $file }}</a>
                @empty
                    <span class="text-muted">No log files found.</span>
                @endforelse
            </div>
        </aside>
        <section class="col-md-9 col-lg-10">
            @if($logs === null)
                <div class="alert alert-warning">This log is too large to display. Download it for offline inspection.</div>
            @elseif(count($logs))
                <div class="table-responsive">
                    <table class="table table-striped table-sm">
                        <thead><tr><th>Level</th><th>Context</th><th>Date</th><th>Content</th></tr></thead>
                        <tbody>
                            @foreach($logs as $log)
                                <tr>
                                    <td>{{ $log['level'] }}</td>
                                    <td>{{ $log['context'] }}</td>
                                    <td class="text-nowrap">{{ $log['date'] }}</td>
                                    <td><div class="text-break">{{ $log['text'] }}</div>@if($log['stack'])<pre class="small text-wrap mt-2">{{ trim($log['stack']) }}</pre>@endif</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                {{ $logs->links('pagination::bootstrap-5') }}
                @if($current_file)
                    <a class="btn btn-outline-primary" href="?dl={{ urlencode(\Illuminate\Support\Facades\Crypt::encryptString($current_file)) }}">Download file</a>
                @endif
            @else
                <p class="text-muted">The selected log is empty.</p>
            @endif
        </section>
    </div>
</main>
</body>
</html>
