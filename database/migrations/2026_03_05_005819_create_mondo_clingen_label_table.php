<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('mondo_clingen_label', function (Blueprint $table) {
            $table->id();

            $table->string('mondo_id')->index();
            $table->string('xref_genevalidation')->nullable();
            $table->string('xref_source')->default('MONDO:CLINGEN');
            $table->string('see_also')->nullable();
            $table->string('see_also_source')->default('MONDO:CLINGEN');
            $table->string('clingen_label');
            $table->string('clingen_label_type')->default('http://purl.obolibrary.org/obo/mondo#CLINGEN_LABEL');
            $table->string('clingen_label_source')->nullable();
            $table->string('subset')->default('http://purl.obolibrary.org/obo/mondo#clingen');
            $table->string('subset_source')->default('MONDO:CLINGEN');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mondo_clingen_label');
    }
};
