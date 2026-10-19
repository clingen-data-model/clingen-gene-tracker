<?php

namespace App\Listeners;

use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/**
 * Records CPU and peak memory of each top-level artisan process so resource
 * requests for scheduled tasks can be tuned. Stored in the database because
 * the scheduler discards child process output and CronJob pods are ephemeral.
 */
class RecordCommandResourceUsage
{
    // Each scheduled task runs in its own process and is recorded on its own;
    // the per-minute scheduler run itself would only add noise.
    private const IGNORED_COMMANDS = ['schedule:run'];

    private int $depth = 0;

    private ?float $startedAt = null;

    public function subscribe(Dispatcher $events): array
    {
        return [
            CommandStarting::class => 'handleStarting',
            CommandFinished::class => 'handleFinished',
        ];
    }

    public function handleStarting(CommandStarting $event): void
    {
        if ($this->depth++ === 0) {
            $this->startedAt = microtime(true);
        }
    }

    public function handleFinished(CommandFinished $event): void
    {
        // Commands invoked via Artisan::call() share the process, so only the
        // outermost one has meaningful process-wide usage.
        if ($this->depth === 0 || --$this->depth > 0 || ! config('logging.record_command_resource_usage')) {
            return;
        }

        if (in_array($event->command, self::IGNORED_COMMANDS, true)) {
            return;
        }

        try {
            $self = getrusage();
            $children = getrusage(1); // RUSAGE_CHILDREN

            DB::table('command_resource_usages')->insert([
                'command' => Str::limit((string) $event->command, 255, ''),
                'input' => Str::limit((string) $event->input, 1000, ''),
                'exit_code' => $event->exitCode,
                'host' => Str::limit((string) gethostname(), 255, ''),
                'started_at' => date('Y-m-d H:i:s', (int) $this->startedAt),
                'wall_seconds' => round(microtime(true) - $this->startedAt, 3),
                'cpu_user_seconds' => $this->seconds($self, 'utime'),
                'cpu_system_seconds' => $this->seconds($self, 'stime'),
                'max_rss_kb' => $self['ru_maxrss'],
                'children_cpu_seconds' => $this->seconds($children, 'utime') + $this->seconds($children, 'stime'),
                'children_max_rss_kb' => $children['ru_maxrss'],
                'php_peak_bytes' => memory_get_peak_usage(true),
            ]);
        } catch (Throwable $e) {
            report($e);
        }
    }

    private function seconds(array $usage, string $field): float
    {
        return round($usage["ru_{$field}.tv_sec"] + $usage["ru_{$field}.tv_usec"] / 1e6, 3);
    }
}
