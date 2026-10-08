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
            $table->dropColumn(['price_m2_from', 'price_m2_to']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('search_agents', function (Blueprint $table) {
            $table->decimal('price_m2_from', 10, 2)->nullable();
            $table->decimal('price_m2_to', 10, 2)->nullable();
        });
    }
};
