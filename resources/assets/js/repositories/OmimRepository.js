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

OmimRepo.entry = function (mimNumber) {
    return this.makeRequest('get', this.baseUrl+'/entry?mim_number='+mimNumber);
}

OmimRepo.search = function (search) {
    return this.makeRequest('get', this.baseUrl+'/search?search='+search);
}

OmimRepo.gene = function (geneSymbol) {
    return this.makeRequest('get', this.baseUrl+'/gene/'+geneSymbol);
}

module.exports = OmimRepo;