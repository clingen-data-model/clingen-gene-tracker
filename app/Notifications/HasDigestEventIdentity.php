<?php

namespace App\Notifications;

use Illuminate\Support\Str;

trait HasDigestEventIdentity
{
    protected ?string $digestEventId = null;

    protected function initializeDigestEventId(): void
    {
        $this->digestEventId = (string) Str::uuid();
    }

    public function withDigestEventId(string $id): static
    {
        $this->digestEventId = $id;

        return $this;
    }

    public function toDatabase($notifiable): array
    {
        $data = $this->toArray($notifiable);
        $data['digest_event_id'] = $this->digestEventId ??= (string) Str::uuid();

        return $data;
    }
}
