const queryStringFromParams = function(params = {}, paginate) {
    let parsedParams = params;
    if (Object.keys(params).includes('filter')) {
        let { filter, ...rest } = params;
        parsedParams = {...filter, ...rest };
    }

    let queryStringParts = [];
    if (paginate) {
        queryStringParts.push('page=' + (parsedParams.currentPage ? parsedParams.currentPage : 1))
    }

    delete(parsedParams.currentPage)

    for (let param in parsedParams) {
        if (parsedParams[param] === null || parsedParams[param] === undefined) {
            continue;
        }

        if (Array.isArray(parsedParams[param])) {
            parsedParams[param].forEach(val => {
                queryStringParts.push(encodeURIComponent(param) + '[]=' + encodeURIComponent(val));
            })
        } else if (typeof parsedParams[param] == 'object' && parsedParams[param] !== null) {
            Object.keys(parsedParams[param]).forEach(key => {
                queryStringParts.push(`${encodeURIComponent(param)}[${key}]=${parsedParams[param][key]}`)
            })
        } else {
            queryStringParts.push(encodeURIComponent(param) + '=' + encodeURIComponent(parsedParams[param]));
        }

    }

    return queryStringParts.join('&');
}

export default queryStringFromParams;