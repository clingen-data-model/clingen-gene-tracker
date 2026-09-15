<?php

namespace App\DataExchange\Commands\Gci;

use App\Curation;
use Ramsey\Uuid\Uuid;
use App\StreamMessage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Bus;
use App\Jobs\Curations\CreateStreamMessage;
use Illuminate\Database\Eloquent\Collection;

/**
 * One-time bootstrap that seeded GCI with a baseline snapshot of pre-curations
 * at launch (2021-08-26, going by the single burst in stream_messages -- see
 * documentation/history-fixup-202609.md). Kept as a historical artifact, not a
 * tool to reach for again: with --truncate it wipes stream_messages outright,
 * and even without it, re-running produces a fresh burst of messages stamped
 * with today's date while echoing whatever status each curation currently
 * holds -- exactly the kind of stale evidence
 * App\Curations\OutgoingStatusAssertions has to guard against elsewhere.
 * --i-recognize-the-risk exists so this isn't run by accident.
 */
class ProduceBaselineGTEvents extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gci:produce-baseline
                            {--limit= : number of messags to produce}
                            {--topic=test}
                            {--print : print the ouput}
                            {--truncate : truncate the stream_messages table before}
                            {--i-recognize-the-risk : required to actually run -- see class docblock}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'One-time launch bootstrap, already run in production -- do not re-run without reading the class docblock';

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
        if (!$this->option('i-recognize-the-risk')) {
            $this->error(
                'Refusing to run without --i-recognize-the-risk. This command already ran '
                .'once, at launch. Re-running it truncates stream_messages with --truncate, '
                .'and even without that flag stamps a fresh burst of messages with today\'s '
                .'date while echoing whatever status each curation currently holds -- see the '
                .'class docblock and documentation/history-fixup-202609.md before using it.'
            );

            return self::FAILURE;
        }

        $this->truncateStreamMessages();

        $curations = $this->getCurations();

        $this->info('Create messages...');
        $bar = $this->output->createProgressBar($curations->count());
        $curations->map(function ($curation) use ($bar) {
            $bar->advance();
            if (!$curation->currentStatus) {
                return;
            }

            Bus::dispatchSync(
                new CreateStreamMessage(
                    config('dx.topics.outgoing.gt-gci-sync'), 
                    $curation, 
                    'precuration_completed'
                )
            );
        });
    }

    private function truncateStreamMessages()
    {
        if ($this->option('truncate')) {
            $this->info('Truncating stream_messages table...');
            DB::table('stream_messages')->truncate();
        }
    }
    

    private function getCurations(): Collection
    {
        $this->info('Getting curations...');
        $query = Curation::query()
                    ->with(['currentStatus', 'expertPanel', 'expertPanel.affiliation', 'expertPanel.affiliation.parent', 'modeOfInheritance', 'curationType', 'phenotypes']);

        if ($this->getLimit()) {
            $query->limit($this->getLimit());
        }

        $query->whereNotNull('mondo_id')
            ->whereNotNull('moi_id')
            ->whereIn('curation_status_id', [4,5,6,7,9]);

        return $query->get();
    }
    

    private function getLimit()
    {
        if ($this->hasOption('limit')) {
            return $this->option('limit');
        }

        return null;
    }
}
