<h4>OMIM phenotypes added:</h4>
<p>OMIM has added new phenotypes to genes relevant to one of your expert panels:</p>
<ul>
    @foreach ($notifications as $notification)
        <li>
            {{$notification->data['phenotype']['name']}} 
            added for 
            <a href="{{url('/#/curations/'.$notification->data['curation']['id'])}}">
                {{$notification->data['curation']['gene_symbol']}}
            </a>.
        </li>
    @endforeach
</ul>