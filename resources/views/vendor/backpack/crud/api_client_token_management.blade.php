<script>
    const csrfToken = document.head.querySelector('meta[name="csrf-token"]');
    const initTokenDelete = (tokenId) => {
        if (confirm('Are you sure you want to delete this token?')) {
            const options = {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json;charset=utf-8',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken.content
                },

            }
            fetch(`/admin/api-client-tokens/${tokenId}`, options)
                .then(rsp => {
                    window.location.reload(true)
                })
        }
    }
</script>
<hr>
<h4>Access Tokens</h4>
<a href="/admin/api-client/{{$entry->id}}/create-token">Create a new token</a>
<table  class="table table-striped mb-0 w-50">
    <thead>
        <tr>
            <th>Name</th>
            <th>Last used</th>
            <th>&nbsp;</th>
        </tr>
    </thead>
    <tbody>
        @foreach($entry->tokens as $token)
        <tr>
            <td>{{$token->name}}</td>
            <td>{{$token->last_used_at}}</td>
            <td>
                <a 
                    href="#delete-token:{{$token->id}}" 
                    class="btn-link" 
                    onclick="initTokenDelete({{$token->id}})"
                >
                    Delete
                </a>
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
