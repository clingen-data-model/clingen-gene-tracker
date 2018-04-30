<?php

return [
    'curation-types' => [
        'single-omim' => 'Curate a single gene-disease entity from this list (Recommended)',
        'single-new' => 'Curate a single gene-disease entity not on this list',
        'isoloted-phenotype' => 'Curate an isolated phenotype that is part of this disease entity (Discouraged)',
        'lumped' => 'Curate a “lumped” disease entity from this list'
    ],
    'rationales' => [
        1 => 'Assertion',
        2 => 'Molecular mechanism',
        3 => 'Phenotypic Variability',
        4 => 'Inheritance pattern',
        5 => 'To dispute asserted entity',
        6 => 'Insufficient evidence for single disease entity',
        100 => 'Other'
    ]
];
