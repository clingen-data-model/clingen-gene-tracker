<?php

return [
    'beforeImport' => '\App\DbImport\ImportHooks@beforeImport',
    'afterImport' => '\App\DbImport\ImportHooks@afterImport',
    'ignoreTables' => null,
    'whitelist' => null,
];
