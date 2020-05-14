import Axios from "axios"

const queryStringFromParams = function (params) {
    let queryStringParts = [];
    for (let param in params) {
        if (params[param] === null || params[param] === undefined) {
            continue;
        }
        queryStringParts.push(encodeURIComponent(param) + '=' + encodeURIComponent(params[param]));
    }
   
    return queryStringParts.join('&');
}

const getCurations = function (params) {
    const url = '/api/curations' + '?' + queryStringFromParams(params);
    return axios.get(url);
}

export default getCurations;