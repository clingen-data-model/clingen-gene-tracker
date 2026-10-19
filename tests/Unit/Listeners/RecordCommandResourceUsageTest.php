<?php

namespace Tests\Unit\Listeners;

use App\Listeners\RecordCommandResourceUsage;
use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Tests\TestCase;

class RecordCommandResourceUsageTest extends TestCase
{
    use DatabaseTransactions;

    private RecordCommandResourceUsage $listener;

    public function setUp(): void
    {
        parent::setUp();
        config(['logging.record_command_resource_usage' => true]);
        $this->listener = new RecordCommandResourceUsage();
    }

    private function start(string $command): void
    {
        $this->listener->handleStarting(new CommandStarting($command, new ArrayInput([]), new NullOutput()));
    }

    private function finish(string $command, int $exitCode = 0): void
    {
        $this->listener->handleFinished(new CommandFinished($command, new ArrayInput([]), new NullOutput(), $exitCode));
    }

    #[Test]
    public function records_usage_for_top_level_command()
    {
        $this->start('omim:update-data');
        $this->finish('omim:update-data', 1);

        $row = DB::table('command_resource_usages')->sole();
        $this->assertSame('omim:update-data', $row->command);
        $this->assertEquals(1, $row->exit_code);
        $this->assertGreaterThan(0, $row->max_rss_kb);
        $this->assertGreaterThan(0, $row->php_peak_bytes);
    }

    #[Test]
    public function records_only_outermost_of_nested_commands()
    {
        $this->start('omim:update-data');
        $this->start('omim:check-moved-and-removed');
        $this->finish('omim:check-moved-and-removed');
        $this->finish('omim:update-data');

        $this->assertSame(['omim:update-data'], DB::table('command_resource_usages')->pluck('command')->all());
    }

    #[Test]
    public function does_not_record_scheduler_runs()
    {
        $this->start('schedule:run');
        $this->finish('schedule:run');

        $this->assertSame(0, DB::table('command_resource_usages')->count());
    }

    #[Test]
    public function records_nothing_when_disabled()
    {
        config(['logging.record_command_resource_usage' => false]);

        $this->start('omim:update-data');
        $this->finish('omim:update-data');

        $this->assertSame(0, DB::table('command_resource_usages')->count());
    }
}
