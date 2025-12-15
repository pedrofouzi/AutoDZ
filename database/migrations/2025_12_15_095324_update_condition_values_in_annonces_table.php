<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Convert existing condition values
        DB::table('annonces')->where('condition', 'neuf')->update(['condition' => 'oui']);
        DB::table('annonces')->where('condition', 'occasion')->update(['condition' => 'non']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert the condition values
        DB::table('annonces')->where('condition', 'oui')->update(['condition' => 'neuf']);
        DB::table('annonces')->where('condition', 'non')->update(['condition' => 'occasion']);
    }
};
