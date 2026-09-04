<?php

namespace App\Http\Controllers\Api;

use App\Email;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class EmailAdminController extends Controller
{
    public function index(Request $request)
    {
        $perPage = min(max((int) $request->input('per_page', 25), 1), 100);
        $emails = Email::query()->orderByDesc('created_at')->orderByDesc('id')->paginate($perPage);
        $emails->getCollection()->transform(fn (Email $email) => $this->serialize($email));

        return $emails;
    }

    public function show(Email $email)
    {
        return $this->serialize($email);
    }

    private function serialize(Email $email): array
    {
        return [
            'id' => $email->id,
            'from' => $this->addresses($email, 'from'),
            'sender' => $this->addresses($email, 'sender'),
            'to' => $this->addresses($email, 'to'),
            'cc' => $this->addresses($email, 'cc'),
            'bcc' => $this->addresses($email, 'bcc'),
            'reply_to' => $this->addresses($email, 'reply_to'),
            'subject' => $email->subject,
            'body' => $email->getRawOriginal('body'),
            'created_at' => $email->created_at,
        ];
    }

    private function addresses(Email $email, string $field): array
    {
        $raw = $email->getRawOriginal($field);
        if ($raw === null || $raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [$raw => null];
    }
}
