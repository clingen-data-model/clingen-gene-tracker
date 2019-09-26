<h2>
    The MonDO ID {{$curation->mondo_id}} entered for 
    a <a href="{{url('/#/curations/'.$curation->id)}}">curation {{$curation->id}}</a> was not found
</h2>

<p>
    The MonDO ID {{$curation->mondo_id}} used for 
    <a href="{{url('/#/curations/'.$curation->id)}}">one of your curatations</a>
    could not be found in the Monarch Disease Ontology.
</p>

<p>
    Please consult MonDO and update the curation with an existing MonDO ID.
</p>