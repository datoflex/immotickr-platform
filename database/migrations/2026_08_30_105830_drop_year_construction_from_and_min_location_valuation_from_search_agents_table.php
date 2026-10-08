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
            $table->dropColumn(['year_construction_from', 'min_location_valuation']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('search_agents', function (Blueprint $table) {
            $table->unsignedSmallInteger('year_construction_from')->nullable();
            $table->string('min_location_valuation')->nullable();
        });
    }
};
