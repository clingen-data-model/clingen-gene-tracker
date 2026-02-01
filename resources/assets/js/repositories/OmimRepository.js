import BaseRepo from './Repository.js';

var OmimRepo = Object.create(BaseRepo);
OmimRepo.baseUrl = '/api/omim';
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

OmimRepo.gene = function (geneSymbol) {
    return this.makeRequest('get', this.baseUrl+'/gene/'+geneSymbol);
}

OmimRepo.forCuration = function (curationId) {
    const url = `${OmimRepo.baseUrl}/curation/${curationId}`
    return this.makeRequest('get', url)
}

export default OmimRepo;
