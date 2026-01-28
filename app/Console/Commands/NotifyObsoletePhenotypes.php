<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Curation;
use App\AppState;
use App\Phenotype;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\User;
use App\Notifications\OmimObsoletePhenotypesDigest;
use Carbon\Carbon;

class NotifyObsoletePhenotypes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'omim:notify-obsolete-phenotypes {--days=7} {--dry-run}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'OMIM obsolete phenotypes notification';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Log::info('OMIM obsolete phenotype notification starting.');

        $state = AppState::findByName('last_omim_obsolete_digest_sent_at');
        if(! $state) { 
            $state = AppState::firstOrCreate(['name' => 'last_omim_obsolete_digest_sent_at', 'value' => now()->subDays(7)]); 
        } elseif (!$state->value) {
            $state->update(['value' => now()->subDays(7)]);
        }
        $since = $state?->value ?? now()->subDays(7);

        $newlyObsolete = Phenotype::where('obsolete', true)
            ->whereNotNull('obsoleted_at')
            ->where('obsoleted_at', '>=', $since) 
            ->get();

        $curationsByEp = Curation::query()
            ->whereHas('phenotypes', function ($q) use ($since) {
                $q->where('obsolete', true)
                  ->whereNotNull('obsoleted_at')
                  ->where('obsoleted_at', '>=', $since);
            })
            ->with([
                'expertPanel',
                'phenotypes' => function ($q) use ($since) {
                    $q->where('obsolete', true)
                      ->whereNotNull('obsoleted_at')
                      ->where('obsoleted_at', '>=', $since);
                },
            ])
            ->get()
            ->groupBy('expert_panel_id');

        if ($curationsByEp->count() === 0) {
            $this->info('No newly-obsoleted phenotypes found since '.$since);
            Log::info('OMIM obsolete phenotype digest: nothing to send.');
            return 0;
        }
            
        foreach ($curationsByEp as $epId => $curations) {
            $expertPanel = $curations->first()->expertPanel;

            $coordinators = DB::table('expert_panel_user')
                ->join('users', 'users.id', '=', 'expert_panel_user.user_id')
                ->where('expert_panel_user.expert_panel_id', $epId)
                ->where('expert_panel_user.is_coordinator', 1)
                ->whereNull('users.deleted_at')
                ->whereNull('users.deactivated_at')
                ->select('users.id')
                ->get()
                ->pluck('id');

            if ($coordinators->count() === 0) {
                Log::warning("No coordinators found for expert_panel_id={$epId}");
                continue;
            }

            $users = User::whereIn('id', $coordinators)->get();

            if ($this->option('dry-run')) {
                $this->info("DRY RUN: would notify {$users->count()} coordinator(s) for {$expertPanel->name}");
                continue;
            }

            foreach ($users as $user) {
                $user->notify(new OmimObsoletePhenotypesDigest($expertPanel, $curations, Carbon::parse($since)));
            }
        }

        // update last sent time
        if (!$this->option('dry-run')) {
            $state->update(['value' => now()]);
        }

        Log::info('OMIM obsolete phenotype digest finished.');
        return 0;

    }
}
