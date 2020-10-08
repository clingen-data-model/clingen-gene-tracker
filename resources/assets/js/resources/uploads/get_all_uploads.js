import queryStringFromParams from '../../http/query_string_from_params'

export default async function(params) {
    const curationId = params.where.curation_id
    const baseUrl = '/api/curations/' + curationId + '/uploads';

    return await axios.get(baseUrl + ((params) ? '?' + queryStringFromParams(params) : ''))
        .then(response => {
            return response.data.data
        });
}