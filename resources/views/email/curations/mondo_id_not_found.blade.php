<li>
    The MonDO ID {{$curation['mondo_id']}} used for 
    <a href="{{url('/#/curations/'.$curation['id'])}}"> your curatation with id {{$curation['id']}}</a>
    could not be found in the Monarch Disease Ontology.
    <br>

    <p>Please check the following:</p>
    <ol>
        <li>The MonDO ID is not correctly formatted.  MonDO IDs should be formatted <strong>MONDO:123453</strong> with no spaces or other puntation</li>
        <li>Leading zeros are required.</li>
    </ol>

    <br>
    Please consult MonDO and update the curation with an existing MonDO ID.
</li>