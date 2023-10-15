<?php

use App\Services\Utilities\CleanDuplicateCurationStatuses;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
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
    public function up(): void
    {
        $this->cleaner->clean();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        $this->cleaner->restore();
    }
};
