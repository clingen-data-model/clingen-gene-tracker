<?php

namespace App\Actions;

use Carbon\Carbon;
use App\StreamError;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Query\Builder;
use Lorisleiva\Actions\Concerns\AsCommand;

class StreamErrorsClean
{
    use AsCommand;

    public $commandSignature = 'dx:clean-incoming-errors {--dry-run : Print the number of errors to delete without deleting}';

    public function handle()
    {
        $this->buildQuery()
            ->delete();
    }

    public function asCommand(Command $command)
    {
        $count = $this->buildQuery()->count();

        $dryRun = $command->option('dry-run');
        if ($dryRun) {
            $command->info('DRY RUN: Would clear '.$count.' incoming stream errors.');
            return;
        }

        $this->handle();

        $command->info('Cleared '.$count.' incoming steam errors.');
    }
    

    private function buildQuery(): Builder
    {
        return DB::table('stream_errors')
        ->where('created_at', '<', Carbon::today()->subDays(30))
        ->where('direction', 'incoming');
    }
}
