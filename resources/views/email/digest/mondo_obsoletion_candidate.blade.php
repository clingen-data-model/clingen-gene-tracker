
A MonDO term used in one or more of your curations has been marked by MONDO for obsoletion.

<table cellpadding="10px" cellspacing="0">
    <tr>
        <th>Term</th>
        <th>Curation</th>
        <th>Obsoletion Scheduled</th>
        <th>MonDO Comments</th>
    </tr>
    @foreach($notifications as $notification)
        @php 
            $curation = $notification->data['curation'];
            $messageData = $notification->data['message_data'];
        @endphp
        <tr>
            <td>
                <a href="https://monarchinitiative.org/disease/{{$messageData['content']['mondo_id']}}">
                    {{$messageData['content']['label']}} ({{$messageData['content']['mondo_id']}}) 
                </a> 
            </td>
            <td>
                <a href="/curations/{{$curation['id']}}">
                    Curation for {{$curation['gene_symbol']}} in {{$curation['expert_panel']['name']}}
                </a>
            </td>
            <td>{{$messageData['content']['obsoletion_date']}}</td>
            <td>
                {{$messageData['content']['comment']}}
                <br>
                <a href="{{$messageData['content']['issue']}}">More Details</a>
            </td>
        </li>
    @endforeach
</table>