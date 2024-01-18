<?php

namespace Tests\Feature\Console\Commands;

use Tests\TestCase;
use App\Console\Commands\ClearExports;

final class ClearExportsTest extends TestCase
{
    private $testFileNames = [
        'test1.csv',
        'test2.csv',
        'test3.csv',
    ];

    public function setUp(): void
    {
        parent::setup();

        foreach ($this->testFileNames as $name) {
            touch(storage_path("/exports/$name"));
        }
    }

    /**
     * @test
     */
    public function it_removes_files_more_than_15_minutes_old():void
    {
        foreach ($this->testFileNames as $name) {
            $this->assertFileExists(storage_path("/exports/$name"));
        }

        $consoleCommand = new ClearExports();
        $consoleCommand->handle();

        foreach ($this->testFileNames as $name) {
            $this->assertFileDoesNotExist(storage_path("/exports/$name"));
        }
    }
}
