<div class="alert alert-warning">
    <h5>Some of the genes in your upload already have curations in the GeneTracker:</h5>
    <small>
        <table class="table table-sm table-striped bg-white">
            <tr>
                <th>Gene</th>
                <th>ExpertPanel</th>
                <th>Status</th>
                <th>Phenotypes</th>
                <th>Last updated</th>
            </tr>
            @foreach ($duplicates->sortBy('gene_symbol') as $dup)
                <tr>
                    <td>{{$dup->gene_symbol}}</td>
                    <td>{{$dup->expertPanel->name}}</td>
                    <td>{{$dup->currentStatus ? $dup->currentStatus->name : '??'}}</td>
                    <td>
                        <ul class="list-unstyled">
                            @foreach ($dup->selectedPhenotypes as $pheno)
                                <li>{{$pheno->name}}</li> 
                            @endforeach
                        </ul>
                    </td>
                    <td>{{$dup->updated_at->format('Y-m-d')}}</td>
                </tr>
            @endforeach
        </table>
    </small>
    <form action="/bulk-uploads" method="POST">
        {{csrf_field()}}
        <input type="hidden" name="path" value="{{$path}}">
        <input type="hidden" name="expert_panel_id" value="{{$expert_panel_id}}">

        <a href="/bulk-uploads" class="btn btn-light btn-sm border"> 
            Cancel upload
        </a>
        
        <button class="btn btn-primary btn-sm border" type="submit" name="continue_duplicate_upload" value="1">
            Continue with upload
        </button>
    </form>
</div>
