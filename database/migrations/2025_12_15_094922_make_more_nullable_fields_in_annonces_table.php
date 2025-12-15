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
        Schema::table('annonces', function (Blueprint $table) {
            $table->text('description')->nullable()->change();
            $table->integer('annee')->nullable()->change();
            $table->integer('kilometrage')->nullable()->change();
            $table->string('carburant')->nullable()->change();
            $table->string('boite_vitesse')->nullable()->change();
            $table->string('ville')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('annonces', function (Blueprint $table) {
            $table->text('description')->nullable(false)->change();
            $table->integer('annee')->nullable(false)->change();
            $table->integer('kilometrage')->nullable(false)->change();
            $table->string('carburant')->nullable(false)->change();
            $table->string('boite_vitesse')->nullable(false)->change();
            $table->string('ville')->nullable(false)->change();
        });
    }
};
