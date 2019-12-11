import Axios from "axios"

const queryStringFromParams = function (params) {
    let queryStringParts = [
        'page=' + (params.currentPage ? params.currentPage : 1)
    ];
    delete (params.currentPage)
    for (let param in params) {
        if (params[param] === null || params[param] === undefined) {
            continue;
        }
        queryStringParts.push(encodeURIComponent(param) + '=' + encodeURIComponent(params[param]));
    }
   
    return queryStringParts.join('&');    
}

const getPageOfCurations = function (params) {
    const url = '/api/curations' + '?' + queryStringFromParams(params);
    return axios.get(url);
}

export default getPageOfCurations;