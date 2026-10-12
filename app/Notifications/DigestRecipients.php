<?php

namespace App\Notifications;

use Illuminate\Support\Facades\Validator;

class DigestRecipients
{
    public static function normalize($configured): array
    {
        $addresses = collect(is_array($configured) ? $configured : explode(',', $configured ?? ''))
            ->map(fn ($email) => strtolower(trim($email)))
            ->filter(fn ($email) => $email !== '')
            ->unique()->values()->all();

        Validator::make(['emails' => $addresses], ['emails.*' => ['required', 'email']])->validate();

        return $addresses;
    }
}
