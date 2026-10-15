<?php

namespace App\Notifications;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class DigestRecipients
{
    public static function normalize($configured): array
    {
        return collect(is_array($configured) ? $configured : explode(',', $configured ?? ''))
            ->map(fn ($email) => strtolower(trim($email)))
            ->filter(fn ($email) => $email !== '')
            ->unique()
            ->filter(function ($email) {
                // A config typo should cost only that address, not every user's digest.
                if (Validator::make(['email' => $email], ['email' => 'email'])->fails()) {
                    Log::error('Ignoring invalid curation digest recipient address.', ['email' => $email]);
                    return false;
                }
                return true;
            })
            ->values()->all();
    }
}
