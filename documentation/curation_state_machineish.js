states = {
    uploaded: {
        name: 'uploaded',
        system: 'gt'
    },
    precuration: {
        name: 'precuration',
        system: 'gt'
    },
    precurationComplete: {
        name: 'precuration-complete',
        system: 'gt'
    },
    inProgress: {
        name: 'in-progress',
        system: 'gci'
    },
    provisional: {
        name: 'provisional',
        system: 'gci'
    },
    approved: {
        name: 'approved',
        system: 'gci'
    },
    published: {
        name: 'published',
        system: 'gci'
    },
    unpublished: {
        name: 'unpublished',
        system: 'gci'
    },
    newProvisional: {
        name: 'new-provisional',
        system: 'gci'
    },
    retired: {
        name: 'retired',
        system: 'gt'
    },
    // recuration: {
    //     name: 'recuration',
    //     system: ['gt', 'gci']
    // }
}

const init = 'uploaded'
const transitions = [
    {name: 'beginPrecuration',          from: 'uploaded',               to: 'precuration'},
    {name: 'rollback',                  from: 'precuration',            to: 'uploaded'},
    {name: 'markPrecurationComplete',   from: 'precuration',            to: 'precurationComplete'},
    {name: 'propogateToGci',            from: 'precurationComplete',    to: 'inProgress'},
    {name: 'classify',                  from: 'inProgress',             to: 'provisional'},
    {name: 'updateEvidence',            from: 'provisional',            to: 'inProgress'},
    {name: 'approveClassification',     from: 'provisional',            to: 'published'},
    {name: 'unpublish',                 from: 'published',              to: 'unpublished'},
    {name: 'reClassify',                from: 'publishded',             to: 'newProvisional'},
    {name: 'retire',                    from: '*',                      to: 'retired'},
]

// Splits one gdm to multiple new gdms
splitToNewGdm(gdm)

// Splits one gdm to multiple new gdms
splitToNewGdms(gdm)

// Lumps two or more gdms
lumpGdms(...gdms)