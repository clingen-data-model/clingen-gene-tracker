<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('curation_curation_status', function (Blueprint $table) {
            $table->timestamp('status_date')->default(DB::raw('CURRENT_TIMESTAMP'))->after('curation_id');
        });
        DB::table('curation_curation_status')->get()->each(function ($csRow) {
            DB::table('curation_curation_status')
                ->where('id', $csRow->id)
                ->update(['status_date' => $csRow->created_at]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('curation_curation_status', function (Blueprint $table) {
            $table->dropColumn('status_date');
        });
    }
};
