<?php

use App\Curation;
use App\CurationStatus;
use App\Jobs\Curations\AddStatus;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
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
     *
     * @return void
     */
    public function down()
    {
    }
};
