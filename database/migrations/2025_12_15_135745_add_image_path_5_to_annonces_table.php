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
        $table->string('image_path_5')->nullable()->after('image_path_4');
    });
}

public function down(): void
{
    Schema::table('annonces', function (Blueprint $table) {
        $table->dropColumn('image_path_5');
    });
}

};
