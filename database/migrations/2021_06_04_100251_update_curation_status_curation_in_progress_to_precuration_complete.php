<?php

use App\CurationStatus;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $status = CurationStatus::find(config('curations.statuses.curation-in-progress'));
        if ($status) {
            $status->update(['name' => 'Precuration Complete']);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $status = CurationStatus::find(config('curations.statuses.precuration-complete'));
        if ($status) {
            $status->update(['name' => 'Curation In Progress']);
        }
    }
};
