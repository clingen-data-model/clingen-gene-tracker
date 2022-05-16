<p>Hi {{$notifiable->first_name}},</p>

<p>
    MonDO has obsoleted the term 
    <a href="https://monarchinitiative.org/disease/{{$curation->mondo_id}}">
        {{$curation->disease->name}} ({{$curation->mondo_id}})
    </a> 
    related to 
    <a href="{{url('/#/curations/'.$curation->id)}}">
        {{$curation->gene_symbol}} 
        for 
        {{$curation->expertPanel->name}}
    </a>

    @if ($curation->disease->replaced_by)
        and replaced it with 
        <a href="https://monarchinitiative.org/disease/{{$curation->disease->mondo_id}}">
            {{$curation->disease->replaced_by}}
        </a>
    @else
        and has not suggested a replacement.
    @endif
</p>

<p>
Thanks,
<br>
{{config('mail.from.name')}}
</p>