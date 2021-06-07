<?php

use App\CurationStatus;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateCurationStatusCurationInProgressToPrecurationComplete extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $status = CurationStatus::find(config('curations.statuses.curation-in-progress'));
        if ($status) {
            $status->update(['name' => 'Precuration Complete']);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $status = CurationStatus::find(config('curations.statuses.precuration-complete'));
        if ($status) {
            $status->update(['name' => 'Curation In Progress']);
        }
    }
}
