var BaseRepo = require('./Repository.js');

var OmimRepo = Object.create(BaseRepo);
OmimRepo.baseUrl = '/api/omim';
OmimRepo.all = function (params) {
    throw ('all method is not supported');
};
OmimRepo.all = function (params) {
    throw ('"all" method is not supported');
};
OmimRepo.store = function (params) {
    throw ('"store" method is not supported');
};
OmimRepo.update = function (params) {
    throw ('"update" method is not supported');
};
OmimRepo.destroy = function (params) {
    throw ('"destroy" method is not supported');
};

// Deprecated
OmimRepo.entry = function (mimNumber) {
    console.warn('OmimRepo.entry has been deprecated due to lack of use and will be removed in the future')
    return this.makeRequest('get', this.baseUrl+'/entry?mim_number='+mimNumber);
}

// Deprecated
OmimRepo.search = function (search) {
    console.warn('OmimRepo.search has been deprecated due to lack of use and will be removed in the future')
    return this.makeRequest('get', this.baseUrl+'/search?search='+search);
}

OmimRepo.gene = function (geneSymbol) {
    return this.makeRequest('get', this.baseUrl+'/gene/'+geneSymbol);
}

OmimRepo.forCuration = function (curationId) {
    const url = `${OmimRepo.baseUrl}/curation/${curationId}`
    console.log({baseUrl: OmimRepo.baseUrl, curationId, url})
    return this.makeRequest('get', url)
}

module.exports = OmimRepo;