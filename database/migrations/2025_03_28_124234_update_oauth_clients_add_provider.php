<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


/**
 * This is needed because passport added a 'provider' column to the oauth_clients table
 * in version 9, but they just updated the old migration file instead of
 * making a new migration. Weird, becuase I that that was what migrations are for...
 * 
 * Because of this weirdness, there is no down migration.
 */

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('oauth_clients', 'provider')) {
            Schema::table('oauth_clients', function(Blueprint $table) {
                $table->string('provider')->nullable()->after('secret');
            });
        }
    }

    public function down(): void
    {
        //
    }
};
