<?php

use App\Curation;
use App\CurationStatus;
use App\Jobs\Curations\AddStatus;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $uploadedStatus = CurationStatus::find(config('project.curation-statuses.uploaded'));
        Curation::doesntHave('statuses')
            ->get()
            ->each(function ($curation) use ($uploadedStatus) {
                AddStatus::dispatch($curation, $uploadedStatus, $curation->created_at);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
