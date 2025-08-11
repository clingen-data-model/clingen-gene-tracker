<?php

namespace App\DataExchange\Commands;

use App\User;
use App\Affiliation;
use App\StreamError;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;
use App\DataExchange\Notifications\StreamErrorNotification;

class CreateNotificationsForStreamErrors extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dx:notify-errors';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sends notifications for errors resulting from the streaming service integeration.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $allAffiliations = Affiliation::with(['expertPanel.coordinators'])->get();

        $byClingenId = $allAffiliations->keyBy('clingen_id');        // clingen_id -> Affiliation
        $childrenByParentId = $allAffiliations->groupBy('parent_id'); // parent affiliations.id -> [children]
        $admins = User::role('admin')->get();

        $groupedErrors = StreamError::unsent()
            ->with(['geneModel', 'diseaseModel', 'moiModel'])
            ->get()
            ->groupBy('affiliation_id');

        $groupedErrors->each(function ($errors, $affiliation_clingen_id) use ($byClingenId, $childrenByParentId, $admins) {
            $aff  = $byClingenId->get($affiliation_clingen_id);
            
            $coordinators = collect();

            if ($aff) {
                $isParentLike = str_starts_with((string)$affiliation_clingen_id, '1');                
                if ($isParentLike) { // CASE WHEN THE CLINGEN IS A PARENT 1XXXX
                    $parentAff = $byClingenId->get($affiliation_clingen_id);
                    
                    if ($parentAff) {
                        $children = $childrenByParentId->get($parentAff->id, collect());
                        
                        $coordinators = $children
                            ->flatMap(fn ($child) => optional($child->expertPanel)->coordinators ?? collect())
                            ->unique('id')
                            ->values(); 
                    } 
                } else { // Non-parent (e.g., 4xxxx/5xxxx): use this affiliation's EP coordinators
                    $aff = $byClingenId->get($affiliation_clingen_id); 
                    if ($aff && $aff->expertPanel) {
                        $coordinators = $aff->expertPanel->coordinators ?? collect(); 
                    } 
                }
            }

            if ($coordinators->isNotEmpty()) { 
                Notification::send($coordinators, new StreamErrorNotification($errors));
            } else { 
                Notification::send($admins, new StreamErrorNotification($errors));
            }
        });

        $groupedErrors->flatten()->each->markSent();
    }

}
