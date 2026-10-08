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
        Schema::table('search_agents', function (Blueprint $table) {
            $table->dropColumn(['min_price_valuation', 'pot_rent_m2']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('search_agents', function (Blueprint $table) {
            $table->string('min_price_valuation')->nullable();
            $table->string('pot_rent_m2')->nullable();
        });
    }
};
