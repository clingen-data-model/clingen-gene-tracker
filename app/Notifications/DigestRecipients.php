<?php

namespace App\Notifications;

use InvalidArgumentException;

class DigestRecipients
{
    public static function normalize(array|string|null $emails): array
    {
        $emails = is_array($emails) ? $emails : explode(',', $emails ?? '');
        $result = [];
        foreach ($emails as $email) {
            $email = strtolower(trim($email));
            if ($email === '') {
                continue;
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new InvalidArgumentException('Invalid CURATION_DIGEST_ADDITIONAL_EMAILS address: '.$email);
            }
            $result[$email] = $email;
        }
        return array_values($result);
    }
}
