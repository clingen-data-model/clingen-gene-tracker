@if (isset($newCurations) && $newCurations->count() > 0)
    <div class="alert alert-info">
        Created {{$newCurations->count()}} new curations for {{$newCurations->first()->expertPanel->name}}
        <ul>
        @foreach ($newCurations as $curation)
            <li>
                <a href="/#/curations/{{$curation->id}}">{{$curation->gene_symbol}}</a>
            </li>
        @endforeach
        </ul>
    </div>
@endif
