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
        \App\Events\Event::class => [
            \App\Listeners\EventListener::class,
        ],
        \App\Events\User\Created::class => [
            \App\Listeners\SendWelcomeEmail::class,
        ],
        \App\Events\Curation\Saving::class => [
            \App\Listeners\Curations\AugmentWithHgncInfo::class,
        ],
        \App\Events\Curation\Saved::class => [
            \App\Listeners\Curations\AugmentWithMondoInfo::class,
            \App\Listeners\Curations\MakeGtGciSyncMessage::class,
        ],
        \App\Events\Curation\Created::class => [
            \App\Listeners\Curations\MakeCurationCreatedStreamMessage::class,
        ],
        \App\Events\Curation\Updated::class => [
            \App\Listeners\Curations\MakeCurationUpdatedStreamMessage::class,
        ],
        \App\Events\Curation\Deleted::class => [
            \App\Listeners\Curations\MakeCurationDeletedStreamMessage::class,
        ],

        \App\Events\Disease\DiseaseNameChanged::class => [
            \App\Listeners\Disease\NotifyDiseaseNameChanged::class
        ],
        \App\Events\Disease\MondoTermObsoleted::class => [
            \App\Listeners\Disease\NotifyMondoObsoleted::class
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
