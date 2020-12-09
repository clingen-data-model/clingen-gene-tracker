<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterUploadsMakeCurationIdForeignKeyCascadeOnDelete extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('uploads', function (Blueprint $table) {
            $table->dropForeign(['curation_id']);
            $table->foreign('curation_id')
                ->references('id')
                ->on('curations')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('uploads', function (Blueprint $table) {
            $table->dropForeign(['curation_id']);
            $table->foreign('curation_id')
                ->references('id')
                ->on('curations');
        });
    }
}
