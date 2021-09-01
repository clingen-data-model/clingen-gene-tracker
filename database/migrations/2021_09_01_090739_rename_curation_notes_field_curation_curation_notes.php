<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenameCurationNotesFieldCurationCurationNotes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('curations', function (Blueprint $table) {
            $table->renameColumn('notes', 'curation_notes');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('curations', function (Blueprint $table) {
            $table->renameColumn('curation_notes', 'notes');
        });
    }
}
