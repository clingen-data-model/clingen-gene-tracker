<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Curation;
use App\AppState;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\User;
use Carbon\Carbon;
use App\Notifications\Curations\OmimOutdatedPhenotypesNotification;

class NotifyOutdatedPhenotypes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'omim:notify-outdated-phenotypes {--days=7} {--dry-run}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Notify coordinators about outdated phenotype labels no longer present in the current OMIM genemap2 file';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Log::info('OMIM Outdated Phenotype Labels notification starting.');
        $scanStartedAt = now();
        $days = max(1, (int) $this->option('days'));

        $state = AppState::findByName('last_omim_obsolete_digest_sent_at');
        if (!$state) {
            $state = AppState::firstOrCreate(['name' => 'last_omim_obsolete_digest_sent_at'], ['value' => now()->subDays($days)]);
        } elseif (!$state->value) {
            $state->update(['value' => now()->subDays($days)]);
        }
        $since = Carbon::parse($state->value ?? now()->subDays($days));

        $curationsByEp = Curation::query()
            ->whereHas('phenotypes', function ($query) use ($since) {
                $query->whereNotNull('label_obsolete_at')->where('label_obsolete_at', '>=', $since);
            })
            ->with([
                'expertPanel',
                'phenotypes' => function ($query) use ($since) {
                    $query->whereNotNull('label_obsolete_at')->where('label_obsolete_at', '>=', $since);
                },
            ])
            ->get()
            ->groupBy('expert_panel_id');

        if ($curationsByEp->isEmpty()) {
            $this->info('No phenotype labels newly missing from genemap2 since ' . $since->toDateTimeString());
            Log::info('OMIM Outdated Phenotype Labels notification: nothing to send.');

            if (!$this->option('dry-run')) {
                $state->update(['value' => $scanStartedAt]);
            }

            return 0;
        }

        foreach ($curationsByEp as $epId => $curations) {
            $expertPanel = $curations->first()->expertPanel;
            if (!$expertPanel) {
                Log::warning("No expert panel found for expert_panel_id={$epId}");
                continue;
            }
            $coordinatorIds = DB::table('expert_panel_user')
                ->join('users', 'users.id', '=', 'expert_panel_user.user_id')
                ->where('expert_panel_user.expert_panel_id', $epId)
                ->where('expert_panel_user.is_coordinator', 1)
                ->whereNull('users.deleted_at')
                ->whereNull('users.deactivated_at')
                ->pluck('users.id');

            if ($coordinatorIds->isEmpty()) {
                Log::warning("No coordinators found for expert_panel_id={$epId}");
                continue;
            }

            $users = User::whereIn('id', $coordinatorIds)->get();

            if ($this->option('dry-run')) {
                $this->info("DRY RUN: would notify {$users->count()} coordinator(s) for {$expertPanel->name}");
                continue;
            }

            $baseUrl = rtrim(config('app.url'), '/');
            $payload = [
                'digest_key' => sha1('omim_label_obsolete|'.$epId.'|'.$since->toDateTimeString().'|'.implode(',', $curations->pluck('id')->sort()->values()->all())),
                'since' => $since->toDateTimeString(),
                'expert_panel' => [
                    'id' => $expertPanel->id,
                    'name' => $expertPanel->name,
                ],
                'curations' => $curations->map(function ($curation) use ($baseUrl) {
                        return [
                            'id' => $curation->id,
                            'gene_symbol' => $curation->gene_symbol,
                            'link' => $baseUrl.'/home#/curations/'.$curation->id,
                            'link_text' => 'Open curation',
                            'phenotypes' => $curation->phenotypes->map(function ($phenotype) {
                                return [
                                    'id' => $phenotype->id,
                                    'mim_number' => $phenotype->mim_number,
                                    'name' => $phenotype->name,
                                    'label_obsolete_at' => $phenotype->label_obsolete_at,
                                ];
                            })->values()->all(),
                        ];
                    })->values()->all(),
            ];

            foreach ($users as $user) {
                $user->notify(new OmimOutdatedPhenotypesNotification($payload));
            }
        }

        if (!$this->option('dry-run')) {
            $state->update(['value' => $scanStartedAt]);
        }

        Log::info('OMIM Outdated Phenotype Labels notification finished.');
        return 0;

    }
}
