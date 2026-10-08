<?php

return [
    'digest_additional_emails' => array_values(array_filter(
        array_map('trim', explode(',', env('CURATION_DIGEST_ADDITIONAL_EMAILS', '')))
    )),
];