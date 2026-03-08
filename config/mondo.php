<?php

return [
    'github' => [
        'owner' => env('GT_MONDO_OWNER', 'monarch-initiative'),
        'repo'  => env('GT_MONDO_REPO', 'mondo-clingen-test'),

        'app' => [
            'app_id' => env('GT_MONDO_APP_ID'),
            'installation_id' => env('GT_MONDO_INSTALLATION_ID'),
            'private_key_path' => env('GT_MONDO_PRIVATE_KEY_PATH'),
        ],

        'issue' => [
            'labels' => ['ClinGen', 'New term request'],
        ],
    ],

    'template' => [
        'owner' => env('GT_MONDO_TEMPLATE_OWNER', 'monarch-initiative'),
        'repo'  => env('GT_MONDO_TEMPLATE_REPO', 'mondo'),
        'ref'   => env('GT_MONDO_TEMPLATE_REF'),
        'path'  => env('GT_MONDO_TEMPLATE_PATH'),

        'cache_path' => env('GT_MONDO_TEMPLATE_CACHE_PATH', 'mondo/templates/monogenic_ntr.md.j2'),
    ],

    'renderer' => [
        'python_bin' => env('GT_MONDO_PYTHON_BIN', 'storage/app/mondo/venv/Scripts/python.exe'),
    ],
];