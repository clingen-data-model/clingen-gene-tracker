<?php

return [
    'types' => [
        'single-omim' => 'Curate a single gene-disease entity from this list',
        'single-new' => 'Curate a single gene-disease entity not on this list',
        'isolated-phenotype' => 'Curate an isolated phenotype that is part of this disease entity (Discouraged)',
        'lumped' => 'Curate a “lumped” disease entity from this list',
    ],
    'statuses' => [
        'uploaded' => 1,
        'precuration' => 2,
        'disease-entity-assigned' => 3,
        'in-progress' => 4,
        'curation-in-progress' => 4, // must come second for BulkCurationProcessor
        'provisional' => 5,
        'curation-provisional' => 5, // must come second for BulkCurationProcessor
        'approved' => 6,
        'curation-approved' => 6, // must come second for BulkCurationProcessor
        'recuration-assigned' => 7,
        'retired-assignment' => 8,
        'published' => 9,
        'unpublished-on-gci' => 10,
    ],
    'rationales' => [
        1 => 'Assertion',
        2 => 'Molecular mechanism',
        3 => 'Phenotypic Variability',
        4 => 'Inheritance pattern',
        5 => 'To dispute asserted entity',
        6 => 'Insufficient evidence for single disease entity',
        100 => 'Other',
    ],
    'classifications' => [
        'definitive' => 1,
        'strong' => 2,
        'moderate' => 3,
        'limited' => 4,
        'no-known-disease-relationship' => 5,
        'disputed' => 6,
        'refuted' => 7,
    ],
];
