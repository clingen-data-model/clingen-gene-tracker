<?php

use App\Services\Utilities\CleanDuplicateCurationStatuses;
use Illuminate\Database\Migrations\Migration;

class CleanDuplicateCurationCurationStatuses extends Migration
{
    protected $cleaner;

    public function __construct()
    {
        $this->cleaner = new CleanDuplicateCurationStatuses();
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $this->cleaner->clean();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $this->cleaner->restore();
    }
}
