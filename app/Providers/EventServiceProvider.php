<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;
use App\Events\Curation\Created;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        'App\Events\Event' => [
            'App\Listeners\EventListener',
        ],
        'App\Events\User\Created' => [
            'App\Listeners\SendWelcomeEmail',
        ],
        'App\Events\Curation\Saved' => [
            'App\Listeners\Curations\AugmentWithHgncAndMondoInfo'
        ],
        'App\Events\Curation\Created' => [
            'App\Listeners\Curations\MakeCurationCreatedStreamMessage'
        ],
        'App\Events\Curation\Updated' => [
            'App\Listeners\Curations\MakeCurationUpdatedStreamMessage'
        ],
        'App\Events\Curation\Deleted' => [
            'App\Listeners\Curations\MakeCurationDeletedStreamMessage'
        ],
        \App\Events\Genes\GeneSymbolChanged::class => [
            \App\Listeners\Genes\UpdateCurations::class,
            \App\Listeners\Genes\NotifyGeneSymbolChanged::class
        ],
        \App\Events\Phenotypes\OmimRemovedPhenotype::class => [
            \App\Listeners\SendPhenotypeRemovedNotification::class,
            \App\Listeners\Curations\UnlinkRemovedPhenotype::class
        ],
        \App\Events\Phenotypes\OmimMovedPhenotype::class => [
            \App\Listeners\Curations\UpdateLinkedPhenotype::class,
            \App\Listeners\SendPhenotypeMovedNotification::class
        ],
        \App\Events\Phenotypes\PhenotypeNameChanged::class => [
            \App\Listeners\SendPhenotypeNameChangedNotification::class
        ],
        'App\Events\StreamMessages\Created' => [
            'App\Listeners\StreamMessages\PushMessage'
        ],
        \App\Events\StreamMessages\Received::class => [
            \App\Listeners\Curations\UpdateFromStreamMessage::class,
            \App\Listeners\SteamMessages\SetTopicOffset::class
        ],
        \Illuminate\Mail\Events\MessageSent::class => [
            \App\Listeners\Mail\StoreMailInDatabase::class
        ]
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();
    }
}
