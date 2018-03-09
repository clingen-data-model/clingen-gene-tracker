var handleError = function(message){
    console.error(message);
    alert('There was a problem: '+message+'. Please notify the development team.');
}

var objectToQueryString = function(obj){
    var params = [];
    for (var key in obj) {
        params.push(key+'='+obj[key]);
    }
    return '?'+params.join('&');
}

module.exports = {
    baseUrl: null,
    name: 'BaseRepository',
    dates: [],
    makeRequest: function(method, url, data){
        if(!url){
            throw 'No url';
        }

        method = (method) ? method : 'GET';

        var request = {
            method: method,
            url: url,
        }

        if (data) {
            request.data = data;
        }
        console.log(request.data);

        return window.axios(request);
    },

    all: function(params){
        var url = this.baseUrl+objectToQueryString(params);        
        return this.makeRequest('get', url)
                .then(function (response) {
                    var data = response.data.map(function (item) {
                        return this.transformDates(item);
                    }.bind(this));

                    var retVal = {
                        data: data,
                        status: response.status,
                        headers: response.headers,
                        request: response.request,
                        statusText: response.statusText,
                        config: response.config
                    };
                    return retVal;
                }.bind(this))
                .catch(function(error){
                    handleError('Unable to retrieve '+this.name)
                    return error;
                }.bind(this));
    },
    find: function(id, params){
        var url = this.baseUrl+'/'+id+objectToQueryString(params);
        return this.makeRequest('get', url).then(function (response) {
            response.data = this.transformDates(response.data);
            return response
        }.bind(this));
    },

    save: function(data){
        for (var key in data) {
            if(data[key] && data[key]._isAMomentObject){
                data[key] = data[key].format('YYYY-MM-DD HH:mm:ss');
            }else if( this.dates.indexOf(key) > -1){
                data[key] = moment(data[key]).format('YYYY-MM-DD HH:mm:ss');
            }
        }

        if (data.id) {
            return this.update(data);
        }else{
            return this.store(data);
        }
    },

    store: function(data){
        return this.makeRequest('post', this.baseUrl, data)
            .then(function (response) {
                response.data = this.transformDates(response.data);
                return response;
            }.bind(this));
    },

    update: function(data){
        return this.makeRequest('put', this.baseUrl+'/'+data.id, data);
    },

    destroy: function(id) {
        return this.makeRequest('delete', this.baseUrl+'/'+id);
    },

    transformDates: function(obj) {
        var data = {};
        for (var key in obj) {
            if( this.dates.indexOf(key) > -1){
                data[key] = moment(obj[key]);
            }else{
                data[key] = obj[key]
            }
        }
        return data;
    }

};